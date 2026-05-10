<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Mis tareas</span>
    </x-slot>

    <div class="p-4 md:p-8 space-y-6 max-w-[1500px]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Mis tareas</h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    Gestiona tus tareas asignadas y las tareas de proyectos donde participas.
                </p>
            </div>

            <a href="{{ route('mis-tareas.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary-container px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-secondary-container/20 transition-all hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                Nueva tarea
            </a>
        </div>

        @if (session('success'))
            <div class="glass-panel rounded-xl border-emerald-500/30 px-5 py-3 text-sm text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('mis-tareas.index') }}"
              class="glass-panel rounded-2xl px-5 py-4 flex items-center gap-5 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gray-500" style="font-size:16px;">filter_list</span>
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>

            @if ($sprintsDisponibles->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-400 whitespace-nowrap">Sprint</label>
                    <select name="sprint" onchange="this.form.submit()" class="ds-select dark-form-select">
                        <option value="todos" {{ $sprintFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="sin_sprint" {{ $sprintFiltro === 'sin_sprint' ? 'selected' : '' }}>Sin sprint</option>
                        @foreach ($sprintsDisponibles as $sprint)
                            <option value="{{ $sprint->id }}" {{ (string) $sprintFiltro === (string) $sprint->id ? 'selected' : '' }}>
                                {{ $sprint->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400 whitespace-nowrap">Estado</label>
                <select name="estado" onchange="this.form.submit()" class="ds-select dark-form-select">
                    <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                    @foreach ($estadosDisponibles as $status)
                        <option value="{{ $status->id }}" {{ (string) $estadoFiltro === (string) $status->id ? 'selected' : '' }}>
                            {{ $status->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                <a href="{{ route('mis-tareas.index') }}"
                   class="ml-auto flex items-center gap-1 text-xs text-gray-500 transition-colors hover:text-gray-300">
                    <span class="material-symbols-outlined" style="font-size:14px;">close</span>
                    Limpiar filtros
                </a>
            @endif
        </form>

        @if ($tasks->isEmpty())
            <div class="glass-panel rounded-2xl p-14 text-center">
                <span class="material-symbols-outlined mx-auto block text-gray-600" style="font-size:52px;">task_alt</span>
                <h2 class="mt-4 text-lg font-bold text-white">No hay tareas para mostrar</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                    @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                        No hay tareas que coincidan con los filtros seleccionados.
                    @else
                        Crea una nueva tarea o revisa los proyectos donde participas.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('mis-tareas.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-secondary-container px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">
                        <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                        Nueva tarea
                    </a>
                    @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                        <a href="{{ route('mis-tareas.index') }}"
                           class="inline-flex items-center rounded-xl border border-white/10 bg-surface px-5 py-2.5 text-sm font-bold text-gray-300 transition hover:bg-white/5">
                            Ver todas
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="ds-table w-full">
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Proyecto</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Sprint</th>
                                <th>Fecha limite</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $today = now()->startOfDay(); @endphp
                            @foreach ($tasks as $task)
                                @php
                                    $statusName = strtolower($task->status->nombre ?? '');
                                    $isDone = str_contains($statusName, 'complet') || str_contains($statusName, 'hecho');
                                    $isOverdue = $task->fecha_limite && $task->fecha_limite->lt($today) && !$isDone;
                                    $statusBadgeClass = 'inline-flex shrink-0 items-center justify-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase leading-none tracking-wide whitespace-nowrap align-middle';
                                    $statusClass = match (true) {
                                        str_contains($statusName, 'complet') || str_contains($statusName, 'hecho') => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
                                        str_contains($statusName, 'progreso') => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
                                        str_contains($statusName, 'revision') || str_contains($statusName, 'revisi') => 'border-yellow-400/60 bg-yellow-500/10 text-yellow-200',
                                        str_contains($statusName, 'pendiente') => 'border-slate-500 bg-slate-800/60 text-slate-200',
                                        default => 'bg-white/5 text-gray-400 border-white/10',
                                    };
                                    $canDelete = (int) $task->assigned_to === Auth::id()
                                        || ($task->proyecto?->isAccessibleBy(Auth::id()) ?? false);
                                @endphp

                                <tr class="group">
                                    <td>
                                        <a href="{{ route('mis-tareas.show', $task) }}"
                                           class="block text-sm font-semibold text-on-surface transition-colors hover:text-blue-300">
                                            {{ $task->titulo }}
                                        </a>
                                        @if ($task->descripcion)
                                            <p class="mt-1 max-w-xs truncate text-xs text-gray-500">{{ $task->descripcion }}</p>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($task->proyecto)
                                            <a href="{{ route('proyectos.show', $task->proyecto) }}"
                                               class="text-sm text-gray-400 transition-colors hover:text-blue-400">
                                                {{ $task->proyecto->nombre }}
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-600">Sin proyecto</span>
                                        @endif
                                    </td>

                                    <td class="text-sm text-gray-400">
                                        {{ $task->assignedTo->name ?? 'Sin responsable' }}
                                    </td>

                                    <td>
                                        @if ($task->status)
                                            <span class="{{ $statusBadgeClass }} {{ $statusClass }}">{{ $task->status->nombre }}</span>
                                        @else
                                            <span class="text-xs text-gray-600">Sin estado</span>
                                        @endif
                                    </td>

                                    <td class="text-xs text-gray-500">
                                        {{ $task->sprint->nombre ?? 'Sin sprint' }}
                                    </td>

                                    <td>
                                        @if ($task->fecha_limite)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium {{ $isOverdue ? 'text-red-400' : 'text-gray-400' }}">
                                                <span class="material-symbols-outlined" style="font-size:13px;">{{ $isOverdue ? 'event_busy' : 'calendar_today' }}</span>
                                                {{ $task->fecha_limite->format('d/m/Y') }}
                                                @if ($isOverdue)
                                                    <span class="text-red-500/80">Vencida</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-600">Sin fecha</span>
                                        @endif
                                    </td>

                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('mis-tareas.show', $task) }}"
                                               class="text-xs font-semibold text-blue-400 hover:text-blue-300">
                                                Ver
                                            </a>
                                            <a href="{{ route('mis-tareas.edit', $task) }}"
                                               class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">
                                                Editar
                                            </a>
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('mis-tareas.destroy', $task) }}"
                                                      onsubmit="return confirm('Eliminar esta tarea? Esta accion no se puede deshacer.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-red-400 hover:text-red-300">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($tasks->hasPages())
                    <div class="border-t border-white/5 px-6 py-4">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
