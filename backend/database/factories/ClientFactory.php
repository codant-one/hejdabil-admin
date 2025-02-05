<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @var string
     */

     protected $model = Client::class;
     

    public function definition(): array
    {
        return [
            'supplier_id' => rand(1,15),
            'fullname' => $this->faker->name,
            'email' => $this->faker->email,
            'address' => $this->faker->address,
            'street' => $this->faker->streetName,
            'postal_code' => $this->faker->buildingNumber,
            'phone' => $this->faker->phoneNumber,
            'reference' => $this->faker->name,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
