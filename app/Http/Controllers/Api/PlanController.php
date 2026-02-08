<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private PlanLimitService $planLimitService) {}

    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        return response()->json([
            'data' => $plans->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'max_invoices_per_month' => $plan->max_invoices_per_month,
                'max_clients' => $plan->max_clients,
                'max_products' => $plan->max_products,
                'features' => [
                    'custom_logo' => $plan->custom_logo,
                    'custom_templates' => $plan->custom_templates,
                    'recurring_invoices' => $plan->recurring_invoices,
                    'remove_watermark' => $plan->remove_watermark,
                    'export_csv' => $plan->export_csv,
                ],
            ]),
        ]);
    }

    public function limits(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->planLimitService->getLimitsOverview($request->user()),
        ]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        if (! $subscription) {
            return response()->json([
                'data' => [
                    'plan' => [
                        'name' => $user->activePlan()->name,
                        'slug' => $user->activePlan()->slug,
                    ],
                    'status' => 'free',
                    'current_period_end' => null,
                    'canceled_at' => null,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'plan' => [
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                ],
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start?->toISOString(),
                'current_period_end' => $subscription->current_period_end?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'canceled_at' => $subscription->canceled_at?->toISOString(),
                'on_grace_period' => $subscription->isOnGracePeriod(),
            ],
        ]);
    }
}
