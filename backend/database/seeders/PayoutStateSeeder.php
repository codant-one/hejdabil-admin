<?php

namespace Database\Seeders;

use App\Models\PayoutState;
use Illuminate\Database\Seeder;

class PayoutStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = ['Skapad', 'Skickad', 'Väntande', 'Betald', 'Misslyckad', 'Avbruten', 'Utgången', 'Debiterad'];
        $labels = ['Created', 'Sent', 'Pending', 'Paid', 'Failed', 'Cancelled', 'Expired', 'Debited'];

        foreach($states as $key => $state) {
            PayoutState::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
