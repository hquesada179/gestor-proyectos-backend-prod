<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ModuloSelectorController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectInputController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ScrumBoardController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserStoryController;
use App\Http\Controllers\ChatController;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    $totalProyectos    = Auth::user()->proyectos()->count();
    $sprintsActivos    = Sprint::whereHas('proyecto', fn($q) => $q->where('user_id', Auth::id()))
                             ->where('estado', 'en_progreso')->count();
    $tareasPendientes  = Task::where('assigned_to', Auth::id())
                             ->whereHas('status', fn($q) => $q->where('nombre', '!=', 'Completado'))
                             ->count();
    $proyectosRecientes = Auth::user()->proyectos()
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
});

require __DIR__.'/auth.php';
