<?php

namespace Database\Seeders;

use App\Models\Billing;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        for($i = 1; $i <= 20; $i++)
        {
            Billing::factory(['invoice_id' => $i])->create();
        }
    }
    
}
