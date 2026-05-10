<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">{{ __('app.nav.dashboard') }}</span>
    </x-slot>

    <div class="p-4 md:p-8 space-y-8 max-w-[1400px]">

        {{-- ── Bienvenida + CTA ─────────────────────────────────────── --}}
        <div class="glass-panel rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white">
                    {{ __('app.dashboard.welcome') }} {{ Auth::user()->name }}
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    {{ __('app.dashboard.subtitle') }}
                </p>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <a href="{{ route('asistente-ia.index') }}"
                   class="flex items-center justify-center gap-2 bg-surface text-white px-5 py-2.5 rounded-xl border border-white/10
                          font-bold text-sm hover:bg-white/5 transition-all active:scale-95 w-full sm:w-auto">
                    <span class="material-symbols-outlined text-secondary-container" style="font-size: 16px;">smart_toy</span>
                    {{ __('app.actions.create_with_ai') }}
                </a>
                <a href="{{ route('proyectos.create') }}"
                   class="flex items-center justify-center gap-2 bg-secondary-container text-white px-4 py-2.5 rounded-xl
                          font-bold text-sm hover:opacity-90 transition-all active:scale-95 w-full sm:w-auto">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    {{ __('app.actions.new_project') }}
                </a>
            </div>
        </div>

        {{-- ── Estadísticas ──────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <a href="{{ route('proyectos.index') }}"
               class="glass-panel-hover rounded-2xl p-6 block group">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                            {{ __('app.dashboard.my_projects') }}
                        </p>
                        <p class="mt-2 text-4xl font-black text-blue-400">{{ $totalProyectos }}</p>
                        <p class="mt-2 text-xs text-gray-500 group-hover:text-blue-400 transition-colors flex items-center gap-1">
                            {{ __('app.actions.see_all') }}
                            <span class="material-symbols-outlined" style="font-size: 13px;">arrow_forward</span>
                        </p>
                    </div>
                    <div class="p-3 bg-blue-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-blue-400" style="font-size: 22px;">folder_open</span>
                    </div>
                </div>
            </a>

            <a href="{{ route('mis-tareas.index') }}"
               class="glass-panel-hover rounded-2xl p-6 block group">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                            {{ __('app.dashboard.pending_tasks') }}
                        </p>
                        <p class="mt-2 text-4xl font-black text-yellow-400">{{ $tareasPendientes }}</p>
                        <p class="mt-2 text-xs text-gray-500 group-hover:text-yellow-400 transition-colors flex items-center gap-1">
                            {{ __('app.actions.see_my_tasks') }}
                            <span class="material-symbols-outlined" style="font-size: 13px;">arrow_forward</span>
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-yellow-400" style="font-size: 22px;">task_alt</span>
                    </div>
                </div>
            </a>

            <div class="glass-panel rounded-2xl p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                            {{ __('app.dashboard.active_sprints') }}
                        </p>
                        <p class="mt-2 text-4xl font-black text-green-400">{{ $sprintsActivos }}</p>
                        <p class="mt-2 text-xs text-gray-600">{{ __('app.dashboard.in_all_projects') }}</p>
                    </div>
                    <div class="p-3 bg-green-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-green-400" style="font-size: 22px;">sprint</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Módulos del proyecto ──────────────────────────────────── --}}
        <div>
            <div class="flex items-center gap-2 mb-4">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ __('app.dashboard.project_modules') }}</p>
                <p class="text-xs text-gray-600">{{ __('app.dashboard.select_module') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ([
                    ['slug' => 'sprints',        'label' => __('app.nav.sprints'),       'icon' => 'view_kanban',  'color' => 'text-blue-400',   'bg' => 'bg-blue-500/10'],
                    ['slug' => 'tareas',         'label' => __('app.nav.tasks_board'),   'icon' => 'task_alt',     'color' => 'text-yellow-400', 'bg' => 'bg-yellow-500/10'],
                    ['slug' => 'requerimientos', 'label' => __('app.nav.requirements'),  'icon' => 'edit_note',    'color' => 'text-purple-400', 'bg' => 'bg-purple-500/10'],
                    ['slug' => 'insumos',        'label' => __('app.nav.inputs'),        'icon' => 'inventory_2',  'color' => 'text-orange-400', 'bg' => 'bg-orange-500/10'],
                ] as $modulo)
                    <a href="{{ route('modulo.selector', $modulo['slug']) }}"
                       class="glass-panel rounded-2xl p-5 flex flex-col items-center gap-3 text-center
                              hover:bg-white/5 hover:scale-[1.02] transition-all duration-150 group cursor-pointer">
                        <div class="p-3 {{ $modulo['bg'] }} rounded-xl group-hover:scale-110 transition-transform duration-150">
                            <span class="material-symbols-outlined {{ $modulo['color'] }}" style="font-size: 24px;">{{ $modulo['icon'] }}</span>
                        </div>
                        <p class="text-sm font-semibold text-on-surface group-hover:text-white transition-colors">
                            {{ $modulo['label'] }}
                        </p>
                        <span class="text-[10px] text-blue-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity -mt-1">
                            {{ __('app.actions.open') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Proyectos recientes ───────────────────────────────────── --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                    {{ __('app.dashboard.recent_projects') }}
                </p>
                <a href="{{ route('proyectos.index') }}"
                   class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors">
                    {{ __('app.actions.see_all') }}
                    <span class="material-symbols-outlined" style="font-size: 13px;">arrow_forward</span>
                </a>
            </div>

            @if ($proyectosRecientes->isEmpty())
                <div class="glass-panel rounded-2xl p-10 text-center">
                    <span class="material-symbols-outlined text-gray-600 block mb-3" style="font-size: 40px;">folder_open</span>
                    <p class="text-sm text-gray-500">{{ __('app.dashboard.no_projects') }}</p>
                    <a href="{{ route('proyectos.create') }}" class="text-blue-400 hover:underline text-sm mt-1 inline-block">
                        {{ __('app.actions.create_first') }}
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($proyectosRecientes as $proyecto)
                        <a href="{{ route('proyectos.show', $proyecto) }}"
                           class="glass-panel-hover rounded-2xl p-5 flex flex-col gap-4 group">

                            <div>
                                <p class="text-sm font-bold text-white truncate group-hover:text-blue-300 transition-colors">
                                    {{ $proyecto->nombre }}
                                </p>
                                @if ($proyecto->descripcion)
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $proyecto->descripcion }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-4 pt-3 border-t border-white/5">
                                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">task_alt</span>
                                    {{ $proyecto->tasks_count }} {{ __('app.projects.tasks_short') }}
                                </span>
                                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">view_kanban</span>
                                    {{ $proyecto->sprints_count }} {{ __('app.projects.sprints_short') }}
                                </span>
                                <span class="ml-auto text-xs text-blue-400 font-semibold group-hover:underline">
                                    {{ __('app.actions.open') }}
                                </span>
                            </div>

                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
