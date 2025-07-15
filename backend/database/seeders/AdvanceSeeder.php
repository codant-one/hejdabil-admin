<?php

namespace Database\Seeders;

use App\Models\Advance;
use Illuminate\Database\Seeder;

class AdvanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $advances = range(5, 95, 5);

        foreach ($advances as $advance) {
            Advance::updateOrCreate(['name' => $advance]);
        }

        Advance::updateOrCreate(['name' => 'Ange handpenningen manuellt under summa kontant / handpenning']);

    }
}
