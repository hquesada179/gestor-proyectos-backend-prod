<?php

use App\Http\Controllers\Api\ProyectoApiController;
use App\Http\Controllers\Api\TareaApiController;
use Illuminate\Support\Facades\Route;

Route::get('/proyectos', [ProyectoApiController::class, 'index']);
Route::get('/proyectos/{id}', [ProyectoApiController::class, 'show']);
Route::get('/proyectos/{id}/tareas', [TareaApiController::class, 'index']);
