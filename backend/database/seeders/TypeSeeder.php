<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Type;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [ 
            [
                'name' => 'Text',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Nummer',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Decimal',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        Type::insert($types);
        
    }
}
