<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInvoiceTest extends TestCase
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

    public function test_invoice_gets_public_token_on_creation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.public_token'));
        $this->assertNotNull($response->json('data.public_url'));
    }

    public function test_public_invoice_view_via_token(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'test-token-12345',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Web Development',
            'quantity' => 10,
            'price' => 100,
            'total' => 1000,
        ]);
        $invoice->calculateTotals();

        $response = $this->getJson('/api/p/test-token-12345');

        $response->assertOk()
            ->assertJsonPath('data.invoice_number', $invoice->invoice_number)
            ->assertJsonPath('data.client.name', $this->client->name)
            ->assertJsonStructure([
                'data' => [
                    'invoice_number',
                    'status',
                    'currency',
                    'subtotal',
                    'total',
                    'company',
                    'client',
                    'items',
                ],
            ]);
    }

    public function test_public_invoice_includes_company_info(): void
    {
        CompanySettings::create([
            'user_id' => $this->user->id,
            'company_name' => 'Test Corp',
            'email' => 'corp@test.com',
            'bank_details' => 'IBAN: GB123456',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'company-test-token',
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->getJson('/api/p/company-test-token');

        $response->assertOk()
            ->assertJsonPath('data.company.name', 'Test Corp')
            ->assertJsonPath('data.company.email', 'corp@test.com')
            ->assertJsonPath('data.company.bank_details', 'IBAN: GB123456');
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->getJson('/api/p/nonexistent-token');

        $response->assertStatus(404);
    }

    public function test_public_pdf_download_via_token(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'pdf-test-token',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'PDF Service',
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);
        $invoice->calculateTotals();

        $response = $this->get('/api/p/pdf-test-token/pdf');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_public_pdf_with_invalid_token_returns_404(): void
    {
        $response = $this->get('/api/p/fake-token/pdf');

        $response->assertStatus(404);
    }

    public function test_toggle_share_enables_public_link(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        // Manually disable sharing first
        $invoice->update(['public_token' => null]);
        $this->assertNull($invoice->fresh()->public_token);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/invoices/{$invoice->id}/share");

        $response->assertOk()
            ->assertJsonPath('shared', true);
        $this->assertNotNull($response->json('public_url'));

        $invoice->refresh();
        $this->assertNotNull($invoice->public_token);
    }

    public function test_toggle_share_disables_public_link(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'existing-token',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/invoices/{$invoice->id}/share");

        $response->assertOk()
            ->assertJsonPath('shared', false)
            ->assertJsonPath('public_url', null);

        $invoice->refresh();
        $this->assertNull($invoice->public_token);
    }

    public function test_disabled_public_link_returns_404(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'soon-disabled',
        ]);

        // Verify it works first
        $this->getJson('/api/p/soon-disabled')->assertOk();

        // Disable sharing
        $invoice->update(['public_token' => null]);

        // Now it should 404
        $this->getJson('/api/p/soon-disabled')->assertStatus(404);
    }

    public function test_cannot_toggle_share_on_other_users_invoice(): void
    {
        $otherInvoice = Invoice::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/invoices/{$otherInvoice->id}/share");

        $response->assertStatus(403);
    }

    public function test_public_url_in_invoice_resource(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'public_token' => 'resource-test-token',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.public_token', 'resource-test-token');
        $this->assertStringContainsString('/p/resource-test-token', $response->json('data.public_url'));
    }

    public function test_null_token_gives_null_public_url(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        // Manually disable sharing
        $invoice->update(['public_token' => null]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.public_token', null)
            ->assertJsonPath('data.public_url', null);
    }
}
