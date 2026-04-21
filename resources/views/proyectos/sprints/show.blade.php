<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">
                    <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="hover:text-indigo-600">
                        {{ $proyecto->nombre }} › Sprints
                    </a>
                </p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $sprint->nombre }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.sprints.edit', [$proyecto, $sprint]) }}">
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.sprints.destroy', [$proyecto, $sprint]) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('¿Eliminar este sprint? Esta acción no se puede deshacer.')">
                        Eliminar
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
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Estado</p>
                        @php
                            $badgeClass = match($sprint->estado) {
                                'en_progreso' => 'bg-indigo-100 text-indigo-700',
                                'completado'  => 'bg-green-100 text-green-700',
                                'planificado' => 'bg-gray-100 text-gray-600',
                                default       => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $badgeClass }}">
                            {{ str_replace('_', ' ', $sprint->estado) }}
                        </span>
                    </div>

                    @if ($sprint->objetivo)
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Objetivo</p>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $sprint->objetivo }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fecha de inicio</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $sprint->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fecha de fin</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $sprint->fecha_fin?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Registrado</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $sprint->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                </div>
            </div>

            {{-- Resumen de progreso --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4">Progreso del sprint</h3>

                    @if ($total === 0)
                        <p class="text-sm text-gray-400">Sin tareas asociadas aún.</p>
                    @else
                        {{-- Barra de progreso --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-1 bg-gray-100 rounded-full h-2.5">
                                <div class="bg-green-500 h-2.5 rounded-full transition-all"
                                     style="width: {{ $progreso }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700 w-12 text-right">{{ $progreso }}%</span>
                        </div>

                        {{-- Conteos por estado --}}
                        <div class="flex flex-wrap gap-4">
                            @foreach ($porEstado as $nombreEstado => $grupo)
                                @php
                                    $dot = match(strtolower($nombreEstado)) {
                                        'completado'  => 'bg-green-500',
                                        'en progreso' => 'bg-blue-500',
                                        'en revisión' => 'bg-yellow-500',
                                        default       => 'bg-gray-400',
                                    };
                                @endphp
                                <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $dot }}"></span>
                                    {{ $nombreEstado }}
                                    <span class="font-semibold text-gray-800">{{ $grupo->count() }}</span>
                                </div>
                            @endforeach
                            <div class="text-sm text-gray-400 ml-auto">
                                {{ $completadas }} de {{ $total }} completadas
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Tareas del sprint</h3>

                    @if ($tasks->isEmpty())
                        <p class="text-sm text-gray-500">
                            Este sprint no tiene tareas asociadas todavía.
                            <a href="{{ route('proyectos.tasks.create', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">Crear una tarea</a>
                            y asígnala a este sprint.
                        </p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="pb-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Título</th>
                                    <th class="pb-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Estado</th>
                                    <th class="pb-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Responsable</th>
                                    <th class="pb-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Fecha límite</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($tasks as $task)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-gray-900">
                                            <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}" class="hover:text-indigo-600">
                                                {{ $task->titulo }}
                                            </a>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $task->status->nombre ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $task->assignedTo->name ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $task->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('proyectos.tasks.edit', [$proyecto, $task]) }}" class="text-indigo-600 hover:underline text-xs">Editar</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="text-indigo-600 hover:underline">
                    ← Volver a sprints
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
