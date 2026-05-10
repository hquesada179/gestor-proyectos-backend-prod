<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-slate-100">
                {{ $proyecto->nombre }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('proyectos.actividad', $proyecto) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/30 bg-indigo-500/15 px-3 py-1.5 text-xs font-semibold text-indigo-200 transition hover:bg-indigo-500/20">
                    <span class="material-symbols-outlined" style="font-size:14px;">history</span>
                    Actividad
                </a>
                <a href="{{ route('proyectos.edit', $proyecto) }}">
                    <x-secondary-button>{{ __('app.actions.edit') }}</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.destroy', $proyecto) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('Eliminar este proyecto? Esta accion no se puede deshacer.')">
                        {{ __('app.actions.delete') }}
                    </x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Detalle del proyecto</h3>
                    <p class="mt-1 text-sm text-slate-400">Informacion principal y fechas de referencia.</p>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.status') }}</p>
                        <p class="mt-2 text-sm font-semibold capitalize text-white">{{ $proyecto->estado }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.created') }}</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $proyecto->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.description') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-200">
                            {{ $proyecto->descripcion ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.start_date') }}</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $proyecto->fecha_inicio?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.end_date') }}</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $proyecto->fecha_fin_estimada?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">
                    <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.summary') }}</h3>

                    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                            class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4 text-center transition hover:border-indigo-500/60 hover:bg-slate-800">
                            <p class="text-2xl font-bold text-white">{{ $stats['tasks'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.nav.tasks_board') }}</p>
                        </a>
                        <a href="{{ route('proyectos.sprints.index', $proyecto) }}"
                            class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4 text-center transition hover:border-indigo-500/60 hover:bg-slate-800">
                            <p class="text-2xl font-bold text-white">{{ $stats['sprints'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.nav.sprints') }}</p>
                        </a>
                        <a href="{{ route('proyectos.requirements.index', $proyecto) }}"
                            class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4 text-center transition hover:border-indigo-500/60 hover:bg-slate-800">
                            <p class="text-2xl font-bold text-white">{{ $stats['requirements'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.nav.requirements') }}</p>
                        </a>
                        <div class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4 text-center" title="{{ __('app.project.stories') }}">
                            <p class="text-2xl font-bold text-white">{{ $stats['userStories'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.project.stories') }}</p>
                        </div>
                        <a href="{{ route('proyectos.inputs.index', $proyecto) }}"
                            class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4 text-center transition hover:border-indigo-500/60 hover:bg-slate-800">
                            <p class="text-2xl font-bold text-white">{{ $stats['inputs'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.nav.inputs') }}</p>
                        </a>
                    </div>

                    @if ($tasksByStatus->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.tasks_by_status') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tasksByStatus as $item)
                                    <a href="{{ route('proyectos.tasks.index', ['proyecto' => $proyecto, 'estado' => $item->status_id]) }}"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-xs text-slate-300 transition hover:border-indigo-500/60 hover:text-indigo-200">
                                        <span class="font-bold text-white">{{ $item->total }}</span>
                                        {{ $item->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($kanbanSprints->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-6">
                        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.sprints') }}</h3>
                        <div class="space-y-3">
                            @foreach ($kanbanSprints as $sprint)
                                @php
                                    $total     = $sprint->tasks_count;
                                    $done      = $sprint->completed_tasks_count;
                                    $pct       = $total > 0 ? round(($done / $total) * 100) : 0;
                                    $estadoMap = [
                                        'planificado' => 'border-slate-500/70 bg-slate-800 text-slate-200',
                                        'activo'      => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
                                        'finalizado'  => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
                                        'en_progreso' => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
                                        'completado'  => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
                                    ];
                                    $estadoClass = $estadoMap[$sprint->estado] ?? 'border-slate-500/70 bg-slate-800 text-slate-200';
                                @endphp
                                <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}"
                                    class="block rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 transition hover:border-indigo-500/60 hover:bg-slate-800">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white">{{ $sprint->nombre }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ $sprint->fecha_inicio?->format('d/m/Y') ?? '-' }}
                                                -
                                                {{ $sprint->fecha_fin?->format('d/m/Y') ?? '-' }}
                                            </p>
                                        </div>
                                        <div class="flex flex-shrink-0 items-center gap-3">
                                            <span class="inline-flex items-center justify-center rounded-full border px-3 py-1 text-xs font-bold uppercase leading-none whitespace-nowrap {{ $estadoClass }}">
                                                {{ $sprint->estado }}
                                            </span>
                                            <span class="whitespace-nowrap text-xs text-slate-400">
                                                {{ $done }} / {{ $total }} {{ __('app.project.completed_count') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if ($total > 0)
                                        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-700">
                                            <div class="h-full rounded-full bg-indigo-500 transition-all"
                                                style="width: {{ $pct }}%"></div>
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($kanbanStatuses->isNotEmpty() && $stats['tasks'] > 0)
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-6">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.tasks_board') }}</h3>
                            <form method="GET" action="{{ route('proyectos.show', $proyecto) }}">
                                <select name="kanban_sprint" onchange="this.form.submit()"
                                    class="dark-form-select rounded-lg border border-slate-600 bg-slate-800 py-1 pl-3 pr-8 text-xs text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="todos" {{ $kanbanSprint === 'todos' ? 'selected' : '' }}>{{ __('app.project.all_tasks') }}</option>
                                    <option value="sin_sprint" {{ $kanbanSprint === 'sin_sprint' ? 'selected' : '' }}>{{ __('app.project.no_sprint') }}</option>
                                    @foreach ($kanbanSprints as $sprint)
                                        <option value="{{ $sprint->id }}" {{ (string) $kanbanSprint === (string) $sprint->id ? 'selected' : '' }}>
                                            {{ $sprint->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <div class="flex gap-4" style="min-width: max-content;">
                                @php $today = now()->startOfDay(); @endphp
                                @foreach ($kanbanStatuses as $status)
                                    @php $columnTasks = $kanbanTasks->get($status->id, collect()); @endphp
                                    <div class="w-56 flex-shrink-0">
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $status->nombre }}</span>
                                            <span class="text-xs font-medium text-slate-500">{{ $columnTasks->count() }}</span>
                                        </div>
                                        <div class="space-y-2">
                                            @forelse ($columnTasks as $task)
                                                <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}"
                                                    class="block rounded-xl border border-slate-700/50 bg-slate-800/50 p-3 text-left transition hover:border-indigo-500/60 hover:bg-slate-800">
                                                    <p class="text-sm font-semibold leading-snug text-white">{{ $task->titulo }}</p>
                                                    @if ($task->fecha_limite)
                                                        @php
                                                            $isOverdue  = $task->fecha_limite->lt($today);
                                                            $isUpcoming = !$isOverdue && $task->fecha_limite->lte($today->copy()->addDays(3));
                                                        @endphp
                                                        <p class="mt-1 text-xs font-semibold
                                                            {{ $isOverdue ? 'text-red-400' : ($isUpcoming ? 'text-amber-400' : 'text-slate-500') }}">
                                                            {{ $task->fecha_limite->format('d/m/Y') }}
                                                            @if ($isOverdue) {{ __('app.my_tasks_page.overdue') }}
                                                            @elseif ($isUpcoming) {{ __('app.my_tasks_page.upcoming') }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                    @if ($task->assignedTo)
                                                        <p class="mt-1 text-xs text-indigo-300">{{ $task->assignedTo->name }}</p>
                                                    @endif
                                                </a>
                                            @empty
                                                <p class="px-1 text-xs italic text-slate-500">{{ __('app.project.no_tasks') }}</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.project.modules') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('proyectos.inputs.index', $proyecto) }}"
                            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                            {{ __('app.nav.inputs') }}
                        </a>
                        <a href="{{ route('proyectos.requirements.index', $proyecto) }}"
                            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                            {{ __('app.nav.requirements') }}
                        </a>
                        <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                            {{ __('app.nav.tasks_board') }}
                        </a>
                        <a href="{{ route('proyectos.sprints.index', $proyecto) }}"
                            class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                            {{ __('app.nav.sprints') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('proyectos.index') }}" class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                    {{ __('app.project.back') }}
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
