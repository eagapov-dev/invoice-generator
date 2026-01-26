<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_user_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        Invoice::factory()->count(2)->create(); // Other user's invoices

        $response = $this->actingAs($this->user)
            ->getJson('/api/invoices');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_invoice(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'tax_percent' => 10,
                'discount' => 50,
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'notes' => 'Thank you for your business',
                'items' => [
                    [
                        'description' => 'Web Development',
                        'quantity' => 10,
                        'price' => 100,
                    ],
                    [
                        'description' => 'Design Work',
                        'quantity' => 5,
                        'price' => 80,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'client_id',
                    'subtotal',
                    'tax_percent',
                    'discount',
                    'total',
                    'status',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertDatabaseCount('invoice_items', 2);
    }

    public function test_invoice_number_is_auto_generated(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertMatchesRegularExpression('/^INV-\d{4}$/', $response->json('data.invoice_number'));
    }

    public function test_invoice_totals_are_calculated_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'tax_percent' => 10,
                'discount' => 50,
                'items' => [
                    ['description' => 'Item 1', 'quantity' => 2, 'price' => 100], // 200
                    ['description' => 'Item 2', 'quantity' => 3, 'price' => 50],  // 150
                ],
            ]);

        $response->assertStatus(201);

        // Subtotal: 200 + 150 = 350
        // Tax: 350 * 0.10 = 35
        // Total: 350 + 35 - 50 = 335
        $this->assertEquals(350, $response->json('data.subtotal'));
        $this->assertEquals(335, $response->json('data.total'));
    }

    public function test_user_can_view_own_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id);
    }

    public function test_user_cannot_view_other_users_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/invoices/{$invoice->id}", [
                'tax_percent' => 15,
                'notes' => 'Updated notes',
                'items' => [
                    ['description' => 'New Item', 'quantity' => 1, 'price' => 200],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.tax_percent', 15)
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_user_can_update_invoice_status(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/invoices/{$invoice->id}/status", [
                'status' => 'paid',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_user_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_user_can_filter_invoices_by_status(): void
    {
        Invoice::factory()->draft()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        Invoice::factory()->paid()->count(2)->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/invoices?status=paid');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_invoice_requires_at_least_one_item(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_invoice_client_must_belong_to_user(): void
    {
        $otherClient = Client::factory()->create(); // Different user's client

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $otherClient->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_user_can_get_pdf_url(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $response->assertOk()
            ->assertJsonStructure(['url']);

        $this->assertStringContainsString('signature=', $response->json('url'));
    }
}
