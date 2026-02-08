<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';

    protected $description = 'Downgrade users with expired canceled subscriptions to the free plan';

    public function handle(): int
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (! $freePlan) {
            $this->error('Free plan not found.');

            return self::FAILURE;
        }

        $expired = Subscription::where('status', 'canceled')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->with('user')
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            if ($subscription->user) {
                $subscription->user->update(['plan_id' => $freePlan->id]);
                $count++;
            }

            $subscription->update(['status' => 'expired']);
        }

        $this->info("Downgraded {$count} users with expired subscriptions.");

        return self::SUCCESS;
    }
}
