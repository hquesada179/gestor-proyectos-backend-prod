<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="mb-1 text-xs text-slate-500">
                <a href="{{ route('proyectos.inputs.show', [$proyecto, $input]) }}" class="hover:text-indigo-300">
                    {{ $proyecto->nombre }} / Insumos / {{ $input->titulo }}
                </a>
            </p>
            <h2 class="text-xl font-semibold leading-tight text-slate-100">
                Editar insumo
            </h2>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Datos del insumo</h3>
                    <p class="mt-1 text-sm text-slate-400">Actualiza la informacion registrada para este insumo.</p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('proyectos.inputs.update', [$proyecto, $input]) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="tipo" value="Tipo de insumo" />
                            <select id="tipo" name="tipo"
                                class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['documento', 'entrevista', 'observacion', 'reunion', 'otro'] as $opcion)
                                    <option value="{{ $opcion }}" {{ old('tipo', $input->tipo) === $opcion ? 'selected' : '' }}>
                                        {{ ucfirst($opcion) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('tipo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="titulo" value="Titulo" />
                            <x-text-input id="titulo" name="titulo" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('titulo', $input->titulo) }}"
                                required autofocus />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="contenido" value="Contenido" />
                            <textarea id="contenido" name="contenido"
                                rows="6"
                                class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">{{ old('contenido', $input->contenido) }}</textarea>
                            <x-input-error :messages="$errors->get('contenido')" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-3 border-t border-slate-700/60 pt-6">
                            <x-primary-button>Actualizar insumo</x-primary-button>
                            <a href="{{ route('proyectos.inputs.show', [$proyecto, $input]) }}">
                                <x-secondary-button type="button">Cancelar</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
