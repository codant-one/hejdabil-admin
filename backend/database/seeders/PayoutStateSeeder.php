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
        $states = ['Väntade', 'Skickad', 'Avbruten', 'Slutförd', 'Misslyckad', 'Utgången', 'Debiterad'];
        $labels = ['Created', 'Sent', 'Pending', 'Paid', 'Failed', 'Expired', 'Debited'];

        foreach($states as $key => $state) {
            PayoutState::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
