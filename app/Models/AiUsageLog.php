<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'action_type',
        'credits_charged',
        'estimated_input_tokens',
        'estimated_output_tokens',
        'actual_input_tokens',
        'actual_output_tokens',
        'estimated_cost_usd',
        'actual_cost_usd',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'credits_charged'          => 'integer',
            'estimated_input_tokens'   => 'integer',
            'estimated_output_tokens'  => 'integer',
            'actual_input_tokens'      => 'integer',
            'actual_output_tokens'     => 'integer',
            'estimated_cost_usd'       => 'decimal:6',
            'actual_cost_usd'          => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'project_id');
    }
}
