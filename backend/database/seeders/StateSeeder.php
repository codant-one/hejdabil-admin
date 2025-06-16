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
        $states = ['Inaktiv', 'Aktiv', 'Publicerad', 'Väntande', 'Borttagen', 'Avvisad', 'Betalad', 'Förfallna', 'Kredit', 'På lager', 'På annons', 'Såld', 'Förmedlingsbil'];
        $labels = ['Inactive', 'Active', 'Published', 'Pending', 'Removed', 'Rejected', 'Paid', 'Overdue', 'Credit', 'In stock', 'Announced', 'Sold', 'Rented'];

        foreach($states as $key => $state) {
            State::updateOrCreate(
                ['name' => $state],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}
