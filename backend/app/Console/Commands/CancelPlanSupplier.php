<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

use Carbon\Carbon;
use App\Models\Supplier;

use App\Jobs\SendEmailJob;

class CancelPlanSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:cancel-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel plan for suppliers with expired grace period';

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
        self::cancelPlan();

        return 0;
    }

    private function cancelPlan()
    {
        $today = Carbon::today();

        $suppliers = 
            Supplier::with('plan', 'user')
                ->where('state_id', 2)
                ->whereNotNull('cancellation_date')
                ->whereNotNull('grace_end_date')
                ->whereDate('grace_end_date', '>=', $today)// ya paso la fecha de gracia
                ->get();

        foreach($suppliers as $supplier) {
            $supplier->is_subscription_active = 0;//desactiva la suscripción
            $supplier->save();

            //NUEVO OJO
            //inactivo, ya no usa mas la plataforma, se elimina el proveedor y sus usuarios
            $supplier->deleteSupplier($supplier->id);

            $email = $supplier->user->email;
            $subject = 'Ditt abonnemang hos Bilflogg har avslutats';
            $text_primary = "Vi vill informera dig om att din uppsägningstid på tre månader nu har löpt ut och att ditt abonnemang hos Bilflogg därmed har avslutats.<br>";
            $text_primary .= "Ditt konto är inte längre aktivt och du har inte längre tillgång till Bilfloggs tjänster.<br>";
            $text_secondary  = "Om du framöver vill börja använda Bilflogg igen är du välkommen att kontakta oss för att återaktivera ditt konto och abonnemang.<br>";
            $text_secondary .= "Har du några frågor är du alltid välkommen att höra av dig till oss.<br>";

            $data = [
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name ,
                'text_primary' => $text_primary,
                'text_secondary' => $text_secondary,
                'title' => $subject,
                'icon' => asset('/images/important.png')
            ];

            // Send email asynchronously
            SendEmailJob::dispatch(
                'emails.suppliers.notifications',
                $data,
                $email,
                $subject
            );
        }    

        return 0;
    }
}
