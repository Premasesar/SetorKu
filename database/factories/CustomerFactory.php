<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory Customer dengan koordinat sekitar DI Yogyakarta.
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory()->state([
                'name' => $name,
                'role' => 'nasabah',
            ]),
            'name' => $name,
            'address' => fake()->streetAddress() . ', Yogyakarta',
            'latitude' => fake()->latitude(-7.8400000, -7.7500000),
            'longitude' => fake()->longitude(110.3300000, 110.4100000),
            'balance' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
