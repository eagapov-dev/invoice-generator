<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
        'max_invoices_per_month',
        'max_clients',
        'max_products',
        'custom_logo',
        'custom_templates',
        'recurring_invoices',
        'remove_watermark',
        'export_csv',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'custom_logo' => 'boolean',
            'custom_templates' => 'boolean',
            'recurring_invoices' => 'boolean',
            'remove_watermark' => 'boolean',
            'export_csv' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isUnlimited(string $field): bool
    {
        return $this->$field === -1;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
