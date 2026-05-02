<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('proyectos.requirements.index', $proyecto) }}" class="hover:text-secondary-container transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_back</span>
                    {{ $proyecto->nombre }}
                </a>
                <span>/</span>
                <span>Requerimientos</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-secondary-container" style="font-size: 24px;">note_add</span>
                <h2 class="font-semibold text-xl text-gray-300 leading-tight">
                    Nuevo requerimiento
                </h2>
            </div>
        </div>
    </x-slot>

    <style>
        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e4ec;
            font-size: 0.875rem;
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }

        .form-input::placeholder {
            color: #3f424e;
        }

        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.07);
        }

        .form-input:focus {
            border-color: var(--color-secondary-container, #7c6af7);
            background: rgba(124, 106, 247, 0.07);
            box-shadow: 0 0 0 3px rgba(124, 106, 247, 0.15);
        }

        select.form-input option {
            background: #1a1a2e;
            color: #e2e4ec;
        }

        textarea.form-input {
            resize: none;
        }
    </style>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-white/5">

                <div class="bg-surface/50 p-6 border-b border-white/10">
                    <h3 class="text-xl font-bold text-white">Detalles del Requerimiento</h3>
                    <p class="text-sm text-on-surface-variant mt-1">Define las especificaciones y características necesarias.</p>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('proyectos.requirements.store', $proyecto) }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="codigo" class="block mb-2 text-sm font-medium text-gray-300">
                                Código <span class="text-gray-600 text-xs font-normal">(Opcional)</span>
                            </label>
                            <input id="codigo" name="codigo" type="text"
                                class="form-input"
                                value="{{ old('codigo') }}"
                                placeholder="Ej: REQ-001" />
                            @error('codigo') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="titulo" class="block mb-2 text-sm font-medium text-gray-300">
                                Título <span class="text-red-400">*</span>
                            </label>
                            <input id="titulo" name="titulo" type="text"
                                class="form-input"
                                value="{{ old('titulo') }}"
                                placeholder="Nombre del requerimiento"
                                required autofocus />
                            @error('titulo') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-300">
                                Descripción <span class="text-red-400">*</span>
                            </label>
                            <textarea id="descripcion" name="descripcion" rows="5"
                                class="form-input"
                                placeholder="Describe el requerimiento en detalle..."
                                required>{{ old('descripcion') }}</textarea>
                            @error('descripcion') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="tipo" class="block mb-2 text-sm font-medium text-gray-300">Tipo</label>
                                <select id="tipo" name="tipo" class="form-input">
                                    <option value="funcional" {{ old('tipo', 'funcional') === 'funcional' ? 'selected' : '' }}>Funcional</option>
                                    <option value="no_funcional" {{ old('tipo') === 'no_funcional' ? 'selected' : '' }}>No funcional</option>
                                </select>
                                @error('tipo') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="prioridad" class="block mb-2 text-sm font-medium text-gray-300">Prioridad</label>
                                <select id="prioridad" name="prioridad" class="form-input">
                                    @foreach (['alta', 'media', 'baja'] as $opcion)
                                        <option value="{{ $opcion }}" {{ old('prioridad', 'media') === $opcion ? 'selected' : '' }}>
                                            {{ ucfirst($opcion) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('prioridad') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/10 mt-8">
                            <a href="{{ route('proyectos.requirements.index', $proyecto) }}"
                               class="flex items-center gap-2 bg-surface border border-white/10 text-gray-400 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white/5 transition-all active:scale-95">
                                Cancelar
                            </a>
                            <button type="submit"
                               class="flex items-center gap-2 bg-secondary-container text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 transition-all active:scale-95 shadow-lg shadow-secondary-container/20">
                                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                                Guardar requerimiento
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>