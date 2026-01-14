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
        $states = ['Väntande', 'Skickad', 'Avbruten', 'Slutförd', 'Misslyckad', 'Utgången'];
        $labels = ['created', 'sent', 'cancel', 'paid', 'failed', 'expired'];

        foreach($states as $key => $state) {
            PayoutState::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
