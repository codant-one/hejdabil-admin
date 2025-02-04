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
                'name_en' => 'Text',
                'name_se' => 'Tekst',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name_en' => 'Number',
                'name_se' => 'Tall',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name_en' => 'Decimal',
                'name_se' => 'Desimal',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        Type::insert($types);
        
    }
}
