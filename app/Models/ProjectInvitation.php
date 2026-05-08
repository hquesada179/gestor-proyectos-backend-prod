<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInvitation extends Model
{
    protected $fillable = [
        'proyecto_id',
        'invited_by_user_id',
        'invited_user_id',
        'role_id',
        'email',
        'status',
        'token',
        'message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
