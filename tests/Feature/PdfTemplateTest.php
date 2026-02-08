<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfTemplateTest extends TestCase
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

    public function test_invoice_created_with_classic_template_by_default(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pdf_template', 'classic');
    }

    public function test_invoice_created_with_explicit_template(): void
    {
        $this->upgradeUserToPro($this->user);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'pdf_template' => 'modern',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pdf_template', 'modern');
    }

    public function test_free_user_cannot_use_premium_template(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'pdf_template' => 'modern',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pdf_template', 'classic');
    }

    public function test_pro_user_can_use_modern_template(): void
    {
        $this->upgradeUserToPro($this->user);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'pdf_template' => 'modern',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pdf_template', 'modern');
    }

    public function test_pro_user_can_use_minimal_template(): void
    {
        $this->upgradeUserToPro($this->user);

        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'pdf_template' => 'minimal',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pdf_template', 'minimal');
    }

    public function test_invalid_template_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/invoices', [
                'client_id' => $this->client->id,
                'pdf_template' => 'nonexistent',
                'items' => [
                    ['description' => 'Service', 'quantity' => 1, 'price' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pdf_template']);
    }

    public function test_template_returned_in_resource(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'pdf_template' => 'modern',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.pdf_template', 'modern');
    }

    public function test_template_can_be_updated(): void
    {
        $this->upgradeUserToPro($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'pdf_template' => 'classic',
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/invoices/{$invoice->id}", [
                'pdf_template' => 'minimal',
                'items' => [
                    ['description' => 'Updated', 'quantity' => 1, 'price' => 200],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.pdf_template', 'minimal');
    }

    public function test_free_user_pdf_has_watermark(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service',
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);
        $invoice->calculateTotals();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $response->assertOk();

        $pdfUrl = $response->json('url');
        $pdfResponse = $this->get($pdfUrl);
        $pdfResponse->assertOk();
    }

    public function test_pro_user_pdf_no_watermark(): void
    {
        $this->upgradeUserToPro($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service',
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);
        $invoice->calculateTotals();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $response->assertOk();

        $pdfUrl = $response->json('url');
        $pdfResponse = $this->get($pdfUrl);
        $pdfResponse->assertOk();
    }

    public function test_modern_template_pdf_renders(): void
    {
        $this->upgradeUserToPro($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'pdf_template' => 'modern',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Modern Service',
            'quantity' => 2,
            'price' => 150,
            'total' => 300,
        ]);
        $invoice->calculateTotals();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $pdfUrl = $response->json('url');
        $pdfResponse = $this->get($pdfUrl);
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_minimal_template_pdf_renders(): void
    {
        $this->upgradeUserToPro($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'pdf_template' => 'minimal',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Minimal Service',
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);
        $invoice->calculateTotals();

        $response = $this->actingAs($this->user)
            ->getJson("/api/invoices/{$invoice->id}/pdf-url");

        $pdfUrl = $response->json('url');
        $pdfResponse = $this->get($pdfUrl);
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
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
