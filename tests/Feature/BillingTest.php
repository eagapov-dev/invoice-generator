<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $freePlan;

    private Plan $proPlan;

    private Plan $businessPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freePlan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'price_yearly' => 0,
            'max_invoices_per_month' => 3, 'max_clients' => 5, 'max_products' => 10,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 5, 'price_yearly' => 48,
            'max_invoices_per_month' => 50, 'max_clients' => -1, 'max_products' => -1,
            'custom_logo' => true, 'custom_templates' => true, 'remove_watermark' => true, 'export_csv' => true,
        ]);

        $this->businessPlan = Plan::create([
            'name' => 'Business', 'slug' => 'business', 'price_monthly' => 12, 'price_yearly' => 120,
            'max_invoices_per_month' => -1, 'max_clients' => -1, 'max_products' => -1,
            'custom_logo' => true, 'custom_templates' => true, 'recurring_invoices' => true,
            'remove_watermark' => true, 'export_csv' => true,
        ]);

        $this->user = User::factory()->create(['plan_id' => $this->freePlan->id]);
    }

    public function test_billing_status_returns_free_for_new_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/billing/status');

        $response->assertOk()
            ->assertJsonPath('data.plan.slug', 'free')
            ->assertJsonPath('data.subscription', null);
    }

    public function test_billing_status_returns_active_subscription(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
            'payment_provider' => 'lemon_squeezy',
            'payment_provider_subscription_id' => '12345',
            'current_period_end' => now()->addMonth(),
        ]);
        $this->user->update(['plan_id' => $this->proPlan->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/billing/status');

        $response->assertOk()
            ->assertJsonPath('data.plan.slug', 'pro')
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.on_grace_period', false);
    }

    public function test_checkout_validates_plan_slug(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/billing/checkout', [
                'plan_slug' => 'invalid',
                'billing_period' => 'monthly',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_slug']);
    }

    public function test_checkout_validates_billing_period(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/billing/checkout', [
                'plan_slug' => 'pro',
                'billing_period' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['billing_period']);
    }

    public function test_checkout_returns_503_when_not_configured(): void
    {
        config(['lemon-squeezy.variants.pro.monthly' => 'variant-123']);
        config(['lemon-squeezy.store_id' => null]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/billing/checkout', [
                'plan_slug' => 'pro',
                'billing_period' => 'monthly',
            ]);

        $response->assertStatus(503);
    }

    public function test_checkout_creates_session_via_api(): void
    {
        config([
            'lemon-squeezy.api_key' => 'test-key',
            'lemon-squeezy.store_id' => '12345',
            'lemon-squeezy.variants.pro.monthly' => '67890',
        ]);

        Http::fake([
            'api.lemonsqueezy.com/*' => Http::response([
                'data' => [
                    'attributes' => [
                        'url' => 'https://checkout.lemonsqueezy.com/test-session',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/billing/checkout', [
                'plan_slug' => 'pro',
                'billing_period' => 'monthly',
            ]);

        $response->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.lemonsqueezy.com/test-session');
    }

    public function test_portal_returns_404_without_subscription(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/billing/portal');

        $response->assertStatus(404);
    }
}
