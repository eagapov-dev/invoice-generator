<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetMonthlyInvoiceCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_resets_all_user_counts(): void
    {
        $user1 = User::factory()->create(['monthly_invoice_count' => 5]);
        $user2 = User::factory()->create(['monthly_invoice_count' => 10]);
        $user3 = User::factory()->create(['monthly_invoice_count' => 0]);

        $this->artisan('invoices:reset-monthly-counts')
            ->expectsOutputToContain('Reset monthly invoice counts for 2 users')
            ->assertExitCode(0);

        $this->assertEquals(0, $user1->fresh()->monthly_invoice_count);
        $this->assertEquals(0, $user2->fresh()->monthly_invoice_count);
        $this->assertEquals(0, $user3->fresh()->monthly_invoice_count);

        $this->assertNotNull($user1->fresh()->invoice_count_reset_at);
    }
}
