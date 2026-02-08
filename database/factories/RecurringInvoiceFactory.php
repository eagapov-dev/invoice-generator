<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringInvoiceFactory extends Factory
{
    protected $model = RecurringInvoice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'frequency' => fake()->randomElement(['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly']),
            'start_date' => now()->toDateString(),
            'next_generate_date' => now()->toDateString(),
            'end_date' => null,
            'is_active' => true,
            'tax_percent' => 0,
            'discount' => 0,
            'currency' => 'USD',
            'pdf_template' => 'classic',
            'notes' => null,
        ];
    }
}
