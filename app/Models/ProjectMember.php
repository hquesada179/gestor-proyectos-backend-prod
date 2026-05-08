<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    protected $fillable = [
        'proyecto_id',
        'user_id',
        'role_id',
        'name',
        'email',
        'position',
        'status',
        'work_mode',
        'location',
        'notes',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function initials(): string
    {
        $parts = explode(' ', trim($this->name));
        $ini   = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $ini .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
        return $ini;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'activo'     => 'Activo',
            'inactivo'   => 'Inactivo',
            'invitado'   => 'Invitado',
            'suspendido' => 'Suspendido',
            default      => ucfirst($this->status ?? ''),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'activo'     => 'emerald',
            'inactivo'   => 'slate',
            'invitado'   => 'amber',
            'suspendido' => 'red',
            default      => 'slate',
        };
    }

    public function workModeLabel(): string
    {
        return match ($this->work_mode) {
            'presencial' => 'Presencial',
            'remoto'     => 'Remoto',
            'hibrido'    => 'Híbrido',
            default      => '—',
        };
    }
}
