<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel principal
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Bienvenida + acción principal --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Bienvenido, {{ Auth::user()->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Desde aquí gestionas tus proyectos y haces seguimiento de tu trabajo.
                    </p>
                </div>
                <a href="{{ route('proyectos.create') }}">
                    <x-primary-button>+ Nuevo proyecto</x-primary-button>
                </a>
            </div>

            {{-- Estadísticas globales --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <a href="{{ route('proyectos.index') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group block">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Mis proyectos</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $totalProyectos }}</p>
                    <p class="mt-1 text-xs text-gray-400 group-hover:text-indigo-500 transition">Ver todos →</p>
                </a>

                <a href="{{ route('mis-tareas') }}"
                   class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group block">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tareas pendientes</p>
                    <p class="mt-2 text-3xl font-bold text-yellow-500">{{ $tareasPendientes }}</p>
                    <p class="mt-1 text-xs text-gray-400 group-hover:text-yellow-500 transition">Ver mis tareas →</p>
                </a>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sprints en progreso</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $sprintsActivos }}</p>
                    <p class="mt-1 text-xs text-gray-400">En todos tus proyectos</p>
                </div>
            </div>

            {{-- Módulos del proyecto --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    Módulos del proyecto
                </h4>
                <p class="text-xs text-gray-400 mb-3">Selecciona un módulo y luego elige el proyecto.</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

                    @foreach ([
                        ['slug' => 'sprints',        'label' => 'Sprints',        'color' => 'blue',   'svg' => 'M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122'],
                        ['slug' => 'tareas',         'label' => 'Tareas',         'color' => 'yellow', 'svg' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'],
                        ['slug' => 'requerimientos', 'label' => 'Requerimientos', 'color' => 'purple', 'svg' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
                        ['slug' => 'insumos',        'label' => 'Insumos',        'color' => 'orange', 'svg' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                    ] as $modulo)
                        @php
                            $bgIcon  = "bg-{$modulo['color']}-50";
                            $txtIcon = "text-{$modulo['color']}-" . ($modulo['color'] === 'yellow' || $modulo['color'] === 'orange' ? '500' : '600');
                            $hoverBg = "hover:bg-{$modulo['color']}-50";
                        @endphp
                        <a href="{{ route('modulo.selector', $modulo['slug']) }}"
                           class="group bg-white shadow-sm sm:rounded-lg p-4 flex flex-col items-center gap-2 text-center border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all duration-150 cursor-pointer">
                            <div class="p-2 {{ $bgIcon }} rounded-lg group-hover:scale-110 transition-transform duration-150">
                                <svg class="w-5 h-5 {{ $txtIcon }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $modulo['svg'] }}"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 group-hover:text-indigo-700 transition-colors">{{ $modulo['label'] }}</p>
                            <span class="text-xs text-indigo-500 font-medium opacity-0 group-hover:opacity-100 transition-opacity -mt-1">
                                Abrir módulo →
                            </span>
                        </a>
                    @endforeach

                </div>
            </div>

            {{-- Proyectos recientes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Proyectos recientes
                    </h4>
                    <a href="{{ route('proyectos.index') }}"
                       class="text-xs text-indigo-600 hover:underline">
                        Ver todos →
                    </a>
                </div>

                @if ($proyectosRecientes->isEmpty())
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-sm text-gray-400">
                        Aún no tienes proyectos.
                        <a href="{{ route('proyectos.create') }}" class="text-indigo-600 hover:underline ml-1">
                            Crear el primero
                        </a>.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($proyectosRecientes as $proyecto)
                            <a href="{{ route('proyectos.show', $proyecto) }}"
                               class="bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md border border-transparent hover:border-indigo-100 transition flex flex-col gap-3">

                                {{-- Nombre --}}
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $proyecto->nombre }}</p>
                                    @if ($proyecto->descripcion)
                                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $proyecto->descripcion }}</p>
                                    @endif
                                </div>

                                {{-- Contadores de módulos --}}
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                                        </svg>
                                        {{ $proyecto->tasks_count }} {{ Str::plural('tarea', $proyecto->tasks_count) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122"/>
                                        </svg>
                                        {{ $proyecto->sprints_count }} {{ Str::plural('sprint', $proyecto->sprints_count) }}
                                    </span>
                                    <span class="ml-auto text-indigo-500 font-medium">Abrir →</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
