<?php

namespace Database\Seeders;

use App\Models\Gender;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $genders = [ 
            [
                'name' => 'Female',
                'code' => 'F',
            ],
            [
                'name' => 'Male',
                'code' => 'M',
            ],
            [
                'name' => 'Others',
                'code' => 'O',
            ]
        ];

        Gender::insert($genders);
    }
}
