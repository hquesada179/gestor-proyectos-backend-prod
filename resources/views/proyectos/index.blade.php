<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Proyectos</span>
    </x-slot>

    <div class="p-8 space-y-6 max-w-[1400px]">

        {{-- ── Page header ──────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Mis Proyectos</h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    {{ $proyectos->count() }} {{ Str::plural('proyecto', $proyectos->count()) }} registrado{{ $proyectos->count() !== 1 ? 's' : '' }}
                </p>
            </div>
            <a href="{{ route('proyectos.create') }}"
               class="flex items-center gap-2 bg-secondary-container text-white px-5 py-2.5 rounded-xl
                      font-bold text-sm hover:opacity-90 transition-all active:scale-95 w-fit flex-shrink-0">
                <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                Nuevo proyecto
            </a>
        </div>

        {{-- ── Flash success ──────────────────────────────────────── --}}
        @if (session('success'))
            <div class="glass-panel border-green-500/30 rounded-xl px-5 py-3.5 flex items-center gap-3 text-sm text-green-400">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size: 18px; font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">check_circle</span>
                {{ session('success') }}
            </div>
        @endif

        {{-- ── Empty state ──────────────────────────────────────────── --}}
        @if ($proyectos->isEmpty())
            <div class="glass-panel rounded-2xl p-14 text-center">
                <span class="material-symbols-outlined text-gray-600 block mb-4" style="font-size: 48px;">folder_open</span>
                <p class="text-sm text-gray-500 mb-3">No tienes proyectos registrados todavía.</p>
                <a href="{{ route('proyectos.create') }}"
                   class="text-blue-400 hover:underline text-sm inline-flex items-center gap-1">
                    Crear el primero
                    <span class="material-symbols-outlined" style="font-size: 13px;">arrow_forward</span>
                </a>
            </div>

        {{-- ── Projects table ────────────────────────────────────────── --}}
        @else
            <div class="glass-panel rounded-2xl overflow-hidden">
                <table class="ds-table w-full">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Resumen</th>
                            <th>Inicio</th>
                            <th>Estimado</th>
                            <th class="px-6 py-4 border-b border-white/5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proyectos as $proyecto)
                            <tr class="group">
                                <td>
                                    <a href="{{ route('proyectos.show', $proyecto) }}"
                                       class="text-sm font-semibold text-on-surface hover:text-blue-400 transition-colors">
                                        {{ $proyecto->nombre }}
                                    </a>
                                    @if ($proyecto->descripcion)
                                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-1 max-w-[240px]">
                                            {{ $proyecto->descripcion }}
                                        </p>
                                    @endif
                                </td>

                                <td>
                                    @php
                                        $estado = strtolower($proyecto->estado ?? '');
                                        $estadoClass = match(true) {
                                            in_array($estado, ['activo', 'active', 'en progreso', 'en_progreso']) =>
                                                'bg-green-500/10 text-green-400 border-green-500/20',
                                            in_array($estado, ['pendiente', 'nuevo']) =>
                                                'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                            in_array($estado, ['completado', 'cerrado', 'finalizado']) =>
                                                'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                            in_array($estado, ['cancelado', 'archivado']) =>
                                                'bg-red-500/10 text-red-400 border-red-500/20',
                                            default =>
                                                'bg-white/5 text-gray-400 border-white/10',
                                        };
                                    @endphp
                                    <span class="status-badge {{ $estadoClass }}">
                                        {{ $proyecto->estado ?? '—' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">task_alt</span>
                                            {{ $proyecto->tasks_count }} tareas
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">view_kanban</span>
                                            {{ $proyecto->sprints_count }} sprints
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">edit_note</span>
                                            {{ $proyecto->requirements_count }} req.
                                        </span>
                                    </div>
                                </td>

                                <td class="text-xs text-gray-400">
                                    {{ $proyecto->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td class="text-xs text-gray-400">
                                    {{ $proyecto->fecha_fin_estimada?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('proyectos.edit', $proyecto) }}"
                                           class="text-xs text-blue-400 hover:underline flex items-center gap-1">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">edit</span>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('proyectos.destroy', $proyecto) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-xs text-red-400 hover:underline flex items-center gap-1"
                                                onclick="return confirm('¿Eliminar el proyecto «{{ addslashes($proyecto->nombre) }}»? Esta acción no se puede deshacer.')">
                                                <span class="material-symbols-outlined" style="font-size: 13px;">delete</span>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</x-app-layout>
