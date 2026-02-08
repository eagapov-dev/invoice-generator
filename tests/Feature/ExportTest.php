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

class ExportTest extends TestCase
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

    public function test_free_user_cannot_export_csv(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $response->assertStatus(403);
    }

    public function test_free_user_cannot_export_excel(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/excel');

        $response->assertStatus(403);
    }

    public function test_pro_user_can_export_csv(): void
    {
        $this->upgradeUserToPro($this->user);
        $this->createInvoice();

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('invoices-', $response->headers->get('Content-Disposition'));
    }

    public function test_pro_user_can_export_excel(): void
    {
        $this->upgradeUserToPro($this->user);
        $this->createInvoice();

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/excel');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
    }

    public function test_csv_contains_invoice_data(): void
    {
        $this->upgradeUserToPro($this->user);
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Invoice Number', $content);
        $this->assertStringContainsString($invoice->invoice_number, $content);
        $this->assertStringContainsString($this->client->name, $content);
    }

    public function test_csv_export_with_status_filter(): void
    {
        $this->upgradeUserToPro($this->user);
        $this->createInvoice(['status' => 'draft']);
        $paidInvoice = $this->createInvoice(['status' => 'paid']);

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv?status=paid');

        $content = $response->streamedContent();
        $this->assertStringContainsString($paidInvoice->invoice_number, $content);
    }

    public function test_csv_export_with_search_filter(): void
    {
        $this->upgradeUserToPro($this->user);
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv?search=' . urlencode($invoice->invoice_number));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString($invoice->invoice_number, $content);
    }

    public function test_csv_only_exports_own_invoices(): void
    {
        $this->upgradeUserToPro($this->user);
        $this->createInvoice();

        // Create another user's invoice
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);
        $otherInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id,
            'invoice_number' => 'OTHER-001',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $content = $response->streamedContent();
        $this->assertStringNotContainsString('OTHER-001', $content);
    }

    public function test_unauthenticated_cannot_export(): void
    {
        $response = $this->getJson('/api/export/invoices/csv');

        $response->assertStatus(401);
    }

    public function test_csv_export_empty_invoices(): void
    {
        $this->upgradeUserToPro($this->user);

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Invoice Number', $content);
    }

    public function test_csv_headers_are_correct(): void
    {
        $this->upgradeUserToPro($this->user);

        $response = $this->actingAs($this->user)
            ->get('/api/export/invoices/csv');

        $content = $response->streamedContent();
        $firstLine = strtok($content, "\n");
        $this->assertStringContainsString('Invoice Number', $firstLine);
        $this->assertStringContainsString('Client', $firstLine);
        $this->assertStringContainsString('Status', $firstLine);
        $this->assertStringContainsString('Currency', $firstLine);
        $this->assertStringContainsString('Total', $firstLine);
    }

    public function test_export_respects_plan_feature(): void
    {
        // Verify canExport returns false for free plan
        $planService = app(\App\Services\PlanLimitService::class);
        $this->assertFalse($planService->canExport($this->user));

        // Upgrade
        $this->upgradeUserToPro($this->user);
        $this->user->refresh();
        $this->assertTrue($planService->canExport($this->user));
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ], $overrides));

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service',
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
        ]);

        $invoice->calculateTotals();

        return $invoice;
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
