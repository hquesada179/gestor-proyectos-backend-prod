<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\AiProjectApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FirebaseApiAuthController;
use App\Http\Controllers\Api\InvitacionApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PrivateMessageApiController;
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
    Route::get('/proyectos/{id}/messages', [MessageApiController::class, 'projectMessages']);
    Route::post('/proyectos/{id}/messages', [MessageApiController::class, 'store']);
    Route::get('/proyectos/{id}/members/chat', [PrivateMessageApiController::class, 'projectMembers']);

    // Mensajes grupales — resumen de conversaciones
    Route::get('/messages', [MessageApiController::class, 'index']);

    // Mensajes privados 1 a 1
    // IMPORTANTE: /start y /{id}/read antes de /{id} para evitar colisiones de ruta
    Route::get('/private-messages', [PrivateMessageApiController::class, 'index']);
    Route::post('/private-messages/start', [PrivateMessageApiController::class, 'start']);
    Route::get('/private-messages/{id}', [PrivateMessageApiController::class, 'show'])->whereNumber('id');
    Route::post('/private-messages/{id}', [PrivateMessageApiController::class, 'store'])->whereNumber('id');
    Route::post('/private-messages/{id}/read', [PrivateMessageApiController::class, 'markRead'])->whereNumber('id');

    // Invitaciones
    Route::get('/invitaciones', [InvitacionApiController::class, 'index']);
    Route::post('/invitaciones/{id}/aceptar', [InvitacionApiController::class, 'accept']);
    Route::post('/invitaciones/{id}/rechazar', [InvitacionApiController::class, 'reject']);

    // Notificaciones
    Route::get('/notificaciones', [NotificacionApiController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionApiController::class, 'markAllRead']);
    Route::post('/notificaciones/{id}/leer', [NotificacionApiController::class, 'markRead']);

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
