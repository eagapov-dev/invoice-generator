<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_dashboard_stats(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        // Create invoices with different statuses
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Paid,
            'total' => 1000,
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Sent,
            'total' => 500,
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Overdue,
            'total' => 300,
        ]);

        // Create some products
        Product::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'total_invoices',
                    'paid_total',
                    'unpaid_total',
                    'overdue_total',
                    'total_clients',
                    'total_products',
                    'status_counts',
                ],
                'recent_invoices',
            ]);

        $stats = $response->json('stats');
        $this->assertEquals(3, $stats['total_invoices']);
        $this->assertEquals(1000, $stats['paid_total']);
        $this->assertEquals(800, $stats['unpaid_total']); // 500 + 300
        $this->assertEquals(300, $stats['overdue_total']);
        $this->assertEquals(1, $stats['total_clients']);
        $this->assertEquals(2, $stats['total_products']);
    }

    public function test_dashboard_only_shows_users_own_data(): void
    {
        // Create data for another user
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);
        Invoice::factory()->count(5)->create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id,
        ]);

        // Create data for our user
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertEquals(2, $response->json('stats.total_invoices'));
    }

    public function test_recent_invoices_returns_latest_five(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonCount(5, 'recent_invoices');
    }
}
