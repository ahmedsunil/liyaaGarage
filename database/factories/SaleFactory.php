<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $discountPercentage = $this->faker->randomFloat(2, 0, 20);
        $discountAmount = $subtotal * ($discountPercentage / 100);
        $totalAmount = $subtotal - $discountAmount;

        return [
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'vehicle_id' => function () {
                return Vehicle::inRandomOrder()->first()?->id;
            },
            'customer_id' => function () {
                return Customer::inRandomOrder()->first()?->id;
            },
            'quotation_id' => null, // We'll set this in a state method if needed
            'transaction_type' => $this->faker->randomElement(['pending', 'cash', 'transfer']),
            'subtotal_amount' => $subtotal,
            'is_service' => $this->faker->boolean(30), // 30% chance of being a service
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'remarks' => $this->faker->optional(0.7)->paragraph, // 70% chance of having remarks
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function fromQuotation($quotation = null)
    {
        return $this->state(function (array $attributes) use ($quotation) {
            if (!$quotation) {
                $quotation = \App\Models\Quotation::inRandomOrder()->first();
            }

            if ($quotation) {
                return [
                    'quotation_id' => $quotation->id,
                    'vehicle_id' => $quotation->vehicle_id,
                    'customer_id' => $quotation->customer_id,
                    'subtotal_amount' => $quotation->subtotal_amount,
                    'is_service' => $quotation->is_service,
                    'discount_percentage' => $quotation->discount_percentage,
                    'discount_amount' => $quotation->discount_amount,
                    'total_amount' => $quotation->total_amount,
                ];
            }

            return [];
        });
    }
}
