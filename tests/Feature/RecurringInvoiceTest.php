<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);
        $this->upgradeUserToPro($this->user);
    }

    public function test_free_user_cannot_create_recurring_invoice(): void
    {
        $freeUser = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $freeUser->id]);

        $response = $this->actingAs($freeUser)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $client->id,
                'frequency' => 'monthly',
                'start_date' => now()->toDateString(),
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_pro_user_can_create_recurring_invoice(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $this->client->id,
                'frequency' => 'monthly',
                'start_date' => now()->toDateString(),
                'items' => [
                    ['description' => 'Monthly Service', 'quantity' => 1, 'price' => 500],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.frequency', 'monthly')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.client.name', $this->client->name);

        $this->assertDatabaseCount('recurring_invoices', 1);
        $this->assertDatabaseCount('recurring_invoice_items', 1);
    }

    public function test_recurring_invoice_validates_frequency(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $this->client->id,
                'frequency' => 'invalid',
                'start_date' => now()->toDateString(),
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }

    public function test_recurring_invoice_requires_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $this->client->id,
                'frequency' => 'monthly',
                'start_date' => now()->toDateString(),
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_user_can_list_recurring_invoices(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/recurring-invoices');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_see_other_users_recurring_invoices(): void
    {
        $otherRecurring = RecurringInvoice::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/recurring-invoices');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_view_own_recurring_invoice(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/recurring-invoices/{$recurring->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $recurring->id);
    }

    public function test_user_cannot_view_other_users_recurring_invoice(): void
    {
        $otherRecurring = RecurringInvoice::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/recurring-invoices/{$otherRecurring->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_recurring_invoice(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'monthly',
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/recurring-invoices/{$recurring->id}", [
                'frequency' => 'weekly',
                'items' => [
                    ['description' => 'Updated Service', 'quantity' => 2, 'price' => 200],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.frequency', 'weekly');
    }

    public function test_user_can_delete_recurring_invoice(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/recurring-invoices/{$recurring->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('recurring_invoices', ['id' => $recurring->id]);
    }

    public function test_user_cannot_delete_other_users_recurring_invoice(): void
    {
        $otherRecurring = RecurringInvoice::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/recurring-invoices/{$otherRecurring->id}");

        $response->assertStatus(403);
    }

    public function test_toggle_active_pauses_recurring_invoice(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/recurring-invoices/{$recurring->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($recurring->fresh()->is_active);
    }

    public function test_toggle_active_resumes_recurring_invoice(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/recurring-invoices/{$recurring->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('is_active', true);
    }

    public function test_client_must_belong_to_user(): void
    {
        $otherClient = Client::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $otherClient->id,
                'frequency' => 'monthly',
                'start_date' => now()->toDateString(),
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_recurring_invoice_with_end_date(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/recurring-invoices', [
                'client_id' => $this->client->id,
                'frequency' => 'monthly',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.end_date', now()->addMonths(6)->toDateString());
    }

    public function test_all_frequencies_accepted(): void
    {
        $frequencies = ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'];

        foreach ($frequencies as $freq) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/recurring-invoices', [
                    'client_id' => $this->client->id,
                    'frequency' => $freq,
                    'start_date' => now()->toDateString(),
                    'items' => [
                        ['description' => "Service {$freq}", 'quantity' => 1, 'price' => 100],
                    ],
                ]);

            $response->assertStatus(201, "Failed for frequency: {$freq}");
        }

        $this->assertDatabaseCount('recurring_invoices', 5);
    }

    public function test_unauthenticated_cannot_access_recurring_invoices(): void
    {
        $response = $this->getJson('/api/recurring-invoices');

        $response->assertStatus(401);
    }

    private function upgradeUserToPro(User $user): void
    {
        $proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 5,
            'price_yearly' => 48,
            'max_invoices_per_month' => 50,
            'max_clients' => -1,
            'max_products' => -1,
            'custom_logo' => true,
            'custom_templates' => true,
            'recurring_invoices' => true,
            'remove_watermark' => true,
            'export_csv' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'payment_provider' => 'lemon-squeezy',
            'payment_provider_id' => 'test-sub-123',
            'current_period_start' => now()->subDays(10),
            'current_period_end' => now()->addDays(20),
        ]);
    }
}
