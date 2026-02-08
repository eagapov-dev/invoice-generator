<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'plan_id',
        'monthly_invoice_count',
        'invoice_count_reset_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'invoice_count_reset_at' => 'datetime',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function companySettings(): HasOne
    {
        return $this->hasOne(CompanySettings::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function activePlan(): Plan
    {
        $subscription = $this->subscription;

        if ($subscription && $subscription->isActive()) {
            return $subscription->plan;
        }

        if ($this->plan) {
            return $this->plan;
        }

        return Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_invoices_per_month' => 3,
                'max_clients' => 5,
                'max_products' => 10,
                'custom_logo' => false,
                'custom_templates' => false,
                'recurring_invoices' => false,
                'remove_watermark' => false,
                'export_csv' => false,
            ]
        );
    }

    public function hasActivePaidSubscription(): bool
    {
        $subscription = $this->subscription;

        return $subscription
            && $subscription->isActive()
            && $subscription->plan->slug !== 'free';
    }
}
