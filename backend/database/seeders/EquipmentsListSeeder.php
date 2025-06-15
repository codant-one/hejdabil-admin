<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\EquipmentsList;

use Str;

class EquipmentsListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/equipmentslist.json');
        $equipmentsList = json_decode($json_info, true);

        foreach($equipmentsList as $equipment){
            EquipmentsList::query()->updateOrCreate([
                'id' => $equipment['id'],
                'name' => $equipment['name']
            ]);
        }
    }
}
