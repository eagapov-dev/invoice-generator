<?php

namespace App\Services;

use App\Models\User;

class InvoiceNumberService
{
    public function generate(User $user): string
    {
        $lastInvoice = $user->invoices()
            ->where('invoice_number', 'like', 'INV-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the number part after "INV-"
            $lastNumber = (int) substr($lastInvoice->invoice_number, 4);

            // Find the highest number by checking all invoices
            $maxNumber = $user->invoices()
                ->where('invoice_number', 'like', 'INV-%')
                ->get()
                ->map(fn($inv) => (int) substr($inv->invoice_number, 4))
                ->max();

            $nextNumber = $maxNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'INV-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
