<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Panel principal</a>
                › {{ $info['label'] }}
            </p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $info['label'] }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Instrucción --}}
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-800">Selecciona un proyecto</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $info['desc'] }} Elige el proyecto con el que quieres trabajar.
                </p>
            </div>

            @if ($proyectos->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                    <p class="text-gray-500 text-sm">Aún no tienes proyectos registrados.</p>
                    <a href="{{ route('proyectos.create') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">
                        Crear tu primer proyecto →
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($proyectos as $proyecto)
                        <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col gap-4 border border-transparent hover:border-indigo-100 hover:shadow-md transition">

                            {{-- Nombre y descripción --}}
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $proyecto->nombre }}</p>
                                @if ($proyecto->descripcion)
                                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $proyecto->descripcion }}</p>
                                @endif
                            </div>

                            {{-- Conteo del módulo --}}
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    {{ $proyecto->{$info['conteo']} }}
                                    {{ Str::plural($info['conteo_label'], $proyecto->{$info['conteo']}) }}
                                    registrados
                                </span>

                                <a href="{{ route($info['ruta'], $proyecto) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 transition">
                                    Entrar
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 text-sm">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline">← Volver al panel</a>
            </div>

        </div>
    </div>
</x-app-layout>
