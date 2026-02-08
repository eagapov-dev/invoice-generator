<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LemonSqueezyWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payload = $request->all();
        $eventName = $payload['meta']['event_name'] ?? null;

        Log::info('Lemon Squeezy webhook received', ['event' => $eventName]);

        return match ($eventName) {
            'subscription_created' => $this->handleSubscriptionCreated($payload),
            'subscription_updated' => $this->handleSubscriptionUpdated($payload),
            'subscription_cancelled' => $this->handleSubscriptionCancelled($payload),
            'subscription_payment_success' => $this->handlePaymentSuccess($payload),
            'subscription_payment_failed' => $this->handlePaymentFailed($payload),
            default => response()->json(['message' => 'Event not handled.']),
        };
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('lemon-squeezy.signing_secret');

        if (! $secret) {
            return false;
        }

        $signature = $request->header('X-Signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedSignature, $signature);
    }

    private function handleSubscriptionCreated(array $payload): JsonResponse
    {
        $userId = $payload['meta']['custom_data']['user_id'] ?? null;
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            Log::warning('Lemon Squeezy webhook: user not found', ['user_id' => $userId]);

            return response()->json(['message' => 'User not found.'], 404);
        }

        $attributes = $payload['data']['attributes'] ?? [];
        $plan = $this->resolvePlanFromVariant($attributes['variant_id'] ?? null);

        if (! $plan) {
            Log::warning('Lemon Squeezy webhook: plan not found for variant', [
                'variant_id' => $attributes['variant_id'] ?? null,
            ]);

            return response()->json(['message' => 'Plan not found.'], 404);
        }

        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_id' => $plan->id,
                'status' => $this->mapStatus($attributes['status'] ?? 'active'),
                'payment_provider' => 'lemon_squeezy',
                'payment_provider_subscription_id' => (string) $payload['data']['id'],
                'current_period_start' => $attributes['renews_at'] ? now() : null,
                'current_period_end' => $attributes['renews_at'] ?? null,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
            ]
        );

        $user->update(['plan_id' => $plan->id]);

        Log::info('Subscription created', [
            'user_id' => $user->id,
            'plan' => $plan->slug,
            'subscription_id' => $subscription->id,
        ]);

        return response()->json(['message' => 'Subscription created.']);
    }

    private function handleSubscriptionUpdated(array $payload): JsonResponse
    {
        $subscription = $this->findSubscription($payload);

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        $attributes = $payload['data']['attributes'] ?? [];

        $plan = $this->resolvePlanFromVariant($attributes['variant_id'] ?? null);

        $updateData = [
            'status' => $this->mapStatus($attributes['status'] ?? $subscription->status),
            'current_period_end' => $attributes['renews_at'] ?? $subscription->current_period_end,
        ];

        if ($plan) {
            $updateData['plan_id'] = $plan->id;
            $subscription->user->update(['plan_id' => $plan->id]);
        }

        $subscription->update($updateData);

        Log::info('Subscription updated', [
            'subscription_id' => $subscription->id,
            'status' => $updateData['status'],
        ]);

        return response()->json(['message' => 'Subscription updated.']);
    }

    private function handleSubscriptionCancelled(array $payload): JsonResponse
    {
        $subscription = $this->findSubscription($payload);

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        $attributes = $payload['data']['attributes'] ?? [];

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'current_period_end' => $attributes['ends_at'] ?? $subscription->current_period_end,
        ]);

        // Don't change plan_id yet — user is on grace period until current_period_end

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'grace_period_until' => $subscription->current_period_end,
        ]);

        return response()->json(['message' => 'Subscription cancelled.']);
    }

    private function handlePaymentSuccess(array $payload): JsonResponse
    {
        $subscription = $this->findSubscription($payload);

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        $attributes = $payload['data']['attributes'] ?? [];

        $subscription->update([
            'status' => 'active',
            'current_period_end' => $attributes['renews_at'] ?? $subscription->current_period_end,
        ]);

        Log::info('Payment success', ['subscription_id' => $subscription->id]);

        return response()->json(['message' => 'Payment recorded.']);
    }

    private function handlePaymentFailed(array $payload): JsonResponse
    {
        $subscription = $this->findSubscription($payload);

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        $subscription->update(['status' => 'past_due']);

        Log::info('Payment failed', ['subscription_id' => $subscription->id]);

        return response()->json(['message' => 'Payment failure recorded.']);
    }

    private function findSubscription(array $payload): ?Subscription
    {
        $lsSubscriptionId = (string) ($payload['data']['id'] ?? '');

        return Subscription::where('payment_provider_subscription_id', $lsSubscriptionId)->first();
    }

    private function resolvePlanFromVariant(?int $variantId): ?Plan
    {
        if (! $variantId) {
            return null;
        }

        $variants = config('lemon-squeezy.variants', []);

        foreach ($variants as $slug => $periods) {
            foreach ($periods as $varId) {
                if ((int) $varId === $variantId) {
                    return Plan::where('slug', $slug)->first();
                }
            }
        }

        return null;
    }

    private function mapStatus(string $lsStatus): string
    {
        return match ($lsStatus) {
            'active', 'on_trial' => 'active',
            'cancelled' => 'canceled',
            'past_due' => 'past_due',
            'paused' => 'canceled',
            default => 'active',
        };
    }
}
