<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan_slug' => ['required', Rule::in(['pro', 'business'])],
            'billing_period' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $variantId = config("lemon-squeezy.variants.{$request->plan_slug}.{$request->billing_period}");

        if (! $variantId) {
            return response()->json([
                'message' => 'Invalid plan or billing period.',
            ], 422);
        }

        $storeId = config('lemon-squeezy.store_id');
        $apiKey = config('lemon-squeezy.api_key');

        if (! $storeId || ! $apiKey) {
            return response()->json([
                'message' => 'Payment system is not configured.',
            ], 503);
        }

        $user = $request->user();

        $response = Http::withToken($apiKey)
            ->post('https://api.lemonsqueezy.com/v1/checkouts', [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'checkout_data' => [
                            'email' => $user->email,
                            'name' => $user->name,
                            'custom' => [
                                'user_id' => (string) $user->id,
                            ],
                        ],
                        'product_options' => [
                            'redirect_url' => url('/billing?checkout=success'),
                        ],
                    ],
                    'relationships' => [
                        'store' => [
                            'data' => [
                                'type' => 'stores',
                                'id' => (string) $storeId,
                            ],
                        ],
                        'variant' => [
                            'data' => [
                                'type' => 'variants',
                                'id' => (string) $variantId,
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to create checkout session.',
            ], 502);
        }

        $checkoutUrl = $response->json('data.attributes.url');

        return response()->json([
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        if (! $subscription || ! $subscription->payment_provider_subscription_id) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 404);
        }

        $apiKey = config('lemon-squeezy.api_key');

        if (! $apiKey) {
            return response()->json([
                'message' => 'Payment system is not configured.',
            ], 503);
        }

        $response = Http::withToken($apiKey)
            ->get("https://api.lemonsqueezy.com/v1/subscriptions/{$subscription->payment_provider_subscription_id}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to retrieve subscription details.',
            ], 502);
        }

        $urls = $response->json('data.attributes.urls');

        return response()->json([
            'portal_url' => $urls['customer_portal'] ?? null,
            'update_payment_method_url' => $urls['update_payment_method'] ?? null,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;
        $plan = $user->activePlan();

        return response()->json([
            'data' => [
                'plan' => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price_monthly' => (float) $plan->price_monthly,
                    'price_yearly' => (float) $plan->price_yearly,
                ],
                'subscription' => $subscription ? [
                    'status' => $subscription->status,
                    'payment_provider' => $subscription->payment_provider,
                    'current_period_start' => $subscription->current_period_start?->toISOString(),
                    'current_period_end' => $subscription->current_period_end?->toISOString(),
                    'canceled_at' => $subscription->canceled_at?->toISOString(),
                    'on_grace_period' => $subscription->isOnGracePeriod(),
                ] : null,
            ],
        ]);
    }
}
