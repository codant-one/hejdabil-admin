<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\GuarantyType;

use Str;

class GuarantyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/guaranty_types.json');
        $guarantyTypes = json_decode($json_info, true);

        foreach($guarantyTypes as $guarantyType){
            GuarantyType::query()->updateOrCreate([
                'id' => $guarantyType['id'],
                'name' => $guarantyType['name']
            ]);
        }
    }
}
