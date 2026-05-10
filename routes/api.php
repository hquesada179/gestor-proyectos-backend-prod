<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\ProyectoApiController;
use App\Http\Controllers\Api\SprintApiController;
use App\Http\Controllers\Api\TareaApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('ai')->name('api.ai.')->group(function () {
    Route::post('/generate-project', [AiApiController::class, 'generateProject'])->name('generate-project');
    Route::post('/improve-project', [AiApiController::class, 'improveProject'])->name('improve-project');
    Route::post('/chat', [AiApiController::class, 'chat'])->name('chat');
    Route::post('/refine-proposal', [AiApiController::class, 'refineProposal'])->name('refine-proposal');
    Route::post('/regenerate-section', [AiApiController::class, 'regenerateSection'])->name('regenerate-section');
    Route::post('/regenerate-item', [AiApiController::class, 'regenerateItem'])->name('regenerate-item');
});

Route::get('/proyectos', [ProyectoApiController::class, 'index']);
Route::get('/proyectos/{id}', [ProyectoApiController::class, 'show']);
Route::get('/proyectos/{id}/tareas', [TareaApiController::class, 'index']);
Route::get('/proyectos/{id}/sprints', [SprintApiController::class, 'index']);

Route::get('/tareas', [TareaApiController::class, 'all']);
