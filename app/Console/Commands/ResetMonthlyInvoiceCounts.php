<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyInvoiceCounts extends Command
{
    protected $signature = 'invoices:reset-monthly-counts';

    protected $description = 'Reset monthly invoice counts for all users';

    public function handle(): int
    {
        $count = User::where('monthly_invoice_count', '>', 0)->count();

        User::query()->update([
            'monthly_invoice_count' => 0,
            'invoice_count_reset_at' => now(),
        ]);

        $this->info("Reset monthly invoice counts for {$count} users.");

        return self::SUCCESS;
    }
}
