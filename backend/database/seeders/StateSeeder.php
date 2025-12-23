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
        $states = ['Inaktiv', 'Aktiv', 'Publicerad', 'Obetald', 'Borttagen', 'Avvisad', 'Betald', 'Förfallna', 'Krediterad', 'På lager', 'På annons', 'Såld', 'Förmedlingsbil', 'Väntande'];
        $labels = ['Inactive', 'Active', 'Published', 'Unpaid', 'Removed', 'Rejected', 'Paid', 'Overdue', 'Credit', 'In stock', 'Announced', 'Sold', 'Rented', 'Pending'];

        foreach($states as $key => $state) {
            State::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
