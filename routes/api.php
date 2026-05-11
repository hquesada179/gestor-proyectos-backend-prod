<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\AiProjectApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FirebaseApiAuthController;
use App\Http\Controllers\Api\ProyectoApiController;
use App\Http\Controllers\Api\ScrumBoardApiController;
use App\Http\Controllers\Api\SprintApiController;
use App\Http\Controllers\Api\TareaApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

// ── Público: Android envía Firebase ID Token y recibe Bearer token ─────────────
Route::post('/auth/firebase-login', [FirebaseApiAuthController::class, 'login']);

// ── Protegidas: requieren Authorization: Bearer <sanctum-token> ────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/user', [UserApiController::class, 'show']);

    // Dashboard / métricas
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Proyectos
    Route::get('/proyectos', [ProyectoApiController::class, 'index']);
    Route::post('/proyectos', [ProyectoApiController::class, 'store']);
    Route::get('/proyectos/{id}', [ProyectoApiController::class, 'show']);
    Route::get('/proyectos/{id}/tareas', [TareaApiController::class, 'index']);
    Route::get('/proyectos/{id}/sprints', [SprintApiController::class, 'index']);
    Route::get('/proyectos/{id}/scrum-board', [ScrumBoardApiController::class, 'show']);

    // Tareas globales del usuario
    Route::get('/tareas', [TareaApiController::class, 'all']);

    // IA
    Route::prefix('ai')->name('api.ai.')->group(function () {
        Route::get('/credits', [AiApiController::class, 'credits'])->name('credits');
        Route::get('/history', [AiProjectApiController::class, 'history'])->name('history');
        Route::get('/history/{id}', [AiProjectApiController::class, 'historyDetail'])
            ->whereNumber('id')
            ->name('history.show');

        // Flujo Android: generar → ajustar → confirmar
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::post('/generate',   [AiProjectApiController::class, 'generate'])->name('generate');
            Route::post('/regenerate', [AiProjectApiController::class, 'regenerate'])->name('regenerate');
            Route::post('/adjust',     [AiProjectApiController::class, 'adjust'])->name('adjust');
            Route::post('/confirm',    [AiProjectApiController::class, 'confirm'])->name('confirm');
        });

        // Rutas heredadas (usadas por Laravel web via API)
        Route::post('/generate-project',   [AiApiController::class, 'generateProject'])->name('generate-project');
        Route::post('/improve-project',    [AiApiController::class, 'improveProject'])->name('improve-project');
        Route::post('/chat',               [AiApiController::class, 'chat'])->name('chat');
        Route::post('/refine-proposal',    [AiApiController::class, 'refineProposal'])->name('refine-proposal');
        Route::post('/regenerate-section', [AiApiController::class, 'regenerateSection'])->name('regenerate-section');
        Route::post('/regenerate-item',    [AiApiController::class, 'regenerateItem'])->name('regenerate-item');
    });
});
