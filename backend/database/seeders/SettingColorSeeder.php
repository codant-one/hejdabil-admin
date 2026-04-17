<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\SettingColor;

use Str;

class SettingColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json_info = Storage::disk('local')->get('/json/colors.json');
        $colors = json_decode($json_info, true);

        foreach($colors as $color){
            SettingColor::query()->updateOrCreate([
                'id' => $color['id'],
                'primary' => $color['primary_color'],
                'secondary' => $color['secondary_color']
            ]);
        }
    }
}
