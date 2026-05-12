<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
        'tipo',
        'cantidad',
        'unidad',
        'costo',
        'proveedor',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'float',
            'costo'    => 'float',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
