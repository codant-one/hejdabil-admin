<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\CarBody;

use Str;

class CarBodySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/carbodies.json');
        $carbodies = json_decode($json_info, true);

        foreach($carbodies as $carbody){
            CarBody::query()->updateOrCreate([
                'id' => $carbody['id'],
                'name' => $carbody['name']
            ]);
        }
    }
}
