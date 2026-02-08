<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitTest extends TestCase
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
        ]);

        $this->proPlan = Plan::create([
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
        ]);

        $this->businessPlan = Plan::create([
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
        ]);

        $this->user = User::factory()->create(['plan_id' => $this->freePlan->id]);
    }

    public function test_free_user_can_create_invoices_within_limit(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_free_user_cannot_exceed_invoice_limit(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $this->user->update(['monthly_invoice_count' => 3]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'plan_limit_exceeded')
            ->assertJsonPath('resource', 'invoices')
            ->assertJsonPath('limit', 3);
    }

    public function test_free_user_cannot_exceed_client_limit(): void
    {
        Client::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/clients', [
                'name' => 'New Client',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'plan_limit_exceeded')
            ->assertJsonPath('resource', 'clients');
    }

    public function test_free_user_cannot_exceed_product_limit(): void
    {
        Product::factory()->count(10)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'New Product',
                'price' => 100,
                'unit' => 'hour',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'plan_limit_exceeded')
            ->assertJsonPath('resource', 'products');
    }

    public function test_pro_user_has_unlimited_clients(): void
    {
        $this->user->update(['plan_id' => $this->proPlan->id]);
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->proPlan->id,
            'status' => 'active',
        ]);

        Client::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/clients', [
                'name' => 'Another Client',
                'email' => 'another@example.com',
            ]);

        $response->assertStatus(201);
    }

    public function test_invoice_count_increments_on_creation(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->assertEquals(0, $this->user->monthly_invoice_count);

        $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $this->assertEquals(1, $this->user->fresh()->monthly_invoice_count);
    }

    public function test_plans_endpoint_returns_active_plans(): void
    {
        $response = $this->getJson('/api/plans');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'free')
            ->assertJsonPath('data.1.slug', 'pro')
            ->assertJsonPath('data.2.slug', 'business');
    }

    public function test_user_limits_endpoint_returns_current_usage(): void
    {
        Client::factory()->count(2)->create(['user_id' => $this->user->id]);
        $this->user->update(['monthly_invoice_count' => 1]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/limits');

        $response->assertOk()
            ->assertJsonPath('data.plan.slug', 'free')
            ->assertJsonPath('data.invoices.used', 1)
            ->assertJsonPath('data.invoices.limit', 3)
            ->assertJsonPath('data.clients.used', 2)
            ->assertJsonPath('data.clients.limit', 5);
    }

    public function test_user_subscription_endpoint_returns_free_for_no_subscription(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/subscription');

        $response->assertOk()
            ->assertJsonPath('data.status', 'free')
            ->assertJsonPath('data.plan.slug', 'free');
    }

    public function test_user_resource_includes_plan_and_limits(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'plan' => ['name', 'slug'],
                    'limits' => [
                        'plan',
                        'invoices',
                        'clients',
                        'products',
                        'features',
                    ],
                ],
            ]);
    }

    public function test_limit_response_includes_upgrade_url(): void
    {
        $this->user->update(['monthly_invoice_count' => 3]);
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('upgrade_url', '/pricing');
    }
}
