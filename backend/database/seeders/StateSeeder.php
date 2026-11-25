<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = [
            ['name' => 'Inaktiv', 'label' => 'inactive'],
            ['name' => 'Aktiv', 'label' => 'active'],
            ['name' => 'Publicerad', 'label' => 'published'],
            ['name' => 'Obetald', 'label' => 'unpaid'],
            ['name' => 'Borttagen', 'label' => 'removed'],
            ['name' => 'Avvisad', 'label' => 'rejected'],
            ['name' => 'Betald', 'label' => 'paid'],
            ['name' => 'Förfallna', 'label' => 'overdue'],
            ['name' => 'Krediterad', 'label' => 'credit'],
            ['name' => 'I lager', 'label' => 'in stock'],
            ['name' => 'På annons', 'label' => 'announced'],
            ['name' => 'Såld', 'label' => 'sold'],
            ['name' => 'Förmedlingsbil', 'label' => 'rented'],
            ['name' => 'Väntande', 'label' => 'pending'],
            ['name' => 'I kö', 'label' => 'queued'],
            ['name' => 'Sändning', 'label' => 'sending'],
            ['name' => 'Skickad', 'label' => 'sent'],
            ['name' => 'Misslyckades', 'label' => 'failed'],
        ];

        foreach($states as $state) {
            State::updateOrCreate(
                ['name' => $state['name']],
                ['label' => $state['label']] 
            );
        }

    }
}
