<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_type'        => $this->faker->word(),
            'date'                => Carbon::now(),
            'amount'              => $this->faker->randomFloat(),
            'description'         => $this->faker->text(),
            'payment_method'      => $this->faker->word(),
            'vendor'              => $this->faker->word(),
            'invoice_number'      => $this->faker->word(),
            'category'            => $this->faker->word(),
            'attachment'          => $this->faker->word(),
            'notes'               => $this->faker->word(),
            'unit_price'          => $this->faker->randomNumber(),
            'qty'                 => $this->faker->randomNumber(),
            'rate'                => $this->faker->randomFloat(),
            'gst'                 => $this->faker->randomFloat(),
            'unit_price_with_gst' => $this->faker->word(),
            'total_expenses'      => $this->faker->word(),
            'created_at'          => Carbon::now(),
            'updated_at'          => Carbon::now(),
        ];
    }
}
