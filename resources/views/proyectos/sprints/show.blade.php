<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }} / Sprints
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
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
                    <x-danger-button onclick="return confirm('Eliminar este sprint? Esta accion no se puede deshacer.')">
                        Eliminar
                    </x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Detalle del sprint</h3>
                    <p class="mt-1 text-sm text-slate-400">Estado, fechas y alcance del ciclo de trabajo.</p>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Estado</p>
                        @php
                            $badgeClass = match($sprint->estado) {
                                'en_progreso' => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
                                'completado'  => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
                                'planificado' => 'border-slate-500/70 bg-slate-800/70 text-slate-200',
                                default       => 'border-amber-400/60 bg-amber-500/10 text-amber-200',
                            };
                        @endphp
                        <span class="mt-2 inline-flex items-center justify-center rounded-full border px-3 py-1 text-xs font-bold uppercase leading-none whitespace-nowrap {{ $badgeClass }}">
                            {{ str_replace('_', ' ', $sprint->estado) }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registrado</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $sprint->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    @if ($sprint->objetivo)
                        <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Objetivo</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-200">{{ $sprint->objetivo }}</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Fecha de inicio</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $sprint->fecha_inicio?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Fecha de fin</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $sprint->fecha_fin?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">
                    <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Progreso del sprint</h3>

                    @if ($total === 0)
                        <p class="text-sm text-slate-500">Sin tareas asociadas aun.</p>
                    @else
                        <div class="mb-4 flex items-center gap-3">
                            <div class="h-2.5 flex-1 rounded-full bg-slate-800">
                                <div class="h-2.5 rounded-full bg-emerald-500 transition-all"
                                     style="width: {{ $progreso }}%"></div>
                            </div>
                            <span class="w-12 text-right text-sm font-semibold text-slate-200">{{ $progreso }}%</span>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            @foreach ($porEstado as $nombreEstado => $grupo)
                                @php
                                    $dot = match(strtolower($nombreEstado)) {
                                        'completado'  => 'bg-emerald-500',
                                        'en progreso' => 'bg-blue-500',
                                        'en revision' => 'bg-amber-500',
                                        default       => 'bg-slate-500',
                                    };
                                @endphp
                                <div class="flex items-center gap-1.5 text-sm text-slate-300">
                                    <span class="inline-block h-2 w-2 rounded-full {{ $dot }}"></span>
                                    {{ $nombreEstado }}
                                    <span class="font-semibold text-white">{{ $grupo->count() }}</span>
                                </div>
                            @endforeach
                            <div class="ml-auto text-sm text-slate-500">
                                {{ $completadas }} de {{ $total }} completadas
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Tareas del sprint</h3>

                    @if ($tasks->isEmpty())
                        <p class="text-sm text-slate-400">
                            Este sprint no tiene tareas asociadas todavia.
                            <a href="{{ route('proyectos.tasks.create', $proyecto) }}" class="ml-1 text-indigo-300 hover:underline">Crear una tarea</a>
                            y asignala a este sprint.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-xs uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="pb-3 pr-4 text-left font-semibold">Titulo</th>
                                        <th class="pb-3 pr-4 text-left font-semibold">Estado</th>
                                        <th class="pb-3 pr-4 text-left font-semibold">Responsable</th>
                                        <th class="pb-3 pr-4 text-left font-semibold">Fecha limite</th>
                                        <th class="pb-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    @foreach ($tasks as $task)
                                        <tr class="transition hover:bg-slate-800/40">
                                            <td class="py-3 pr-4 font-medium text-slate-100">
                                                <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}" class="hover:text-indigo-300">
                                                    {{ $task->titulo }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4 text-slate-300">{{ $task->status->nombre ?? '-' }}</td>
                                            <td class="py-3 pr-4 text-slate-300">{{ $task->assignedTo->name ?? '-' }}</td>
                                            <td class="py-3 pr-4 text-slate-300">{{ $task->fecha_limite?->format('d/m/Y') ?? '-' }}</td>
                                            <td class="py-3 text-right">
                                                <a href="{{ route('proyectos.tasks.edit', [$proyecto, $task]) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline">Editar</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                    Volver a sprints
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
