<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Guaranty;

use Str;

class GuarantySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/guaranties.json');
        $guaranties = json_decode($json_info, true);

        foreach($guaranties as $guaranty){
            Guaranty::query()->updateOrCreate([
                'id' => $guaranty['id'],
                'name' => $guaranty['name']
            ]);
        }
    }
}
