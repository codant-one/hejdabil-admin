<?php

namespace Database\Factories;

use App\Models\UserDetails;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserDetailsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDetails::class;


    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $mod_create_date = strtotime(Carbon::now()->format('Y-m-d H:m:s')."- ".rand(1,60)." days");
        $created_at = date("Y-m-d H:m:s", $mod_create_date);
        $arr = array("\r\n", "\n", "\r");

        return [
            'gender_id' => rand(1,2),
            'document' => strval(rand(1,22999999)),
            'phone' => $this->faker->e164PhoneNumber,
            'address' => str_replace($arr, '', $this->faker->address),
            'created_at' => $created_at,
            'updated_at' => $created_at
        ];
    }
}
