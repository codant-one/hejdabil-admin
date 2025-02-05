<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @var string
     */

     protected $model = Supplier::class;
     

    public function definition(): array
    {
        return [
            'company' => $this->faker->company,
            'organization_number' => $this->faker->buildingNumber,
            'address' => $this->faker->address,
            'street' => $this->faker->streetName,
            'postal_code' => $this->faker->buildingNumber,
            'phone' => $this->faker->phoneNumber,
            'bank' => $this->faker->name,
            'account_number' => $this->faker->bankAccountNumber,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
