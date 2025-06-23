<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Fuel;

use Str;

class FuelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = ['Diesel', 'Bensin', 'Hybrid - Diesel', 'Hybrid - Bensin', 'El'];
        $labels = ['diesel', 'gasoline', 'hybrid_diesel', 'hybrid_gasoline', 'electric'];
							
        foreach($names as $key => $name) {
            Fuel::updateOrCreate(
                ['name' => $name],
                ['label' => strtolower($labels[$key])] 
            );
        }
    }
}
