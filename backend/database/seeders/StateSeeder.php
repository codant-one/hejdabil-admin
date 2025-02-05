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
        $states = ['Inactive', 'Active', 'Published', 'Pending', 'Deleted', 'Rejected', 'Paid'];

        foreach($states as $state){
            State::create([
                'name' => $state,
                'label' => strtolower($state)
            ]);
        }

    }
}
