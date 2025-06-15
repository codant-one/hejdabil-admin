<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Equipment;

use Str;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/equipments.json');
        $equipments = json_decode($json_info, true);

        foreach($equipments as $equipment){
            Equipment::query()->updateOrCreate([
                'id' => $equipment['id'],
                'name' => $equipment['name']
            ]);
        }
    }
}
