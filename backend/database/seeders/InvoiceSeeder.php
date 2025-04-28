<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Invoice;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $invoices = [ 
            [
                'type_id' => 1,
                'name' => 'Produkt / tjänst',
                'description' => 'Beskrivning av produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 2,
                'name' => 'Antal',
                'description' => 'Kvantitet av produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 3,
                'name' => 'À-pris',
                'description' => 'Enhetspris för produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 3,
                'name' => 'Belopp',
                'description' => 'Ackumulerat prisbelopp per produkt eller tjänst',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        Invoice::insert($invoices);
        
    }
}
