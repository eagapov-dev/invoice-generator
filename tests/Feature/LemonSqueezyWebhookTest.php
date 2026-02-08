<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LemonSqueezyWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $freePlan;

    private Plan $proPlan;

    private string $signingSecret = 'test-signing-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['lemon-squeezy.signing_secret' => $this->signingSecret]);
        config(['lemon-squeezy.variants.pro.monthly' => 111]);
        config(['lemon-squeezy.variants.pro.yearly' => 112]);
        config(['lemon-squeezy.variants.business.monthly' => 221]);

        $this->freePlan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'price_yearly' => 0,
            'max_invoices_per_month' => 3, 'max_clients' => 5, 'max_products' => 10,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 5, 'price_yearly' => 48,
            'max_invoices_per_month' => 50, 'max_clients' => -1, 'max_products' => -1,
            'custom_logo' => true, 'custom_templates' => true, 'remove_watermark' => true, 'export_csv' => true,
        ]);

        $this->user = User::factory()->create(['plan_id' => $this->freePlan->id]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/lemon-squeezy', [
            'meta' => ['event_name' => 'subscription_created'],
        ], ['X-Signature' => 'invalid']);

        $response->assertStatus(403);
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/webhooks/lemon-squeezy', [
            'meta' => ['event_name' => 'subscription_created'],
        ]);

        $response->assertStatus(403);
    }

    public function test_subscription_created_webhook(): void
    {
        $payload = $this->makePayload('subscription_created', [
            'variant_id' => 111,
            'status' => 'active',
            'renews_at' => now()->addMonth()->toISOString(),
        ]);

        $response = $this->postJson(
            '/api/webhooks/lemon-squeezy',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        );

        $response->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'payment_provider' => 'lemon_squeezy',
            'payment_provider_subscription_id' => '99999',
        ]);

        $this->assertEquals($this->proPlan->id, $this->user->fresh()->plan_id);
    }

    public function test_subscription_cancelled_webhook(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'payment_provider' => 'lemon_squeezy',
            'payment_provider_subscription_id' => '99999',
            'current_period_end' => now()->addMonth(),
        ]);
        $this->user->update(['plan_id' => $this->proPlan->id]);

        $payload = $this->makePayload('subscription_cancelled', [
            'variant_id' => 111,
            'status' => 'cancelled',
            'ends_at' => now()->addMonth()->toISOString(),
        ]);

        $response = $this->postJson(
            '/api/webhooks/lemon-squeezy',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        );

        $response->assertOk();

        $subscription->refresh();
        $this->assertEquals('canceled', $subscription->status);
        $this->assertNotNull($subscription->canceled_at);

        // User should still have pro plan (grace period)
        $this->assertEquals($this->proPlan->id, $this->user->fresh()->plan_id);
    }

    public function test_subscription_payment_failed_webhook(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'payment_provider' => 'lemon_squeezy',
            'payment_provider_subscription_id' => '99999',
        ]);

        $payload = $this->makePayload('subscription_payment_failed', [
            'variant_id' => 111,
            'status' => 'past_due',
        ]);

        $response = $this->postJson(
            '/api/webhooks/lemon-squeezy',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        );

        $response->assertOk();
        $this->assertDatabaseHas('subscriptions', [
            'payment_provider_subscription_id' => '99999',
            'status' => 'past_due',
        ]);
    }

    public function test_subscription_payment_success_webhook(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'past_due',
            'payment_provider' => 'lemon_squeezy',
            'payment_provider_subscription_id' => '99999',
        ]);

        $renewsAt = now()->addMonth()->toISOString();
        $payload = $this->makePayload('subscription_payment_success', [
            'variant_id' => 111,
            'status' => 'active',
            'renews_at' => $renewsAt,
        ]);

        $response = $this->postJson(
            '/api/webhooks/lemon-squeezy',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        );

        $response->assertOk();
        $this->assertDatabaseHas('subscriptions', [
            'payment_provider_subscription_id' => '99999',
            'status' => 'active',
        ]);
    }

    public function test_subscription_created_with_unknown_user_returns_404(): void
    {
        $payload = [
            'meta' => [
                'event_name' => 'subscription_created',
                'custom_data' => ['user_id' => '999999'],
            ],
            'data' => [
                'id' => '99999',
                'attributes' => [
                    'variant_id' => 111,
                    'status' => 'active',
                    'renews_at' => now()->addMonth()->toISOString(),
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/webhooks/lemon-squeezy',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        );

        $response->assertStatus(404);
    }

    private function makePayload(string $event, array $attributes): array
    {
        return [
            'meta' => [
                'event_name' => $event,
                'custom_data' => ['user_id' => (string) $this->user->id],
            ],
            'data' => [
                'id' => '99999',
                'attributes' => $attributes,
            ],
        ];
    }

    private function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->signingSecret);
    }
}
