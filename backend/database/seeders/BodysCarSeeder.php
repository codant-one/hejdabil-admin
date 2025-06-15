<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\BodysCar;

use Str;

class BodysCarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/bodyscar.json');
        $bodyscar = json_decode($json_info, true);

        foreach($bodyscar as $bodycar){
            BodysCar::query()->updateOrCreate([
                'id' => $bodycar['id'],
                'name' => $bodycar['name']
            ]);
        }
    }
}
