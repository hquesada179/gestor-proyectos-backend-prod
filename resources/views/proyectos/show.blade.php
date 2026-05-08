<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $proyecto->nombre }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.edit', $proyecto) }}">
                    <x-secondary-button>{{ __('app.actions.edit') }}</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.destroy', $proyecto) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('¿Eliminar este proyecto? Esta acción no se puede deshacer.')">
                        {{ __('app.actions.delete') }}
                    </x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">

                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.status') }}</p>
                        <p class="mt-1 text-sm text-gray-900 capitalize">{{ $proyecto->estado }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.description') }}</p>
                        <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                            {{ $proyecto->descripcion ?? '—' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.start_date') }}</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $proyecto->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.end_date') }}</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $proyecto->fecha_fin_estimada?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.created') }}</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $proyecto->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4">{{ __('app.project.summary') }}</h3>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                            class="text-center p-3 bg-gray-50 rounded-md hover:bg-indigo-50 hover:ring-1 hover:ring-indigo-200 transition group">
                            <p class="text-2xl font-semibold text-gray-800 group-hover:text-indigo-700">{{ $stats['tasks'] }}</p>
                            <p class="text-xs text-gray-500 mt-1 group-hover:text-indigo-600">{{ __('app.nav.tasks_board') }}</p>
                        </a>
                        <a href="{{ route('proyectos.sprints.index', $proyecto) }}"
                            class="text-center p-3 bg-gray-50 rounded-md hover:bg-indigo-50 hover:ring-1 hover:ring-indigo-200 transition group">
                            <p class="text-2xl font-semibold text-gray-800 group-hover:text-indigo-700">{{ $stats['sprints'] }}</p>
                            <p class="text-xs text-gray-500 mt-1 group-hover:text-indigo-600">{{ __('app.nav.sprints') }}</p>
                        </a>
                        <a href="{{ route('proyectos.requirements.index', $proyecto) }}"
                            class="text-center p-3 bg-gray-50 rounded-md hover:bg-indigo-50 hover:ring-1 hover:ring-indigo-200 transition group">
                            <p class="text-2xl font-semibold text-gray-800 group-hover:text-indigo-700">{{ $stats['requirements'] }}</p>
                            <p class="text-xs text-gray-500 mt-1 group-hover:text-indigo-600">{{ __('app.nav.requirements') }}</p>
                        </a>
                        <div class="text-center p-3 bg-gray-50 rounded-md" title="{{ __('app.project.stories') }}">
                            <p class="text-2xl font-semibold text-gray-800">{{ $stats['userStories'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('app.project.stories') }}</p>
                        </div>
                        <a href="{{ route('proyectos.inputs.index', $proyecto) }}"
                            class="text-center p-3 bg-gray-50 rounded-md hover:bg-indigo-50 hover:ring-1 hover:ring-indigo-200 transition group">
                            <p class="text-2xl font-semibold text-gray-800 group-hover:text-indigo-700">{{ $stats['inputs'] }}</p>
                            <p class="text-xs text-gray-500 mt-1 group-hover:text-indigo-600">{{ __('app.nav.inputs') }}</p>
                        </a>
                    </div>

                    @if ($tasksByStatus->isNotEmpty())
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">{{ __('app.project.tasks_by_status') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tasksByStatus as $item)
                                    <a href="{{ route('proyectos.tasks.index', ['proyecto' => $proyecto, 'estado' => $item->status_id]) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-full text-xs text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 transition">
                                        <span class="font-semibold text-gray-900">{{ $item->total }}</span>
                                        {{ $item->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($kanbanSprints->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4">{{ __('app.project.sprints') }}</h3>
                        <div class="space-y-3">
                            @foreach ($kanbanSprints as $sprint)
                                @php
                                    $total     = $sprint->tasks_count;
                                    $done      = $sprint->completed_tasks_count;
                                    $pct       = $total > 0 ? round(($done / $total) * 100) : 0;
                                    $estadoMap = [
                                        'planificado' => 'bg-gray-100 text-gray-600',
                                        'activo'      => 'bg-blue-100 text-blue-700',
                                        'finalizado'  => 'bg-green-100 text-green-700',
                                    ];
                                    $estadoClass = $estadoMap[$sprint->estado] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}"
                                    class="block p-3 bg-gray-50 rounded-md border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $sprint->nombre }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                {{ $sprint->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                                →
                                                {{ $sprint->fecha_fin?->format('d/m/Y') ?? '—' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3 flex-shrink-0">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $estadoClass }}">
                                                {{ $sprint->estado }}
                                            </span>
                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                {{ $done }} / {{ $total }} {{ __('app.project.completed_count') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if ($total > 0)
                                        <div class="mt-2 h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all"
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.project.tasks_board') }}</h3>
                            <form method="GET" action="{{ route('proyectos.show', $proyecto) }}">
                                <select name="kanban_sprint" onchange="this.form.submit()"
                                    class="text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1 pl-2 pr-6">
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
                                    <div class="w-52 flex-shrink-0">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $status->nombre }}</span>
                                            <span class="text-xs text-gray-400 font-medium">{{ $columnTasks->count() }}</span>
                                        </div>
                                        <div class="space-y-2">
                                            @forelse ($columnTasks as $task)
                                                <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}"
                                                    class="block p-3 bg-gray-50 rounded-md border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition text-left">
                                                    <p class="text-sm font-medium text-gray-900 leading-snug">{{ $task->titulo }}</p>
                                                    @if ($task->fecha_limite)
                                                        @php
                                                            $isOverdue  = $task->fecha_limite->lt($today);
                                                            $isUpcoming = !$isOverdue && $task->fecha_limite->lte($today->copy()->addDays(3));
                                                        @endphp
                                                        <p class="text-xs mt-1 font-medium
                                                            {{ $isOverdue ? 'text-red-500' : ($isUpcoming ? 'text-amber-500' : 'text-gray-400') }}">
                                                            {{ $task->fecha_limite->format('d/m/Y') }}
                                                            @if ($isOverdue) {{ __('app.my_tasks_page.overdue') }}
                                                            @elseif ($isUpcoming) {{ __('app.my_tasks_page.upcoming') }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                    @if ($task->assignedTo)
                                                        <p class="text-xs text-indigo-500 mt-1">{{ $task->assignedTo->name }}</p>
                                                    @endif
                                                </a>
                                            @empty
                                                <p class="text-xs text-gray-400 italic px-1">{{ __('app.project.no_tasks') }}</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">{{ __('app.project.modules') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('proyectos.inputs.index', $proyecto) }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition">
                            {{ __('app.nav.inputs') }}
                        </a>
                        <a href="{{ route('proyectos.requirements.index', $proyecto) }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition">
                            {{ __('app.nav.requirements') }}
                        </a>
                        <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition">
                            {{ __('app.nav.tasks_board') }}
                        </a>
                        <a href="{{ route('proyectos.sprints.index', $proyecto) }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition">
                            {{ __('app.nav.sprints') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('proyectos.index') }}" class="text-indigo-600 hover:underline">
                    {{ __('app.project.back') }}
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
