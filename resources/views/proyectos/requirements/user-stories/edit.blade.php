<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="mb-1 text-xs text-slate-500">
                <a href="{{ route('proyectos.requirements.user-stories.show', [$proyecto, $requirement, $userStory]) }}" class="hover:text-indigo-300">
                    {{ $proyecto->nombre }} / Requerimientos / {{ $requirement->titulo }} / {{ $userStory->titulo }}
                </a>
            </p>
            <h2 class="text-xl font-semibold leading-tight text-slate-100">
                Editar historia de usuario
            </h2>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Datos de la historia</h3>
                    <p class="mt-1 text-sm text-slate-400">Actualiza la necesidad y criterios asociados.</p>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('proyectos.requirements.user-stories.update', [$proyecto, $requirement, $userStory]) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="titulo" value="Titulo" />
                            <x-text-input id="titulo" name="titulo" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('titulo', $userStory->titulo) }}"
                                required autofocus />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="como_usuario" value="Como usuario..." />
                            <x-text-input id="como_usuario" name="como_usuario" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('como_usuario', $userStory->como_usuario) }}" />
                            <x-input-error :messages="$errors->get('como_usuario')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="quiero" value="Quiero..." />
                            <x-text-input id="quiero" name="quiero" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('quiero', $userStory->quiero) }}" />
                            <x-input-error :messages="$errors->get('quiero')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="para_poder" value="Para poder..." />
                            <x-text-input id="para_poder" name="para_poder" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('para_poder', $userStory->para_poder) }}" />
                            <x-input-error :messages="$errors->get('para_poder')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="criterios_aceptacion" value="Criterios de aceptacion" />
                            <textarea id="criterios_aceptacion" name="criterios_aceptacion"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">{{ old('criterios_aceptacion', $userStory->criterios_aceptacion) }}</textarea>
                            <x-input-error :messages="$errors->get('criterios_aceptacion')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="prioridad" value="Prioridad" />
                            <select id="prioridad" name="prioridad"
                                class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['alta', 'media', 'baja'] as $opcion)
                                    <option value="{{ $opcion }}" {{ old('prioridad', $userStory->prioridad) === $opcion ? 'selected' : '' }}>
                                        {{ ucfirst($opcion) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('prioridad')" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-3 border-t border-slate-700/60 pt-6">
                            <x-primary-button>Actualizar historia</x-primary-button>
                            <a href="{{ route('proyectos.requirements.user-stories.show', [$proyecto, $requirement, $userStory]) }}">
                                <x-secondary-button type="button">Cancelar</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
