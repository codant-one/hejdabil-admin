<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\InsuranceType;

use Str;

class InsuranceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/insurance_types.json');
        $insuranceTypes = json_decode($json_info, true);

        foreach($insuranceTypes as $insuranceType){
            InsuranceType::query()->updateOrCreate([
                'id' => $insuranceType['id'],
                'name' => $insuranceType['name']
            ]);
        }
    }
}
