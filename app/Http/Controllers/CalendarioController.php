<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Sprint;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarioController extends Controller
{
    public function index(Request $request)
    {
        $user      = Auth::user();
        $proyectos = $user->proyectos()->orderBy('nombre')->get();

        // Project filter (validate ownership)
        $proyectoId = $request->query('proyecto_id') ?: null;
        if ($proyectoId && ! $proyectos->contains('id', (int) $proyectoId)) {
            $proyectoId = null;
        }

        // Month to display
        try {
            $mesActual = $request->filled('mes')
                ? Carbon::createFromFormat('Y-m', $request->query('mes'))->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $mesActual = Carbon::now()->startOfMonth();
        }

        $proyectoIds = $proyectoId
            ? collect([(int) $proyectoId])
            : $proyectos->pluck('id');

        $events = collect();

        // ── Tareas (fecha_limite) ────────────────────────────────────
        Task::whereIn('proyecto_id', $proyectoIds)
            ->whereNotNull('fecha_limite')
            ->with(['proyecto', 'status'])
            ->get()
            ->each(function ($task) use (&$events) {
                $completado = str_contains(
                    strtolower($task->status->nombre ?? ''), 'complet'
                );
                $events->push([
                    'id'       => 'tarea-' . $task->id,
                    'tipo'     => 'tarea',
                    'titulo'   => $task->titulo,
                    'fecha'    => $task->fecha_limite->format('Y-m-d'),
                    'proyecto' => $task->proyecto->nombre ?? '—',
                    'estado'   => $task->status->nombre ?? 'Sin estado',
                    'vencida'  => $task->fecha_limite->isPast() && ! $completado,
                    'url'      => route('proyectos.tasks.show', [$task->proyecto_id, $task->id]),
                ]);
            });

        // ── Sprints (fecha_inicio + fecha_fin) ───────────────────────
        Sprint::whereIn('proyecto_id', $proyectoIds)
            ->with('proyecto')
            ->get()
            ->each(function ($sprint) use (&$events) {
                if ($sprint->fecha_inicio) {
                    $events->push([
                        'id'       => 'sprint-ini-' . $sprint->id,
                        'tipo'     => 'sprint',
                        'titulo'   => $sprint->nombre . ' · Inicio',
                        'fecha'    => $sprint->fecha_inicio->format('Y-m-d'),
                        'proyecto' => $sprint->proyecto->nombre ?? '—',
                        'estado'   => ucfirst(str_replace('_', ' ', $sprint->estado)),
                        'vencida'  => false,
                        'url'      => route('proyectos.sprints.show', [$sprint->proyecto_id, $sprint->id]),
                    ]);
                }
                if ($sprint->fecha_fin) {
                    $events->push([
                        'id'       => 'sprint-fin-' . $sprint->id,
                        'tipo'     => 'sprint',
                        'titulo'   => $sprint->nombre . ' · Fin',
                        'fecha'    => $sprint->fecha_fin->format('Y-m-d'),
                        'proyecto' => $sprint->proyecto->nombre ?? '—',
                        'estado'   => ucfirst(str_replace('_', ' ', $sprint->estado)),
                        'vencida'  => false,
                        'url'      => route('proyectos.sprints.show', [$sprint->proyecto_id, $sprint->id]),
                    ]);
                }
            });

        // ── Proyectos (fecha_inicio + fecha_fin_estimada) ────────────
        Proyecto::whereIn('id', $proyectoIds)->get()
            ->each(function ($proyecto) use (&$events) {
                if ($proyecto->fecha_inicio) {
                    $events->push([
                        'id'       => 'proy-ini-' . $proyecto->id,
                        'tipo'     => 'proyecto',
                        'titulo'   => $proyecto->nombre . ' · Inicio',
                        'fecha'    => $proyecto->fecha_inicio->format('Y-m-d'),
                        'proyecto' => $proyecto->nombre,
                        'estado'   => ucfirst($proyecto->estado ?? ''),
                        'vencida'  => false,
                        'url'      => route('proyectos.show', $proyecto->id),
                    ]);
                }
                if ($proyecto->fecha_fin_estimada) {
                    $events->push([
                        'id'       => 'proy-fin-' . $proyecto->id,
                        'tipo'     => 'proyecto',
                        'titulo'   => $proyecto->nombre . ' · Fin estimado',
                        'fecha'    => $proyecto->fecha_fin_estimada->format('Y-m-d'),
                        'proyecto' => $proyecto->nombre,
                        'estado'   => ucfirst($proyecto->estado ?? ''),
                        'vencida'  => $proyecto->fecha_fin_estimada->isPast(),
                        'url'      => route('proyectos.show', $proyecto->id),
                    ]);
                }
            });

        // Group by date for the grid + JS panel
        $eventsByDate = $events->groupBy('fecha');

        // Build calendar days — weeks start on Sunday (DOM → SAB, like Stitch reference)
        $primerDia = $mesActual->clone()->startOfMonth();
        $ultimoDia = $mesActual->clone()->endOfMonth();

        // Go back to the nearest Sunday on or before the first day of the month
        $cursor = $primerDia->clone()->subDays($primerDia->dayOfWeek);
        // Go forward to the nearest Saturday on or after the last day of the month
        $daysToSat   = (6 - $ultimoDia->dayOfWeek + 7) % 7;
        $calendarEnd = $ultimoDia->clone()->addDays($daysToSat);

        $days = collect();
        while ($cursor <= $calendarEnd) {
            $dateKey = $cursor->format('Y-m-d');
            $days->push([
                'date'    => $dateKey,
                'day'     => $cursor->day,
                'inMonth' => $cursor->month === $mesActual->month,
                'isToday' => $cursor->isToday(),
                'events'  => $eventsByDate->get($dateKey, collect()),
            ]);
            $cursor->addDay();
        }

        $mesSiguiente = $mesActual->clone()->addMonth()->format('Y-m');
        $mesAnterior  = $mesActual->clone()->subMonth()->format('Y-m');

        // Próximos eventos: desde hoy, ordenados por fecha, máximo 7
        $hoy      = Carbon::now()->startOfDay()->format('Y-m-d');
        $upcoming = $events
            ->filter(fn($e) => $e['fecha'] >= $hoy)
            ->sortBy('fecha')
            ->take(7)
            ->values();

        return view('calendario.index', compact(
            'proyectos', 'proyectoId', 'mesActual',
            'days', 'eventsByDate',
            'mesSiguiente', 'mesAnterior',
            'upcoming'
        ));
    }
}
