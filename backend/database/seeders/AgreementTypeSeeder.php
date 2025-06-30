<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\AgreementType;

use Str;

class AgreementTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * @return void
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/agreement_types.json');
        $agreementTypes = json_decode($json_info, true);

        foreach($agreementTypes as $agreementType){
            AgreementType::query()->updateOrCreate([
                'id' => $agreementType['id'],
                'name' => $agreementType['name'],
                'value' => $agreementType['value']
            ]);
        }
    }
}
