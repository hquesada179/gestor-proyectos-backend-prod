<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProjectDraft extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'selected_mode',
        'original_prompt',
        'improved_prompt',
        'interpretation_data',
        'project_data',
        'requirements_data',
        'tasks_data',
        'sprints_data',
        'supplies_data',
        'raw_responses',
    ];

    protected $casts = [
        'interpretation_data' => 'array',
        'project_data'        => 'array',
        'requirements_data'   => 'array',
        'tasks_data'          => 'array',
        'sprints_data'        => 'array',
        'supplies_data'       => 'array',
        'raw_responses'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
