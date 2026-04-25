<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">{{ $info['label'] }}</span>
        </div>
    </x-slot>

    <div class="p-8 space-y-6 max-w-[900px]">

        {{-- Page header --}}
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $info['label'] }}</h1>
            <p class="text-sm text-on-surface-variant mt-1">
                {{ $info['desc'] }} Elige el proyecto con el que quieres trabajar.
            </p>
        </div>

        @if ($proyectos->isEmpty())
            <div class="glass-panel rounded-2xl p-12 text-center">
                <span class="material-symbols-outlined text-gray-600 block mb-3" style="font-size: 40px;">folder_open</span>
                <p class="text-sm text-gray-500">Aún no tienes proyectos registrados.</p>
                <a href="{{ route('proyectos.create') }}"
                   class="mt-3 inline-block text-sm text-blue-400 hover:underline">
                    Crear tu primer proyecto →
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($proyectos as $proyecto)
                    <div class="glass-panel-hover rounded-2xl p-5 flex flex-col gap-4">
                        <div>
                            <p class="text-sm font-bold text-white">{{ $proyecto->nombre }}</p>
                            @if ($proyecto->descripcion)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $proyecto->descripcion }}</p>
                            @endif
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-white/5">
                            <span class="text-xs text-gray-500">
                                {{ $proyecto->{$info['conteo']} }}
                                {{ Str::plural($info['conteo_label'], $proyecto->{$info['conteo']}) }}
                                registrados
                            </span>
                            <a href="{{ route($info['ruta'], $proyecto) }}"
                               class="flex items-center gap-1.5 px-4 py-1.5 bg-secondary-container text-white text-xs font-bold rounded-xl hover:opacity-90 transition-all active:scale-95">
                                Entrar
                                <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pt-2">
            <a href="{{ route('dashboard') }}"
               class="text-sm text-gray-500 hover:text-gray-300 flex items-center gap-1 transition-colors w-fit">
                <span class="material-symbols-outlined" style="font-size: 15px;">arrow_back</span>
                Volver al panel
            </a>
        </div>

    </div>
</x-app-layout>
