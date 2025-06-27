<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_info = Storage::disk('local')->get('/json/currency.json');
        $currencies = json_decode($json_info, true);

        foreach($currencies as $currency){
            Equipment::query()->updateOrCreate([
                'id' => $currency['id'],
                'name' => $currency['name']
            ]);
        }
    }
}
