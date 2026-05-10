<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 mb-1">
                <a href="{{ route('proyectos.requirements.show', [$proyecto, $requirement]) }}" class="hover:text-indigo-600">
                    {{ $proyecto->nombre }} › Requerimientos › {{ $requirement->titulo }}
                </a>
            </p>
            <h2 class="font-semibold text-xl text-slate-100 leading-tight">
                Editar requerimiento
            </h2>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">

                    <form method="POST" action="{{ route('proyectos.requirements.update', [$proyecto, $requirement]) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-4">
                            <x-input-label for="codigo" value="Código (opcional)" />
                            <x-text-input id="codigo" name="codigo" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('codigo', $requirement->codigo) }}"
                                placeholder="Ej: REQ-001" />
                            <x-input-error :messages="$errors->get('codigo')" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="titulo" value="Título" />
                            <x-text-input id="titulo" name="titulo" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('titulo', $requirement->titulo) }}"
                                required autofocus />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="descripcion" value="Descripción" />
                            <textarea id="descripcion" name="descripcion"
                                rows="5"
                                class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                                required>{{ old('descripcion', $requirement->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                        </div>

                        <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="tipo" value="Tipo" />
                                <select id="tipo" name="tipo"
                                    class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="funcional" {{ old('tipo', $requirement->tipo) === 'funcional' ? 'selected' : '' }}>Funcional</option>
                                    <option value="no_funcional" {{ old('tipo', $requirement->tipo) === 'no_funcional' ? 'selected' : '' }}>No funcional</option>
                                </select>
                                <x-input-error :messages="$errors->get('tipo')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="prioridad" value="Prioridad" />
                                <select id="prioridad" name="prioridad"
                                    class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach (['alta', 'media', 'baja'] as $opcion)
                                        <option value="{{ $opcion }}" {{ old('prioridad', $requirement->prioridad) === $opcion ? 'selected' : '' }}>
                                            {{ ucfirst($opcion) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('prioridad')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mt-6">
                            <x-primary-button>Actualizar requerimiento</x-primary-button>
                            <a href="{{ route('proyectos.requirements.show', [$proyecto, $requirement]) }}">
                                <x-secondary-button type="button">Cancelar</x-secondary-button>
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
