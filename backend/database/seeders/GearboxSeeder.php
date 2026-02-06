<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Gearbox;

use Str;

class GearboxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/gearboxes.json');
        $gearboxes = json_decode($json_info, true);

        foreach($gearboxes as $gearbox){
            Gearbox::query()->updateOrCreate([
                'id' => $gearbox['id'],
                'name' => $gearbox['name']
            ]);
        }
    }
}