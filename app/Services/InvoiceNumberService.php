<?php

namespace App\Services;

use App\Models\User;

class InvoiceNumberService
{
    public function generate(User $user): string
    {
        $query = $user->invoices()
            ->withTrashed()
            ->where('invoice_number', 'like', 'INV-%');

        $driver = $query->getQuery()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $maxNumber = (int) $query
                ->selectRaw("MAX(CAST(SUBSTR(invoice_number, 5) AS INTEGER)) as max_num")
                ->value('max_num');
        } else {
            $maxNumber = (int) $query
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(SUBSTRING(invoice_number FROM 5) AS INTEGER)) as max_num")
                ->value('max_num');
        }

        return 'INV-'.str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
