<?php

namespace Database\Factories;

use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteType>
 */
class WasteTypeFactory extends Factory
{
    protected $model = WasteType::class;

    public function definition(): array
    {
        return [
            'name' => 'Sampah ' . fake()->unique()->lexify('????-####'),
            'unit' => 'kg',
            'price_per_unit' => fake()->randomFloat(2, 1000, 10000),
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
