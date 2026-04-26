<?php

use App\Http\Controllers\Api\ProyectoApiController;
use Illuminate\Support\Facades\Route;

Route::get('/proyectos', [ProyectoApiController::class, 'index']);
