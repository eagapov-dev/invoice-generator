<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'frequency',
        'start_date',
        'next_generate_date',
        'end_date',
        'is_active',
        'tax_percent',
        'discount',
        'currency',
        'pdf_template',
        'notes',
        'total_generated',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'next_generate_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'tax_percent' => 'decimal:2',
            'discount' => 'decimal:2',
            'last_generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function getNextDate(): ?\Carbon\Carbon
    {
        $date = $this->next_generate_date->copy();

        return match ($this->frequency) {
            'weekly' => $date->addWeek(),
            'biweekly' => $date->addWeeks(2),
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'yearly' => $date->addYear(),
            default => null,
        };
    }
}
