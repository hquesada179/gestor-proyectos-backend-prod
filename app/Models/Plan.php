<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'price_cop',
        'monthly_ai_credits',
        'daily_ai_limit',
        'minute_ai_limit',
        'max_users',
        'max_projects',
        'is_custom',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active'              => 'boolean',
            'is_custom'           => 'boolean',
            'monthly_price'       => 'decimal:2',
            'price_cop'           => 'integer',
            'monthly_ai_credits'  => 'integer',
            'daily_ai_limit'      => 'integer',
            'minute_ai_limit'     => 'integer',
            'max_users'           => 'integer',
            'max_projects'        => 'integer',
        ];
    }

    /** COP price in cents as required by Wompi's amount_in_cents field. */
    public function amountCopCents(): int
    {
        return $this->price_cop * 100;
    }

    /** Human-readable COP price string (e.g., "$39.900 COP"). */
    public function formattedCopPrice(): string
    {
        if ($this->price_cop === 0) {
            return '$0 COP';
        }

        return '$' . number_format($this->price_cop, 0, ',', '.') . ' COP';
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserAiCredit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isFree(): bool
    {
        return $this->monthly_price == 0 && !$this->is_custom;
    }
}
