<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\PaymentType;

use Str;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/payment_types.json');
        $paymentTypes = json_decode($json_info, true);

        foreach($paymentTypes as $paymentType){
            PaymentType::query()->updateOrCreate([
                'id' => $paymentType['id'],
                'name' => $paymentType['name']
            ]);
        }
    }
}
