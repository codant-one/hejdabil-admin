<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;

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

        if (!file_exists(storage_path('app/public/brands'))) {
            mkdir(storage_path('app/public/brands'), 0755,true);
        } //create a folder

        $file = new Filesystem;
        $file->cleanDirectory('storage/app/public/brands');

        foreach($brands as $brand){
            Brand::query()->updateOrCreate([
                'id' => $brand['id'],
                'name' => $brand['name'],
                'logo' => ($brand['logo'] === 'null') ? null : $brand['logo']
            ]);

            if($brand['logo'] !== 'null')
                copy(public_path($brand['logo']), storage_path('app/public/').$brand['logo']);
        }
    }
}
