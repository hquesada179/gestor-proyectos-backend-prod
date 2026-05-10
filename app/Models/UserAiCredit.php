<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiCredit extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'credits_available',
        'credits_used',
        'period_starts_at',
        'period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_at'  => 'datetime',
            'period_ends_at'    => 'datetime',
            'credits_available' => 'integer',
            'credits_used'      => 'integer',
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

    public function hasSufficientCredits(int $required): bool
    {
        return $this->credits_available >= $required;
    }
}
