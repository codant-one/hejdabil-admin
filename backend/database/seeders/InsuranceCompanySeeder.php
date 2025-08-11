<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\InsuranceCompany;

use Str;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/insurance_companies.json');
        $insuranceCompanies = json_decode($json_info, true);

        foreach($insuranceCompanies as $insuranceCompany){
            InsuranceCompany::query()->updateOrCreate([
                'id' => $insuranceCompany['id'],
                'name' => $insuranceCompany['name']
            ]);
        }
    }
}
