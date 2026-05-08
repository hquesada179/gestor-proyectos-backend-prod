<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">{{ __('app.nav.my_tasks') }}</span>
    </x-slot>

    <div class="p-8 space-y-6 max-w-[1400px]">

        {{-- ── Page header ──────────────────────────────────────────── --}}
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('app.nav.my_tasks') }}</h1>
            <p class="text-sm text-on-surface-variant mt-1">{{ __('app.my_tasks_page.subtitle') }}</p>
        </div>

        {{-- ── Filters ───────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('mis-tareas') }}"
              class="glass-panel rounded-2xl px-5 py-4 flex items-center gap-5 flex-wrap">

            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gray-500" style="font-size: 16px;">filter_list</span>
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ __('app.my_tasks_page.filters') }}</span>
            </div>

            @if ($sprintsDisponibles->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-400 whitespace-nowrap">{{ __('app.my_tasks_page.sprint_lbl') }}</label>
                    <select name="sprint" onchange="this.form.submit()" class="ds-select">
                        <option value="todos" {{ $sprintFiltro === 'todos' ? 'selected' : '' }}>{{ __('app.status.all') }}</option>
                        <option value="sin_sprint" {{ $sprintFiltro === 'sin_sprint' ? 'selected' : '' }}>{{ __('app.status.no_sprint') }}</option>
                        @foreach ($sprintsDisponibles as $sprint)
                            <option value="{{ $sprint->id }}"
                                {{ (string) $sprintFiltro === (string) $sprint->id ? 'selected' : '' }}>
                                {{ $sprint->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400 whitespace-nowrap">{{ __('app.my_tasks_page.status_lbl') }}</label>
                <select name="estado" onchange="this.form.submit()" class="ds-select">
                    <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>{{ __('app.status.all') }}</option>
                    @foreach ($estadosDisponibles as $status)
                        <option value="{{ $status->id }}"
                            {{ (string) $estadoFiltro === (string) $status->id ? 'selected' : '' }}>
                            {{ $status->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                <a href="{{ route('mis-tareas') }}"
                   class="text-xs text-gray-500 hover:text-gray-300 flex items-center gap-1 transition-colors ml-auto">
                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                    {{ __('app.actions.clear_filters') }}
                </a>
            @endif

        </form>

        {{-- ── Empty state ──────────────────────────────────────────── --}}
        @if ($tasks->isEmpty())
            <div class="glass-panel rounded-2xl p-14 text-center">
                <span class="material-symbols-outlined text-gray-600 block mb-4" style="font-size: 48px;">task_alt</span>
                <p class="text-sm text-gray-500">
                    @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                        {{ __('app.empty.no_tasks_filter_short') }}
                    @else
                        {{ __('app.empty.no_tasks_assigned') }}
                    @endif
                </p>
                @if ($sprintFiltro !== 'todos' || $estadoFiltro !== 'todos')
                    <a href="{{ route('mis-tareas') }}"
                       class="text-xs text-blue-400 hover:underline mt-2 inline-block">
                        {{ __('app.actions.see_all_tasks') }}
                    </a>
                @endif
            </div>

        {{-- ── Tasks table ───────────────────────────────────────────── --}}
        @else
            <div class="glass-panel rounded-2xl overflow-hidden">
                <table class="ds-table w-full">
                    <thead>
                        <tr>
                            <th>{{ __('app.my_tasks_page.task_col') }}</th>
                            <th>{{ __('app.my_tasks_page.project_col') }}</th>
                            <th>{{ __('app.tasks_mod.status_col') }}</th>
                            <th>{{ __('app.my_tasks_page.sprint_col') }}</th>
                            <th>{{ __('app.my_tasks_page.deadline_col') }}</th>
                            <th class="px-6 py-4 border-b border-white/5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $today = now()->startOfDay(); @endphp
                        @foreach ($tasks as $task)
                            <tr class="group">

                                <td>
                                    <span class="text-sm font-semibold text-on-surface">
                                        {{ $task->titulo }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('proyectos.show', $task->proyecto) }}"
                                       class="text-sm text-gray-400 hover:text-blue-400 transition-colors">
                                        {{ $task->proyecto->nombre }}
                                    </a>
                                </td>

                                <td>
                                    @if ($task->status)
                                        @php
                                            $statusNombre = strtolower($task->status->nombre ?? '');
                                            $statusClass = match(true) {
                                                in_array($statusNombre, ['hecho', 'completado', 'done', 'finalizado']) =>
                                                    'bg-green-500/10 text-green-400 border-green-500/20',
                                                in_array($statusNombre, ['en progreso', 'en_progreso', 'progreso', 'in progress']) =>
                                                    'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                in_array($statusNombre, ['pendiente', 'por hacer', 'nuevo', 'todo']) =>
                                                    'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                                in_array($statusNombre, ['bloqueado', 'cancelado']) =>
                                                    'bg-red-500/10 text-red-400 border-red-500/20',
                                                default =>
                                                    'bg-white/5 text-gray-400 border-white/10',
                                            };
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">
                                            {{ $task->status->nombre }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-600">—</span>
                                    @endif
                                </td>

                                <td class="text-xs text-gray-500">
                                    {{ $task->sprint->nombre ?? '—' }}
                                </td>

                                <td>
                                    @if ($task->fecha_limite)
                                        @php
                                            $isOverdue  = $task->fecha_limite->lt($today);
                                            $isUpcoming = !$isOverdue && $task->fecha_limite->lte($today->copy()->addDays(3));
                                            $dateIcon   = $isOverdue ? 'event_busy' : ($isUpcoming ? 'schedule' : 'calendar_today');
                                            $dateClass  = $isOverdue ? 'text-red-400' : ($isUpcoming ? 'text-amber-400' : 'text-gray-400');
                                        @endphp
                                        <span class="text-xs font-medium {{ $dateClass }} flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">{{ $dateIcon }}</span>
                                            {{ $task->fecha_limite->format('d/m/Y') }}
                                            @if ($isOverdue)
                                                <span class="text-red-500/70">{{ __('app.my_tasks_page.overdue') }}</span>
                                            @elseif ($isUpcoming)
                                                <span class="text-amber-500/70">{{ __('app.my_tasks_page.upcoming') }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-600">—</span>
                                    @endif
                                </td>

                                <td class="text-right">
                                    <a href="{{ route('proyectos.tasks.show', [$task->proyecto, $task]) }}"
                                       class="text-xs text-blue-400 hover:underline opacity-0 group-hover:opacity-100
                                              transition-opacity flex items-center gap-1 justify-end">
                                        {{ __('app.actions.view_task') }}
                                        <span class="material-symbols-outlined" style="font-size: 13px;">open_in_new</span>
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($tasks->hasPages())
                    <div class="px-6 py-4 border-t border-white/5">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
</x-app-layout>
