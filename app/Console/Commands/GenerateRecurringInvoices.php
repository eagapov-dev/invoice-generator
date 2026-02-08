<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\RecurringInvoice;
use App\Services\InvoiceNumberService;
use App\Services\PlanLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Generate invoices from active recurring invoice templates';

    public function __construct(
        private InvoiceNumberService $invoiceNumberService,
        private PlanLimitService $planLimitService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dueRecurring = RecurringInvoice::where('is_active', true)
            ->whereDate('next_generate_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->with(['user', 'client', 'items'])
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($dueRecurring as $recurring) {
            // Check if user can still use recurring invoices
            if (! $this->planLimitService->canUseRecurring($recurring->user)) {
                $recurring->update(['is_active' => false]);
                $skipped++;

                continue;
            }

            // Check if user can create more invoices this month
            if (! $this->planLimitService->canCreateInvoice($recurring->user)) {
                $skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($recurring) {
                    $invoice = $recurring->user->invoices()->create([
                        'invoice_number' => $this->invoiceNumberService->generate($recurring->user),
                        'client_id' => $recurring->client_id,
                        'tax_percent' => $recurring->tax_percent,
                        'discount' => $recurring->discount,
                        'due_date' => now()->addDays(30),
                        'notes' => $recurring->notes,
                        'status' => InvoiceStatus::Draft,
                        'currency' => $recurring->currency,
                        'pdf_template' => $recurring->pdf_template,
                    ]);

                    foreach ($recurring->items as $item) {
                        $invoice->items()->create([
                            'product_id' => $item->product_id,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->quantity * $item->price,
                        ]);
                    }

                    $invoice->calculateTotals();
                    $recurring->user->increment('monthly_invoice_count');

                    // Calculate next generate date
                    $nextDate = $recurring->getNextDate();

                    // If end_date is set and next date exceeds it, deactivate
                    if ($recurring->end_date && $nextDate && $nextDate->greaterThan($recurring->end_date)) {
                        $recurring->update([
                            'is_active' => false,
                            'total_generated' => $recurring->total_generated + 1,
                            'last_generated_at' => now(),
                            'next_generate_date' => $nextDate,
                        ]);
                    } else {
                        $recurring->update([
                            'total_generated' => $recurring->total_generated + 1,
                            'last_generated_at' => now(),
                            'next_generate_date' => $nextDate,
                        ]);
                    }
                });

                $generated++;
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for recurring #{$recurring->id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Generated {$generated} invoices, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
