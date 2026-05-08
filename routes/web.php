<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ModuloSelectorController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectInputController;
use App\Http\Controllers\ProjectActivityController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScrumBoardController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserStoryController;
use App\Http\Controllers\ChatController;
use App\Models\Proyecto;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::post('/language', [LanguageController::class, 'change'])->name('language.change');

Route::get('/dashboard', function () {
    $userId = Auth::id();

    $totalProyectos    = Proyecto::accessibleBy($userId)->count();
    $sprintsActivos    = Sprint::whereHas('proyecto', fn($q) => $q->accessibleBy($userId))
                             ->where('estado', 'en_progreso')->count();
    $tareasPendientes  = Task::where('assigned_to', $userId)
                             ->whereHas('status', fn($q) => $q->where('nombre', '!=', 'Completado'))
                             ->count();
    $proyectosRecientes = Proyecto::accessibleBy($userId)
                             ->withCount(['tasks', 'sprints'])
                             ->latest()
                             ->take(4)
                             ->get();
    return view('dashboard', compact('totalProyectos', 'sprintsActivos', 'tareasPendientes', 'proyectosRecientes'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');

    Route::get('/mis-tareas', [MyTasksController::class, 'index'])->name('mis-tareas');
    Route::get('/modulo/{modulo}', [ModuloSelectorController::class, 'show'])->name('modulo.selector');

    // Calendario
    Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');

    // Scrum Board (Kanban)
    Route::get('/scrum-board', [ScrumBoardController::class, 'index'])->name('scrum-board.index');
    Route::get('/scrum-board/{proyecto}', [ScrumBoardController::class, 'show'])->name('scrum-board.show');
    Route::patch('/scrum-board/tasks/{task}/status', [ScrumBoardController::class, 'updateStatus'])->name('scrum-board.tasks.status');

    // Chat IA
    Route::get('proyectos/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('proyectos/chat', [ChatController::class, 'sendMessage'])->name('chat.send');

    // Historial IA (must be before any wildcard segments)
    Route::get('proyectos/chat/historial', [ChatController::class, 'history'])->name('chat.history');
    Route::delete('proyectos/chat/historial/limpiar', [ChatController::class, 'clearHistory'])->name('chat.history.clear');
    Route::delete('proyectos/chat/historial/{id}', [ChatController::class, 'deleteHistoryItem'])->name('chat.history.delete');

    // Asistente IA — pantalla principal (nueva)
    Route::get('/asistente-ia', [AiAssistantController::class, 'index'])->name('asistente-ia.index');

    // Asistente IA — endpoints principales (nuevos)
    Route::post('/asistente-ia/submit', [AiAssistantController::class, 'submit'])->name('asistente-ia.submit');
    Route::post('/asistente-ia/apply', [AiAssistantController::class, 'apply'])->name('asistente-ia.apply');
    Route::get('/asistente-ia/historial', [AiAssistantController::class, 'history'])->name('asistente-ia.historial');
    Route::delete('/asistente-ia/historial/limpiar', [AiAssistantController::class, 'clearHistory'])->name('asistente-ia.historial.clear');
    Route::get('/asistente-ia/historial/{id}', [AiAssistantController::class, 'historyDetail'])->name('asistente-ia.historial.detail');
    Route::delete('/asistente-ia/historial/{id}', [AiAssistantController::class, 'deleteHistoryItem'])->name('asistente-ia.historial.delete');

    // Asistente IA — legacy (no romper)
    Route::post('/asistente-ia/generar', [AiAssistantController::class, 'generate'])->name('asistente-ia.generar');
    Route::get('/asistente-ia/guiado', [AiAssistantController::class, 'guided'])->name('asistente-ia.guiado');
    Route::post('/asistente-ia/interpretar', [AiAssistantController::class, 'interpret'])->name('asistente-ia.interpretar');
    Route::resource('proyectos', ProyectoController::class);
    Route::resource('proyectos.inputs', ProjectInputController::class);
    Route::resource('proyectos.requirements', RequirementController::class);
    Route::resource('proyectos.requirements.user-stories', UserStoryController::class);
    Route::get('proyectos/{proyecto}/tasks/export', [TaskController::class, 'export'])->name('proyectos.tasks.export');
    Route::resource('proyectos.tasks', TaskController::class);
    Route::resource('proyectos.sprints', SprintController::class);

    // ── Equipo (Team) ─────────────────────────────────────────────────────
    Route::get('/equipo', [TeamController::class, 'index'])->name('team.index');
    Route::get('/equipo/{proyecto}', [TeamController::class, 'show'])->name('team.show');
    Route::post('/equipo/{proyecto}/miembros', [TeamController::class, 'storeMember'])->name('team.members.store');
    Route::put('/equipo/miembros/{member}', [TeamController::class, 'updateMember'])->name('team.members.update');
    Route::delete('/equipo/miembros/{member}', [TeamController::class, 'destroyMember'])->name('team.members.destroy');

    // ── Roles & Permisos ─────────────────────────────────────────────────
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::put('/roles/{role}/permisos', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

    // ── Actividad / Auditoría ─────────────────────────────────────────────
    Route::get('/proyectos/{proyecto}/actividad', [ProjectActivityController::class, 'index'])->name('proyectos.actividad');

    // ── Invitaciones ─────────────────────────────────────────────────────
    Route::get('/invitaciones', [ProjectInvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitaciones/{invitation}/aceptar', [ProjectInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/invitaciones/{invitation}/rechazar', [ProjectInvitationController::class, 'reject'])->name('invitations.reject');
});

require __DIR__.'/auth.php';
