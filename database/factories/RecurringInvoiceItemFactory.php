<?php

namespace Database\Factories;

use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringInvoiceItemFactory extends Factory
{
    protected $model = RecurringInvoiceItem::class;

    public function definition(): array
    {
        return [
            'recurring_invoice_id' => RecurringInvoice::factory(),
            'product_id' => null,
            'description' => fake()->sentence(3),
            'quantity' => fake()->numberBetween(1, 10),
            'price' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
