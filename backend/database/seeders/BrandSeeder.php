<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Brand;

use Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/brands.json');
        $brands = json_decode($json_info, true);

        foreach($brands as $brand){
            Brand::query()->updateOrCreate([
                'id' => $brand['id'],
                'name' => $brand['name']
            ]);
        }
    }
}
