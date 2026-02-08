<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateRecurringInvoicesTest extends TestCase
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

    public function test_command_generates_due_invoices(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'monthly',
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
            'description' => 'Monthly Support',
            'quantity' => 1,
            'price' => 500,
        ]);

        $this->artisan('invoices:generate-recurring')
            ->expectsOutputToContain('Generated 1 invoices')
            ->assertSuccessful();

        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();
        $this->assertEquals($this->client->id, $invoice->client_id);
        $this->assertEquals($this->user->id, $invoice->user_id);
        $this->assertEquals('draft', $invoice->status->value);

        // Check items were copied
        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEquals('Monthly Support', $invoice->items()->first()->description);
    }

    public function test_command_advances_next_generate_date(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'monthly',
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $recurring->refresh();
        $this->assertEquals(
            now()->addMonth()->toDateString(),
            $recurring->next_generate_date->toDateString()
        );
        $this->assertEquals(1, $recurring->total_generated);
        $this->assertNotNull($recurring->last_generated_at);
    }

    public function test_command_skips_inactive_recurring(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'is_active' => false,
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_command_skips_future_recurring(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'next_generate_date' => now()->addDays(5)->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_command_skips_past_end_date(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'next_generate_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_command_deactivates_when_next_date_exceeds_end_date(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'monthly',
            'next_generate_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        // Invoice should be generated
        $this->assertDatabaseCount('invoices', 1);

        // But recurring should be deactivated since next month > end_date
        $recurring->refresh();
        $this->assertFalse($recurring->is_active);
    }

    public function test_command_increments_monthly_invoice_count(): void
    {
        $this->assertEquals(0, $this->user->monthly_invoice_count);

        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->user->refresh();
        $this->assertEquals(1, $this->user->monthly_invoice_count);
    }

    public function test_command_respects_plan_limits(): void
    {
        // Downgrade user's plan to free (no recurring invoices)
        $freePlan = Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_invoices_per_month' => 3,
                'max_clients' => 5,
                'max_products' => 10,
                'recurring_invoices' => false,
            ]
        );

        // Remove the pro subscription
        $this->user->subscription?->delete();
        $this->user->update(['plan_id' => $freePlan->id]);

        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')
            ->expectsOutputToContain('skipped 1')
            ->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
        // Recurring should be deactivated
        $this->assertFalse($recurring->fresh()->is_active);
    }

    public function test_weekly_frequency_advances_by_one_week(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'weekly',
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $recurring->refresh();
        $this->assertEquals(
            now()->addWeek()->toDateString(),
            $recurring->next_generate_date->toDateString()
        );
    }

    public function test_quarterly_frequency_advances_by_three_months(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'quarterly',
            'next_generate_date' => now()->toDateString(),
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $recurring->refresh();
        $this->assertEquals(
            now()->addMonths(3)->toDateString(),
            $recurring->next_generate_date->toDateString()
        );
    }

    public function test_generated_invoice_copies_currency_and_template(): void
    {
        $recurring = RecurringInvoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'frequency' => 'monthly',
            'next_generate_date' => now()->toDateString(),
            'currency' => 'EUR',
            'pdf_template' => 'modern',
            'tax_percent' => 20,
            'discount' => 50,
        ]);
        RecurringInvoiceItem::factory()->create([
            'recurring_invoice_id' => $recurring->id,
            'description' => 'Premium Service',
            'quantity' => 2,
            'price' => 300,
        ]);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $invoice = Invoice::first();
        $this->assertEquals('EUR', $invoice->currency);
        $this->assertEquals('modern', $invoice->pdf_template);
        $this->assertEquals(20, (float) $invoice->tax_percent);
        $this->assertEquals(50, (float) $invoice->discount);
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
