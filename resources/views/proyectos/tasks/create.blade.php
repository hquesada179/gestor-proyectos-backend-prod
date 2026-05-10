<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('proyectos.tasks.index', $proyecto) }}" class="hover:text-secondary-container transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_back</span>
                    {{ $proyecto->nombre }}
                </a>
                <span>/</span>
                <span>Tareas</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-secondary-container" style="font-size: 24px;">task</span>
                <h2 class="font-semibold text-xl text-gray-300 leading-tight">
                    Nueva tarea
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
        .form-input::placeholder { color: #64748b; }
        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.07);
        }
        .form-input:focus {
            border-color: var(--color-secondary-container, #7c6af7);
            background: rgba(124, 106, 247, 0.07);
            box-shadow: 0 0 0 3px rgba(124, 106, 247, 0.15);
        }
        textarea.form-input { resize: none; }

        select.form-input {
            color: #e2e4ec !important;
            background-color: #1e1f2e;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.875rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        select.form-input option {
            background-color: #1e1f2e;
            color: #e2e4ec;
        }
    </style>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-white/5">

                <div class="bg-surface/50 p-6 border-b border-white/10">
                    <h3 class="text-xl font-bold text-white">Detalles de la Tarea</h3>
                    <p class="text-sm text-on-surface-variant mt-1">Asigna y describe las actividades pendientes.</p>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('proyectos.tasks.store', $proyecto) }}" class="space-y-6">
                        @csrf
                        @php
                            $defaultStatusId = $statuses->first(fn ($status) => str_contains(strtolower($status->nombre), 'pendiente'))?->id
                                ?? $statuses->first()?->id;
                            $selectedStatusId = old('task_status_id', $defaultStatusId);
                        @endphp

                        <div>
                            <label for="titulo" class="block mb-2 text-sm font-medium text-gray-300">
                                Título <span class="text-red-400">*</span>
                            </label>
                            <input id="titulo" name="titulo" type="text"
                                class="form-input"
                                value="{{ old('titulo') }}"
                                placeholder="Nombre de la tarea"
                                required autofocus />
                            @error('titulo') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-300">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4"
                                class="form-input"
                                placeholder="Describe la tarea en detalle...">{{ old('descripcion') }}</textarea>
                            @error('descripcion') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="task_status_id" class="block mb-2 text-sm font-medium text-gray-300">
                                    Estado <span class="text-red-400">*</span>
                                </label>
                                <select id="task_status_id" name="task_status_id" class="form-input dark-form-select" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->id }}" {{ (string) $selectedStatusId === (string) $status->id ? 'selected' : '' }}>
                                            {{ $status->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('task_status_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="fecha_limite" class="block mb-2 text-sm font-medium text-gray-300">Fecha límite</label>
                                <input id="fecha_limite" name="fecha_limite" type="date"
                                    class="form-input [color-scheme:dark]"
                                    value="{{ old('fecha_limite') }}" />
                                @error('fecha_limite') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-white/5">
                            @if ($userStories->isNotEmpty())
                                <div>
                                    <label for="user_story_id" class="block mb-2 text-sm font-medium text-gray-300">
                                        Historia de usuario <span class="text-gray-600 text-xs font-normal">(Opcional)</span>
                                    </label>
                                    <select id="user_story_id" name="user_story_id" class="form-input dark-form-select">
                                        <option value="">— Sin historia asociada —</option>
                                        @foreach ($userStories as $userStory)
                                            <option value="{{ $userStory->id }}" {{ old('user_story_id') == $userStory->id ? 'selected' : '' }}>
                                                {{ $userStory->titulo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_story_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if ($sprints->isNotEmpty())
                                <div>
                                    <label for="sprint_id" class="block mb-2 text-sm font-medium text-gray-300">
                                        Sprint <span class="text-gray-600 text-xs font-normal">(Opcional)</span>
                                    </label>
                                    <select id="sprint_id" name="sprint_id" class="form-input dark-form-select">
                                        <option value="">— Sin sprint asociado —</option>
                                        @foreach ($sprints as $sprint)
                                            <option value="{{ $sprint->id }}" {{ old('sprint_id') == $sprint->id ? 'selected' : '' }}>
                                                {{ $sprint->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sprint_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="assigned_to" class="block mb-2 text-sm font-medium text-gray-300">
                                Responsable <span class="text-gray-600 text-xs font-normal">(Opcional)</span>
                            </label>
                            <select id="assigned_to" name="assigned_to" class="form-input dark-form-select">
                                <option value="">— Sin responsable —</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/10 mt-8">
                            <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                               class="flex items-center gap-2 bg-surface border border-white/10 text-gray-400 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white/5 transition-all active:scale-95">
                                Cancelar
                            </a>
                            <button type="submit"
                               class="flex items-center gap-2 bg-secondary-container text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 transition-all active:scale-95 shadow-lg shadow-secondary-container/20">
                                <span class="material-symbols-outlined" style="font-size: 18px;">save</span>
                                Guardar tarea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
