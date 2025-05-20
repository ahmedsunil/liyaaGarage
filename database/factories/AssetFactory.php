<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' ' . $this->faker->randomElement(['Tool', 'Equipment', 'Machine', 'Vehicle']),
            'type' => $this->faker->randomElement(['Tool', 'Equipment', 'Machine', 'Vehicle', 'Computer', 'Furniture']),
            'purchased_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'purchased_price' => $this->faker->randomFloat(2, 100, 10000),
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['usable', 'damaged', 'maintenance']),
        ];
    }
}
