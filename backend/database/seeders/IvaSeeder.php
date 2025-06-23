<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Iva;

use Str;

class IvaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/ivas.json');
        $ivas = json_decode($json_info, true);

        foreach($ivas as $iva){
            Iva::query()->updateOrCreate([
                'id' => $iva['id'],
                'name' => $iva['name'],
                'value' => $iva['value']
            ]);
        }
    }
}
