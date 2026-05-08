<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScrumBoardController extends Controller
{
    public function index()
    {
        $proyectos = Auth::user()->proyectos()
            ->withCount([
                'tasks',
                'sprints',
                'requirements',
                'inputs',
                'tasks as completed_tasks_count' => fn ($q) => $q->whereHas(
                    'status', fn ($s) => $s->where('nombre', 'Completado')
                ),
            ])
            ->latest()
            ->get();

        return view('scrum-board.index', compact('proyectos'));
    }

    public function show(Proyecto $proyecto)
    {
        abort_if($proyecto->user_id !== Auth::id(), 403);

        $statuses = TaskStatus::orderBy('orden')->get();

        $tasks = $proyecto->tasks()
            ->with('status', 'assignedTo', 'sprint')
            ->get()
            ->groupBy('task_status_id');

        return view('scrum-board.board', compact('proyecto', 'statuses', 'tasks'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        $task->loadMissing('proyecto');

        if (!$task->proyecto || $task->proyecto->user_id !== Auth::id()) {
            return response()->json(['ok' => false, 'error' => 'Sin permiso.'], 403);
        }

        $request->validate([
            'task_status_id' => ['required', 'integer', 'exists:task_statuses,id'],
        ]);

        $task->update(['task_status_id' => $request->task_status_id]);

        return response()->json(['ok' => true]);
    }
}
