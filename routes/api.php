<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\AiProjectApiController;
use App\Http\Controllers\Api\CalendarEventApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\EquipoApiController;
use App\Http\Controllers\Api\FirebaseApiAuthController;
use App\Http\Controllers\Api\RolApiController;
use App\Http\Controllers\Api\InsumoApiController;
use App\Http\Controllers\Api\InvitacionApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PrivateMessageApiController;
use App\Http\Controllers\Api\ProyectoApiController;
use App\Http\Controllers\Api\RequerimientoApiController;
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
    Route::get('/proyectos/{project}/calendar', [CalendarEventApiController::class, 'projectCalendar'])->whereNumber('project');
    Route::get('/proyectos/{project}/eventos', [CalendarEventApiController::class, 'projectCalendar'])->whereNumber('project');
    Route::post('/proyectos/{project}/eventos', [CalendarEventApiController::class, 'store'])->whereNumber('project');
    Route::get('/proyectos/{id}/messages', [MessageApiController::class, 'projectMessages']);
    Route::post('/proyectos/{id}/messages', [MessageApiController::class, 'store']);

    // Equipo / Miembros — IMPORTANTE: /members antes de /members/chat para evitar colisiones
    Route::get('/proyectos/{project}/members', [EquipoApiController::class, 'members'])->whereNumber('project');
    Route::get('/proyectos/{project}/equipo', [EquipoApiController::class, 'equipo'])->whereNumber('project');
    Route::patch('/proyectos/{project}/members/{userId}', [EquipoApiController::class, 'updateMember'])->whereNumber(['project', 'userId']);
    Route::delete('/proyectos/{project}/members/{userId}', [EquipoApiController::class, 'removeMember'])->whereNumber(['project', 'userId']);
    Route::get('/proyectos/{project}/invitaciones', [EquipoApiController::class, 'invitaciones'])->whereNumber('project');
    Route::post('/proyectos/{project}/invitaciones', [EquipoApiController::class, 'sendInvitacion'])->whereNumber('project');

    Route::get('/proyectos/{id}/members/chat', [PrivateMessageApiController::class, 'projectMembers']);
    Route::get('/proyectos/{id}/requerimientos', [RequerimientoApiController::class, 'index']);
    Route::post('/proyectos/{id}/requerimientos', [RequerimientoApiController::class, 'store']);
    Route::get('/proyectos/{project}/insumos', [InsumoApiController::class, 'index'])->whereNumber('project');
    Route::post('/proyectos/{project}/insumos', [InsumoApiController::class, 'store'])->whereNumber('project');

    // Requerimientos individuales
    Route::get('/requerimientos/{requirement}', [RequerimientoApiController::class, 'show'])->whereNumber('requirement');
    Route::put('/requerimientos/{requirement}', [RequerimientoApiController::class, 'update'])->whereNumber('requirement');
    Route::patch('/requerimientos/{requirement}', [RequerimientoApiController::class, 'update'])->whereNumber('requirement');
    Route::delete('/requerimientos/{requirement}', [RequerimientoApiController::class, 'destroy'])->whereNumber('requirement');
    Route::post('/requerimientos/{requirement}/status', [RequerimientoApiController::class, 'updateStatus'])->whereNumber('requirement');

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
    Route::delete('/invitaciones/{id}', [EquipoApiController::class, 'cancelInvitacion'])->whereNumber('id');

    // Notificaciones
    Route::get('/notificaciones', [NotificacionApiController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionApiController::class, 'markAllRead']);
    Route::post('/notificaciones/{id}/leer', [NotificacionApiController::class, 'markRead']);

    // Tareas globales del usuario
    Route::get('/tareas', [TareaApiController::class, 'all']);
    Route::patch('/tareas/{tarea}', [TareaApiController::class, 'update'])->whereNumber('tarea');

    // Calendario / eventos
    Route::get('/calendar', [CalendarEventApiController::class, 'index']);
    Route::get('/eventos', [CalendarEventApiController::class, 'index']);
    Route::post('/eventos', [CalendarEventApiController::class, 'storeGlobal']);
    Route::get('/eventos/{event}', [CalendarEventApiController::class, 'show'])->whereNumber('event');
    Route::patch('/eventos/{event}', [CalendarEventApiController::class, 'update'])->whereNumber('event');
    Route::delete('/eventos/{event}', [CalendarEventApiController::class, 'destroy'])->whereNumber('event');

    // Insumos individuales — plural y singular para compatibilidad con Android
    Route::get('/insumos/{id}', [InsumoApiController::class, 'show'])->whereNumber('id');
    Route::patch('/insumos/{id}', [InsumoApiController::class, 'update'])->whereNumber('id');
    Route::delete('/insumos/{id}', [InsumoApiController::class, 'destroy'])->whereNumber('id');
    Route::get('/insumo/{id}', [InsumoApiController::class, 'show'])->whereNumber('id');
    Route::patch('/insumo/{id}', [InsumoApiController::class, 'update'])->whereNumber('id');
    Route::delete('/insumo/{id}', [InsumoApiController::class, 'destroy'])->whereNumber('id');

    // Roles y Permisos
    Route::get('/roles', [RolApiController::class, 'index']);
    Route::post('/roles', [RolApiController::class, 'store']);
    Route::get('/roles/{role}', [RolApiController::class, 'show'])->whereNumber('role');
    Route::patch('/roles/{role}', [RolApiController::class, 'update'])->whereNumber('role');
    Route::delete('/roles/{role}', [RolApiController::class, 'destroy'])->whereNumber('role');
    Route::patch('/roles/{role}/permisos', [RolApiController::class, 'updatePermissions'])->whereNumber('role');
    Route::get('/permisos', [RolApiController::class, 'permisos']);
    Route::get('/permissions', [RolApiController::class, 'permisos']);

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
