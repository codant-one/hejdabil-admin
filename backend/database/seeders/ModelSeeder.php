<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\CarModel;

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
        $models = json_decode($json_info, true);

        foreach($models as $model){
            CarModel::query()->updateOrCreate([
                'id' => $model['id'],
                'name' => $model['name'],
                'brand_id' => $model['brand_id']
            ]);
        }
    }
}
