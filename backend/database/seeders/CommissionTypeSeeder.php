<?php

namespace Database\Seeders;

use App\Models\CommissionType;
use Illuminate\Database\Seeder;

class CommissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = ['Fast avgift', 'Procent'];
        $labels = ['fixed_fee', 'percentage'];

        foreach($types as $key => $type) {
            CommissionType::updateOrCreate(
                ['name' => $type],
                ['label' => strtolower($labels[$key])] 
            );
        }

    }
}