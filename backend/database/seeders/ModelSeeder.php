<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\ModelCar;

use Str;

class ModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/models.json');
        $modelCar = json_decode($json_info, true);

        foreach($modelCar as $model){
            ModelCar::query()->updateOrCreate([
                'id' => $model['id'],
                'name' => $model['name'],
                'brand_id' => $model['brand_id']
            ]);
        }
    }
}
