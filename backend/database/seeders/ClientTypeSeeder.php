<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\ClientType;

use Str;

class ClientTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = ['Privat', 'Företag', 'Utländsk kund'];
        $labels = ['private', 'company', 'foreign'];
							
        foreach($names as $key => $name) {
            ClientType::updateOrCreate(
                ['name' => $name],
                ['label' => strtolower($labels[$key])] 
            );
        }
    }
}
