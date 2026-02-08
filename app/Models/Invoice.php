<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (! $invoice->public_token) {
                $invoice->public_token = Str::random(32);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_number',
        'subtotal',
        'tax_percent',
        'discount',
        'total',
        'status',
        'due_date',
        'notes',
        'currency',
        'pdf_template',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $taxAmount = $this->subtotal * ($this->tax_percent / 100);
        $this->total = $this->subtotal + $taxAmount - $this->discount;
        $this->save();
    }
}
