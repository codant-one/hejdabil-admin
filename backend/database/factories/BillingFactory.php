<?php

namespace Database\Factories;

use App\Models\Billing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing>
 */
class BillingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @var string
     */

     protected $model = Billing::class;
     

    public function definition(): array
    {
        $subtotal = 0;
        $details = [];
        $tax = rand(1, 10);

        for ($i = 0; $i < rand(1, 3); $i++) {
            $quantity = rand(1, 3);
            $price = $this->faker->randomFloat(2, 10000, 30000);
            $amount = $quantity * $price;
            $subtotal += $amount;

            $details[] = [
                ['id' => 1, 'value' => 'Märke: Toyota Auris 1.6 VVT-i'],
                ['id' => 2, 'value' => $quantity],
                ['id' => 3, 'value' => $price],
                ['id' => 4, 'value' => $amount]
            ];
        }

        $total = ($subtotal * ($tax / 100)) + $subtotal;        

        return [
            'supplier_id' => rand(1,15),
            'client_id' => rand(1,15),
            'state_id' => rand(0, 1) === 0 ? 4 : 7,
            'detail' => json_encode($details, true),
            'invoice_date' => now(),
            'due_date' => now(),
            'payment_terms' => '5 dagar netto',
            'reference' => $this->faker->name,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
