<?php

namespace App\Http\Controllers;

use App\Models\ProjectActivityLog;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectActivityController extends Controller
{
    public function index(Request $request, Proyecto $proyecto): View
    {
        abort_if(!$proyecto->isAccessibleBy(Auth::id()), 403);

        $query = ProjectActivityLog::where('project_id', $proyecto->id)
            ->with('user')
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) =>
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
            );
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        $logs = $query->paginate(30)->withQueryString();

        // Users who have logged activity on this project
        $actorIds = ProjectActivityLog::where('project_id', $proyecto->id)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $actors = User::whereIn('id', $actorIds)->orderBy('name')->get();

        $modules = ProjectActivityLog::where('project_id', $proyecto->id)
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $actions = ProjectActivityLog::where('project_id', $proyecto->id)
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('proyectos.actividad', compact(
            'proyecto', 'logs', 'actors', 'modules', 'actions'
        ));
    }
}
