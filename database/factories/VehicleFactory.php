<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'vehicle_type' => $this->faker->word,
            'brand_id' => Brand::factory(),
            'year_of_manufacture' => $this->faker->year,
            'engine_number' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'chassis_number' => $this->faker->unique()->regexify('[A-Z0-9]{17}'),
            'vehicle_number' => $this->faker->unique()->regexify($this->getVehicleNumberFormat()),
            'customer_id' => Customer::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function getVehicleNumberFormat(): string
    {
        $type = $this->faker->randomElement(['personal', 'government', 'company', 'rich']);
        switch ($type) {
            case 'personal':
                return 'P[0-9]{4}';
            case 'government':
                return 'G[0-9]{4}';
            case 'company':
                return 'C[0-9]{4}';
            case 'rich':
                return '[0-9]{4}';
            default:
                return '[A-Z0-9]{5}';
        }
    }
}
