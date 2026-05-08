<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivityLog extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'title',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionIcon(): string
    {
        return match ($this->action) {
            'created'              => 'add_circle',
            'updated'              => 'edit',
            'deleted'              => 'delete',
            'changed_status'       => 'swap_horiz',
            'invited'              => 'person_add',
            'added_member'         => 'group_add',
            'removed_member'       => 'person_remove',
            'accepted_invitation'  => 'check_circle',
            'rejected_invitation'  => 'cancel',
            'assigned'             => 'assignment_ind',
            default                => 'history',
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'created'              => '#34d399',
            'updated'              => '#60a5fa',
            'deleted'              => '#f87171',
            'changed_status'       => '#fbbf24',
            'invited'              => '#a5b4fc',
            'added_member'         => '#34d399',
            'removed_member'       => '#f87171',
            'accepted_invitation'  => '#34d399',
            'rejected_invitation'  => '#f87171',
            'assigned'             => '#60a5fa',
            default                => '#94a3b8',
        };
    }

    public function moduleLabel(): string
    {
        return match ($this->module) {
            'proyectos'      => 'Proyectos',
            'tareas'         => 'Tareas',
            'requerimientos' => 'Requerimientos',
            'historias'      => 'Historias',
            'sprints'        => 'Sprints',
            'insumos'        => 'Insumos',
            'equipo'         => 'Equipo',
            'invitaciones'   => 'Invitaciones',
            'roles'          => 'Roles',
            default          => ucfirst($this->module),
        };
    }

    public function moduleIcon(): string
    {
        return match ($this->module) {
            'proyectos'      => 'folder_open',
            'tareas'         => 'task_alt',
            'requerimientos' => 'edit_note',
            'historias'      => 'bookmark',
            'sprints'        => 'sprint',
            'insumos'        => 'inventory_2',
            'equipo'         => 'group',
            'invitaciones'   => 'mail',
            'roles'          => 'admin_panel_settings',
            default          => 'history',
        };
    }

    public function hasDetail(): bool
    {
        return !empty($this->old_values) || !empty($this->new_values);
    }
}
