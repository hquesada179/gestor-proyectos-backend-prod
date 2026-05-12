<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'codigo',
        'titulo',
        'descripcion',
        'tipo',
        'prioridad',
        'estado',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function userStories(): HasMany
    {
        return $this->hasMany(UserStory::class);
    }
}
