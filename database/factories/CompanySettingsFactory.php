<?php

namespace Database\Factories;

use App\Models\CompanySettings;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanySettingsFactory extends Factory
{
    protected $model = CompanySettings::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'logo' => null,
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'bank_details' => "Bank: " . fake()->company() . "\nAccount: " . fake()->iban(),
            'default_currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'default_tax_percent' => fake()->randomElement([0, 5, 10, 20]),
        ];
    }
}
