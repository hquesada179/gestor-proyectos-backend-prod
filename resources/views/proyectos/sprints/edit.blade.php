<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="mb-1 text-xs text-slate-500">
                <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}" class="hover:text-indigo-300">
                    {{ $proyecto->nombre }} / Sprints / {{ $sprint->nombre }}
                </a>
            </p>
            <h2 class="text-xl font-semibold leading-tight text-slate-100">
                Editar sprint
            </h2>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Detalles del sprint</h3>
                    <p class="mt-1 text-sm text-slate-400">Actualiza el alcance, estado y fechas del ciclo.</p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('proyectos.sprints.update', [$proyecto, $sprint]) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('nombre', $sprint->nombre) }}"
                                required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="objetivo" value="Objetivo" />
                            <textarea id="objetivo" name="objetivo"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">{{ old('objetivo', $sprint->objetivo) }}</textarea>
                            <x-input-error :messages="$errors->get('objetivo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="estado" value="Estado" />
                            <select id="estado" name="estado"
                                class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['planificado' => 'Planificado', 'en_progreso' => 'En progreso', 'completado' => 'Completado'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('estado', $sprint->estado) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('estado')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                                <x-text-input id="fecha_inicio" name="fecha_inicio" type="date"
                                    class="mt-1 block w-full [color-scheme:dark]"
                                    value="{{ old('fecha_inicio', $sprint->fecha_inicio?->format('Y-m-d')) }}" />
                                <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="fecha_fin" value="Fecha de fin" />
                                <x-text-input id="fecha_fin" name="fecha_fin" type="date"
                                    class="mt-1 block w-full [color-scheme:dark]"
                                    value="{{ old('fecha_fin', $sprint->fecha_fin?->format('Y-m-d')) }}" />
                                <x-input-error :messages="$errors->get('fecha_fin')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 border-t border-slate-700/60 pt-6">
                            <x-primary-button>Actualizar sprint</x-primary-button>
                            <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}">
                                <x-secondary-button type="button">Cancelar</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
