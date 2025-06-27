<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Currency;

use Str;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/currencies.json');
        $currencies = json_decode($json_info, true);

        foreach($currencies as $currency){
            Currency::query()->updateOrCreate([
                'id' => $currency['id'],
                'name' => $currency['name']
            ]);
        }
    }
}
