<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary-container" style="font-size: 24px;">add_circle</span>
            <span class="text-sm font-medium text-gray-400">Nuevo proyecto</span>
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
        .form-input::placeholder { color: #3f424e; }
        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.07);
        }
        .form-input:focus {
            border-color: var(--color-secondary-container, #7c6af7);
            background: rgba(124, 106, 247, 0.07);
            box-shadow: 0 0 0 3px rgba(124, 106, 247, 0.15);
        }
        select.form-input option { background: #1a1a2e; color: #e2e4ec; }
        textarea.form-input { resize: none; }
    </style>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-white/5">

                <div class="bg-surface/50 p-6 border-b border-white/10">
                    <h3 class="text-xl font-bold text-white">Detalles del Proyecto</h3>
                    <p class="text-sm text-on-surface-variant mt-1">Completa la información básica para iniciar tu nuevo proyecto.</p>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('proyectos.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="nombre" class="block mb-2 text-sm font-medium text-gray-300">
                                Nombre del proyecto <span class="text-red-400">*</span>
                            </label>
                            <input id="nombre" name="nombre" type="text"
                                class="form-input"
                                value="{{ old('nombre') }}"
                                placeholder="Ej: Rediseño de sitio web"
                                required autofocus />
                            @error('nombre') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-300">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4"
                                class="form-input"
                                placeholder="Describe brevemente los objetivos y el alcance del proyecto...">{{ old('descripcion') }}</textarea>
                            @error('descripcion') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="estado" class="block mb-2 text-sm font-medium text-gray-300">Estado</label>
                            <select id="estado" name="estado" class="form-input" style="color: #000000;">
                                @foreach (['activo', 'pausado', 'completado', 'cancelado'] as $opcion)
                                    <option value="{{ $opcion }}" {{ old('estado', 'activo') === $opcion ? 'selected' : '' }}>
                                        {{ ucfirst($opcion) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estado') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="fecha_inicio" class="block mb-2 text-sm font-medium text-gray-300">Fecha de inicio</label>
                                <input id="fecha_inicio" name="fecha_inicio" type="date"
                                    class="form-input [color-scheme:dark]"
                                    value="{{ old('fecha_inicio') }}" />
                                @error('fecha_inicio') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="fecha_fin_estimada" class="block mb-2 text-sm font-medium text-gray-300">Fecha estimada de cierre</label>
                                <input id="fecha_fin_estimada" name="fecha_fin_estimada" type="date"
                                    class="form-input [color-scheme:dark]"
                                    value="{{ old('fecha_fin_estimada') }}" />
                                @error('fecha_fin_estimada') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/10 mt-8">
                            <a href="{{ route('proyectos.index') }}"
                               class="flex items-center gap-2 bg-surface border border-white/10 text-gray-400 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white/5 transition-all active:scale-95">
                                Cancelar
                            </a>
                            <button type="submit"
                               class="flex items-center gap-2 bg-secondary-container text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 transition-all active:scale-95 shadow-lg shadow-secondary-container/20">
                                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                                Guardar proyecto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>