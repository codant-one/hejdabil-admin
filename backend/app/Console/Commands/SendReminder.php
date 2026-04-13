<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

use App\Models\Config;
use App\Models\Billing;

class SendReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder-billings:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for overdue billings';

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
        // Facturas con al menos 1 dia de vencimiento
        $billings = Billing::with(['supplier.settings.billing'])
            ->where('state_id', 8)
            ->whereDate('due_date', '<=', now()->subDay())
            ->get();

        foreach($billings as $billing){
            
            if($billing?->supplier) { // la crea un supplier                
                $send_reminder = ((int) ($billing?->supplier?->settings?->billing?->send_reminder ?? 0)) === 1;
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
            }

            $this->info('Billing: #' . $billing->id . ' - Send reminder: ' . ($send_reminder ? 'Yes' : 'No'));

            if($send_reminder) { // envio el recordatorio
                try {
                    $billingToSend = Billing::find($billing->id);

                    if (!$billingToSend) {
                        $this->warn('The billing could not be found: ' . $billing->id);
                        continue;
                    }

                    Billing::createReminder($billingToSend);
                } catch (\Throwable $e) {
                    $this->error('Error creating reminder for billing ' . $billing->id . ': ' . $e->getMessage());
                    continue;
                }
            }
        }    

        return 0;
    }

}
