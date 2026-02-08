<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

class PlanLimitService
{
    public function canCreateInvoice(User $user): bool
    {
        $plan = $user->activePlan();
        if ($plan->isUnlimited('max_invoices_per_month')) {
            return true;
        }

        return $user->monthly_invoice_count < $plan->max_invoices_per_month;
    }

    public function canCreateClient(User $user): bool
    {
        $plan = $user->activePlan();
        if ($plan->isUnlimited('max_clients')) {
            return true;
        }

        return $user->clients()->count() < $plan->max_clients;
    }

    public function canCreateProduct(User $user): bool
    {
        $plan = $user->activePlan();
        if ($plan->isUnlimited('max_products')) {
            return true;
        }

        return $user->products()->count() < $plan->max_products;
    }

    public function canUseLogo(User $user): bool
    {
        return $user->activePlan()->custom_logo;
    }

    public function canUseTemplate(User $user, string $template): bool
    {
        if ($template === 'classic') {
            return true;
        }

        return $user->activePlan()->custom_templates;
    }

    public function canExport(User $user): bool
    {
        return $user->activePlan()->export_csv;
    }

    public function canUseRecurring(User $user): bool
    {
        return $user->activePlan()->recurring_invoices;
    }

    public function shouldShowWatermark(User $user): bool
    {
        return ! $user->activePlan()->remove_watermark;
    }

    public function getRemainingInvoices(User $user): ?int
    {
        $plan = $user->activePlan();
        if ($plan->isUnlimited('max_invoices_per_month')) {
            return null;
        }

        return max(0, $plan->max_invoices_per_month - $user->monthly_invoice_count);
    }

    public function getLimitsOverview(User $user): array
    {
        $plan = $user->activePlan();

        return [
            'plan' => [
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
            'invoices' => [
                'used' => $user->monthly_invoice_count,
                'limit' => $plan->max_invoices_per_month,
                'unlimited' => $plan->isUnlimited('max_invoices_per_month'),
            ],
            'clients' => [
                'used' => $user->clients()->count(),
                'limit' => $plan->max_clients,
                'unlimited' => $plan->isUnlimited('max_clients'),
            ],
            'products' => [
                'used' => $user->products()->count(),
                'limit' => $plan->max_products,
                'unlimited' => $plan->isUnlimited('max_products'),
            ],
            'features' => [
                'custom_logo' => $plan->custom_logo,
                'custom_templates' => $plan->custom_templates,
                'recurring_invoices' => $plan->recurring_invoices,
                'remove_watermark' => $plan->remove_watermark,
                'export_csv' => $plan->export_csv,
            ],
        ];
    }
}
