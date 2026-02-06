<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Identification;

use Str;

class IdentificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $names = ['Pass', 'Körkort', 'Mobilt bank-ID'];
        // $labels = ['passport', 'driver_license', 'mobile_bank_id'];
							
        $names = ['Pass', 'Körkort', 'ID Kort'];
        $labels = ['passport', 'driver_license', 'id_card'];

        foreach($names as $key => $name) {
            Identification::updateOrCreate(
                ['name' => $name],
                ['label' => strtolower($labels[$key])] 
            );
        }
    }
}
