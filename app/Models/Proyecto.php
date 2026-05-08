<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Proyecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_fin_estimada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio'       => 'date',
            'fecha_fin_estimada' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProjectInput::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function userStories(): HasManyThrough
    {
        return $this->hasManyThrough(UserStory::class, Requirement::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    /**
     * Scope: projects owned by $userId OR where $userId is an active member.
     */
    public function scopeAccessibleBy(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereHas('members', fn ($m) =>
                  $m->where('user_id', $userId)->where('status', 'activo')
              );
        });
    }

    /**
     * Returns true if $userId owns this project or is an active member.
     */
    public function isAccessibleBy(int $userId): bool
    {
        if ($this->user_id === $userId) {
            return true;
        }
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', 'activo')
            ->exists();
    }

    /**
     * Returns true if $userId is the owner of this project.
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
