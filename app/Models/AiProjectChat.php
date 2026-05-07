<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProjectChat extends Model
{
    protected $fillable = [
        'user_id',
        'proyecto_id',
        'parent_chat_id',
        'tipo_accion',
        'prompt_usuario',
        'prompt_mejorado',
        'respuesta_ia',
        'datos_detectados',
        'estado',
    ];

    protected $casts = [
        'datos_detectados' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
