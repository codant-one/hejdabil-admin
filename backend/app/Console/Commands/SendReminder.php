<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

use App\Models\Config;
use App\Models\Billing;
use App\Models\Agreement;

class SendReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for overdue billings and agreements';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        self::billings();
        self::agreements();

        return 0;
    }

    private function billings()
    {
        $delayCounter = 0;
        $delayStep = 3; // segundos de pausa entre cada correo

        // Facturas vencidas
        $billings = Billing::with(['supplier.settings.billing'])
            ->where('state_id', 8)
            ->get();

        foreach($billings as $billing){
            
            if($billing?->supplier) { // la crea un supplier                
                $send_reminder = ((int) ($billing?->supplier?->settings?->billing?->send_reminder ?? 0)) === 1;
                $days = (int) ($billing?->supplier?->settings?->billing?->due_date ?? 1);
            } else {//es administrativa
                $billingConfig = Config::getByKey('billings') ?? ['value' => '{}'];

                // Extract the "value" supporting array or object
                $getValue = function ($cfg) {
                    if (is_array($cfg)) 
                        return $cfg['value'] ?? '{}';
                    if (is_object($cfg) && isset($cfg->value))
                        return $cfg->value;
                    return '{}';
                };
                
                $billingRaw = $getValue($billingConfig);

                if (is_string($billingRaw)) {
                    $billingRaw = json_decode($billingRaw, true) ?? [];
                }

                if (!is_array($billingRaw)) {
                    $billingRaw = [];
                }

                $send_reminder = ((int) ($billingRaw['send_reminder'] ?? 0)) === 1;
                $days = (int) ($billingRaw['due_date'] ?? 1);
            }

            $this->info('Billing: #' . $billing->id . ' - Send reminder: ' . ($send_reminder ? 'Yes' : 'No'));

            $isOverdue = $billing->due_date && \Carbon\Carbon::parse($billing->due_date)->lte(now()->subDays($days));

            if($send_reminder && $isOverdue) {
                try {
                    $billingToSend = Billing::find($billing->id);

                    if (!$billingToSend) {
                        $this->warn('The billing could not be found: ' . $billing->id);
                        continue;
                    }

                    if ($delayCounter > 0) {
                        sleep($delayStep);
                    }

                    Billing::createReminder($billingToSend);
                    $delayCounter++;

                    $this->info('Billing: #' . $billing->id . ' - Reminder sent (' . $delayCounter . ').');
                } catch (\Throwable $e) {
                    $this->error('Error creating reminder for billing ' . $billing->id . ': ' . $e->getMessage());
                    continue;
                }
            }
        }    

        return 0;
    }

    private function agreements()
    {
        $delayCounter = 0;
        $delayStep = 3; // segundos de pausa entre cada correo

        // Contratos enviados pero no firmados
        $agreements = Agreement::with(['supplier.settings.agreement', 'token'])
            ->whereHas('token', function ($query) {
                $query->where('signature_status', 'delivered');
            })
            ->get();

        foreach($agreements as $agreement){
            
            if($agreement?->supplier) { // la crea un supplier                
                $send_reminder = ((int) ($agreement?->supplier?->settings?->agreement?->send_reminder ?? 0)) === 1;
                $days = (int) ($agreement?->supplier?->settings?->agreement?->due_dates ?? 1);
            } else {//es administrativa
                $agreementConfig = Config::getByKey('agreements') ?? ['value' => '{}'];

                // Extract the "value" supporting array or object
                $getValue = function ($cfg) {
                    if (is_array($cfg)) 
                        return $cfg['value'] ?? '{}';
                    if (is_object($cfg) && isset($cfg->value))
                        return $cfg->value;
                    return '{}';
                };
                
                $agreementRaw = $getValue($agreementConfig);

                if (is_string($agreementRaw)) {
                    $agreementRaw = json_decode($agreementRaw, true) ?? [];
                }

                if (!is_array($agreementRaw)) {
                    $agreementRaw = [];
                }

                $send_reminder = ((int) ($agreementRaw['send_reminder'] ?? 0)) === 1;
                $days = (int) ($agreementRaw['due_date'] ?? 1);
            }

            $this->info('Agreement: #' . $agreement->id . ' - Send reminder: ' . ($send_reminder ? 'Yes' : 'No'));

            $isOverdue = $agreement->token?->updated_at && \Carbon\Carbon::parse($agreement->token->updated_at)->lte(now()->subDays($days));

           if($send_reminder && $isOverdue) { // envio el recordatorio
                try {
                    $agreementToSend = Agreement::find($agreement->id);

                    if (!$agreementToSend) {
                        $this->warn('The agreement could not be found: ' . $agreement->id);
                        continue;
                    }

                    if ($delayCounter > 0) {
                        sleep($delayStep);
                    }

                    Agreement::createReminder($agreementToSend);
                    $delayCounter++;

                    $this->info('Agreement: #' . $agreement->id . ' - Reminder sent (' . $delayCounter . ').');
                } catch (\Throwable $e) {
                    $this->error('Error creating reminder for agreement ' . $agreement->id . ': ' . $e->getMessage());
                    continue;
                }
            }
        }    

        return 0;
    }

}
