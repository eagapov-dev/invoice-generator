<?php

namespace App\Policies;

use App\Models\RecurringInvoice;
use App\Models\User;

class RecurringInvoicePolicy
{
    public function view(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->id === $recurringInvoice->user_id;
    }

    public function update(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->id === $recurringInvoice->user_id;
    }

    public function delete(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->id === $recurringInvoice->user_id;
    }
}
