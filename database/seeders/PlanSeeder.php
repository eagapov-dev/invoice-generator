<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_invoices_per_month' => 3,
                'max_clients' => 5,
                'max_products' => 10,
                'custom_logo' => false,
                'custom_templates' => false,
                'recurring_invoices' => false,
                'remove_watermark' => false,
                'export_csv' => false,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 5.00,
                'price_yearly' => 48.00,
                'max_invoices_per_month' => 50,
                'max_clients' => -1,
                'max_products' => -1,
                'custom_logo' => true,
                'custom_templates' => true,
                'recurring_invoices' => false,
                'remove_watermark' => true,
                'export_csv' => true,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 12.00,
                'price_yearly' => 120.00,
                'max_invoices_per_month' => -1,
                'max_clients' => -1,
                'max_products' => -1,
                'custom_logo' => true,
                'custom_templates' => true,
                'recurring_invoices' => true,
                'remove_watermark' => true,
                'export_csv' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
