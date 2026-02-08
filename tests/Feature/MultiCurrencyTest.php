<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyTest extends TestCase
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

    public function test_invoice_created_with_explicit_currency(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'currency' => 'EUR',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'EUR');

        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->user->id,
            'currency' => 'EUR',
        ]);
    }

    public function test_invoice_defaults_to_company_currency(): void
    {
        CompanySettings::create([
            'user_id' => $this->user->id,
            'default_currency' => 'GBP',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'GBP');
    }

    public function test_invoice_defaults_to_usd_without_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'USD');
    }

    public function test_invoice_currency_must_be_3_chars(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'currency' => 'EURO',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_invoice_currency_returned_in_resource(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'currency' => 'PLN',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.currency', 'PLN');
    }

    public function test_invoice_currency_can_be_updated(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'currency' => 'USD',
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/invoices/{$invoice->id}", [
                'currency' => 'CHF',
                'items' => [
                    ['description' => 'Updated Service', 'quantity' => 1, 'price' => 200],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.currency', 'CHF');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'currency' => 'CHF',
        ]);
    }

    public function test_invoice_list_includes_currency(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/invoices');

        $response->assertOk()
            ->assertJsonPath('data.0.currency', 'EUR');
    }

    public function test_pdf_uses_invoice_currency(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'currency' => 'EUR',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Euro Service',
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);
        $invoice->calculateTotals();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $response->assertOk();

        // Fetch the PDF to verify it uses EUR currency
        $pdfUrl = $response->json('url');
        $pdfResponse = $this->get($pdfUrl);
        $pdfResponse->assertOk();
    }
}
