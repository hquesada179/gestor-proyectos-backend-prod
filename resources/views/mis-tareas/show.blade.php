<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 flex items-center gap-2 text-xs text-gray-500">
                    <a href="{{ route('mis-tareas.index') }}" class="transition-colors hover:text-secondary-container">Mis tareas</a>
                    <span>/</span>
                    <span>{{ $task->proyecto->nombre ?? 'Proyecto' }}</span>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-gray-300">{{ $task->titulo }}</h2>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($canEdit)
                    <a href="{{ route('mis-tareas.edit', $task) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-surface px-4 py-2 text-sm font-bold text-gray-300 transition hover:bg-white/5">
                        <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                        Editar
                    </a>
                @endif

                @if ($canDelete)
                    <form method="POST" action="{{ route('mis-tareas.destroy', $task) }}"
                          onsubmit="return confirm('Eliminar esta tarea? Esta accion no se puede deshacer.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-red-500/10 px-4 py-2 text-sm font-bold text-red-300 ring-1 ring-red-500/25 transition hover:bg-red-500/20">
                            <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                            Eliminar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $statusName = strtolower($task->status->nombre ?? '');
        $isDone = str_contains($statusName, 'complet') || str_contains($statusName, 'hecho');
        $isOverdue = $task->fecha_limite && $task->fecha_limite->isPast() && !$isDone;
        $statusBadgeClass = 'inline-flex shrink-0 self-start items-center justify-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase leading-none tracking-wide whitespace-nowrap align-middle';
        $statusClass = match (true) {
            str_contains($statusName, 'complet') || str_contains($statusName, 'hecho') => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
            str_contains($statusName, 'progreso') => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
            str_contains($statusName, 'revision') || str_contains($statusName, 'revisi') => 'border-yellow-400/60 bg-yellow-500/10 text-yellow-200',
            str_contains($statusName, 'pendiente') => 'border-slate-500 bg-slate-800/60 text-slate-200',
            default => 'bg-white/5 text-gray-400 border-white/10',
        };
    @endphp

    <div class="p-8">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="glass-panel rounded-xl border-emerald-500/30 px-5 py-3 text-sm text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif

                <section class="glass-panel rounded-2xl p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Tarea</p>
                            <h1 class="mt-2 break-words text-2xl font-black text-white">{{ $task->titulo }}</h1>
                        </div>
                        @if ($task->status)
                            <span class="{{ $statusBadgeClass }} {{ $statusClass }}">{{ $task->status->nombre }}</span>
                        @endif
                    </div>

                    <div class="mt-6 border-t border-white/10 pt-6">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Descripcion</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-300">
                            {{ $task->descripcion ?: 'Sin descripcion registrada.' }}
                        </p>
                    </div>
                </section>

                <section class="glass-panel rounded-2xl p-6">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Contexto</p>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-xs text-gray-500">Proyecto</p>
                            <a href="{{ $task->proyecto ? route('proyectos.show', $task->proyecto) : '#' }}"
                               class="mt-1 block text-sm font-semibold text-blue-300 hover:text-blue-200">
                                {{ $task->proyecto->nombre ?? 'Sin proyecto' }}
                            </a>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-xs text-gray-500">Sprint</p>
                            <p class="mt-1 text-sm font-semibold text-gray-200">{{ $task->sprint->nombre ?? 'Sin sprint' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-xs text-gray-500">Historia de usuario</p>
                            <p class="mt-1 text-sm font-semibold text-gray-200">{{ $task->userStory->titulo ?? 'Sin historia asociada' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-xs text-gray-500">Responsable</p>
                            <p class="mt-1 text-sm font-semibold text-gray-200">{{ $task->assignedTo->name ?? 'Sin responsable' }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="glass-panel rounded-2xl p-6">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Fechas</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-xs text-gray-500">Fecha limite</p>
                            <p class="mt-1 inline-flex items-center gap-2 text-sm font-semibold {{ $isOverdue ? 'text-red-300' : 'text-gray-200' }}">
                                <span class="material-symbols-outlined" style="font-size:17px;">{{ $isOverdue ? 'event_busy' : 'calendar_today' }}</span>
                                {{ $task->fecha_limite?->format('d/m/Y') ?? 'Sin fecha' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Registrada</p>
                            <p class="mt-1 text-sm font-semibold text-gray-200">{{ $task->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ultima actualizacion</p>
                            <p class="mt-1 text-sm font-semibold text-gray-200">{{ $task->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </section>

                <section class="glass-panel rounded-2xl p-6">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Permisos</p>
                    <div class="mt-4 space-y-3 text-sm text-gray-400">
                        <p class="flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5 text-emerald-400" style="font-size:16px;">visibility</span>
                            Puedes ver esta tarea porque esta asignada a ti o pertenece a un proyecto accesible.
                        </p>
                        <p class="flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5 {{ $canDelete ? 'text-emerald-400' : 'text-amber-400' }}" style="font-size:16px;">
                                {{ $canDelete ? 'delete' : 'lock' }}
                            </span>
                            {{ $canDelete ? 'Puedes eliminarla porque esta asignada a ti o pertenece a un proyecto accesible.' : 'No tienes permiso para eliminar esta tarea.' }}
                        </p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
