<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_id',
        'provider',
        'provider_payment_id',
        'reference',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'raw_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'paid_at'     => 'datetime',
            'amount'      => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
