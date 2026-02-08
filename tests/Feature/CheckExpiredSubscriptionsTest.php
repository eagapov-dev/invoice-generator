<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckExpiredSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_canceled_subscriptions_are_downgraded(): void
    {
        $freePlan = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'price_yearly' => 0,
            'max_invoices_per_month' => 3, 'max_clients' => 5, 'max_products' => 10,
        ]);

        $proPlan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 5, 'price_yearly' => 48,
            'max_invoices_per_month' => 50, 'max_clients' => -1, 'max_products' => -1,
        ]);

        // Expired subscription (past grace period)
        $expiredUser = User::factory()->create(['plan_id' => $proPlan->id]);
        Subscription::create([
            'user_id' => $expiredUser->id,
            'plan_id' => $proPlan->id,
            'status' => 'canceled',
            'current_period_end' => now()->subDay(),
            'canceled_at' => now()->subWeek(),
        ]);

        // Still on grace period
        $gracePeriodUser = User::factory()->create(['plan_id' => $proPlan->id]);
        Subscription::create([
            'user_id' => $gracePeriodUser->id,
            'plan_id' => $proPlan->id,
            'status' => 'canceled',
            'current_period_end' => now()->addWeek(),
            'canceled_at' => now()->subDay(),
        ]);

        // Active subscription
        $activeUser = User::factory()->create(['plan_id' => $proPlan->id]);
        Subscription::create([
            'user_id' => $activeUser->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->artisan('subscriptions:check-expired')
            ->expectsOutputToContain('Downgraded 1 users')
            ->assertExitCode(0);

        // Expired user should be downgraded
        $this->assertEquals($freePlan->id, $expiredUser->fresh()->plan_id);

        // Grace period user should NOT be downgraded
        $this->assertEquals($proPlan->id, $gracePeriodUser->fresh()->plan_id);

        // Active user should NOT be downgraded
        $this->assertEquals($proPlan->id, $activeUser->fresh()->plan_id);
    }
}
