<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'subtotal' => fake()->randomFloat(2, 100, 10000),
            'tax_percent' => fake()->randomElement([0, 5, 10, 20]),
            'discount' => fake()->randomFloat(2, 0, 100),
            'total' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
        ]);
    }
}
