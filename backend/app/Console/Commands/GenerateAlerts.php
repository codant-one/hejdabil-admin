<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Models\Alert;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;

class GenerateAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate alerts for users';

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
        self::overdueBillingSupplier();

        return 0;
    }

    private function overdueBillingSupplier() {

        $billings = SupplierInvoice::where('due_date', '<', now())
                                    ->where('state_id', 4)
                                    ->get();
        
        foreach($billings as $billing) {//facturas vencidas
            $billing = SupplierInvoice::find($billing->id);
            $billing->state_id = 8;// actualizo estado a vencido
            $billing->save();

            // Crear alerta para supplier boss
            Alert::create([
                'user_id' => $billing->supplier->user_id,
                'supplier_id' => $billing->supplier_id,
                'alert_id' => $billing->id,
                'title' => 'Du har en förfallen faktura',
                'subtitle' => 'Din faktura till Bilflogg har förfallit och behöver betalas så snart som möjligt för att undvika att ditt abonnemang och tillgång till tjänsten pausas. <br>Du hittar fakturan under <strong>Inställningar</strong> i Bilflogg samt i din e-post.',
                'color' => 'error',
                'icon' => 'custom-alert-pending',
                'route' => '/dashboard/settings/plan#tab-billings'
            ]);

            $supplier = Supplier::find($billing->supplier_id);

            if (!$supplier) {
                continue;
            }

            $bossId = $supplier->boss_id ?: $supplier->id;
            $users = Supplier::where('boss_id', $bossId)->get();

            foreach($users as $supplierUser) {
                $user = User::find($supplierUser->user_id);

                if (!$user) {
                    continue;
                }

                // Crear alerta para cada usuario del supplier
                Alert::create([
                    'user_id' => $user->id,
                    'supplier_id' => $bossId,
                    'alert_id' => $billing->id,
                    'title' => 'Du har en förfallen faktura',
                    'subtitle' => 'Din faktura till Bilflogg har förfallit och behöver betalas så snart som möjligt för att undvika att ditt abonnemang och tillgång till tjänsten pausas. <br>Du hittar fakturan under <strong>Inställningar</strong> i Bilflogg samt i din e-post.',
                    'color' => 'error',
                    'icon' => 'custom-alert-pending',
                    'route' => '/dashboard/settings/plan#tab-billings'
                ]);
            }
        }
        
        $this->info('Total overdue billings evaluated: ' . $billings->count());
    }

}
