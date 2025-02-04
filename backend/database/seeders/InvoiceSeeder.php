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
                'name_en' => 'Product / Service',
                'name_se' => 'Produkt / tjänst',
                'description_en' => 'Description of the product or service',
                'description_se' => 'Beskrivning av produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 2,
                'name_en' => 'Quantity',
                'name_se' => 'Antal',
                'description_en' => 'Quantity of the product or service',
                'description_se' => 'Kvantitet av produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 3,
                'name_en' => 'Unit Price',
                'name_se' => 'À-pris',
                'description_en' => 'Unit price of the product or service',
                'description_se' => 'Enhetspris för produkten eller tjänsten',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type_id' => 3,
                'name_en' => 'Amount',
                'name_se' => 'Belopp',
                'description_en' => 'Accumulated price amount per product or service',
                'description_se' => 'Ackumulerat prisbelopp per produkt eller tjänst',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        Invoice::insert($invoices);
        
    }
}
