@once
    @push('styles')
        <style>
            .my-task-input {
                width: 100%;
                border-radius: 0.75rem;
                border: 1px solid rgba(148, 163, 184, 0.24);
                background: rgba(15, 23, 42, 0.92);
                color: #f8fafc;
                font-size: 0.875rem;
                outline: none;
                padding: 0.7rem 0.9rem;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            }

            .my-task-input::placeholder {
                color: #64748b;
            }

            .my-task-input:focus {
                border-color: rgba(99, 102, 241, 0.9);
                background: rgba(15, 23, 42, 1);
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
            }

            select.my-task-input {
                appearance: none;
                background-color: #0f172a;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.85rem center;
                padding-right: 2.4rem;
            }

            select.my-task-input option {
                background: #0f172a;
                color: #f8fafc;
            }
        </style>
    @endpush
@endonce

@php
    $isEdit = $task->exists;
    $action = $isEdit ? route('mis-tareas.update', $task) : route('mis-tareas.store');
    $selectedProject = old('proyecto_id', $task->proyecto_id);
    $selectedStatus = old('task_status_id', $task->task_status_id);
    $selectedAssigned = old('assigned_to', $task->assigned_to ?? Auth::id());
    $selectedSprint = old('sprint_id', $task->sprint_id);
    $selectedUserStory = old('user_story_id', $task->user_story_id);
@endphp

@if ($projects->isEmpty() && !$isEdit)
    <div class="glass-panel rounded-2xl p-12 text-center">
        <span class="material-symbols-outlined mx-auto block text-gray-600" style="font-size:52px;">folder_off</span>
        <h2 class="mt-4 text-lg font-bold text-white">No hay proyectos disponibles</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
            Para crear una tarea necesitas ser propietario o miembro activo de un proyecto.
        </p>
        <a href="{{ route('mis-tareas.index') }}"
           class="mt-6 inline-flex items-center rounded-xl border border-white/10 bg-surface px-5 py-2.5 text-sm font-bold text-gray-300 transition hover:bg-white/5">
            Volver a Mis tareas
        </a>
    </div>
@else
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        @if (!$canManageProjectTask)
            <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                Puedes actualizar el estado porque la tarea esta asignada a ti. Para editar campos del proyecto debes ser miembro activo o propietario.
            </div>

            <div>
                <label for="task_status_id" class="mb-2 block text-sm font-semibold text-gray-300">Estado</label>
                <select id="task_status_id" name="task_status_id" class="my-task-input dark-form-select">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" {{ (string) $selectedStatus === (string) $status->id ? 'selected' : '' }}>
                            {{ $status->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('task_status_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
        @else
            <div>
                <label for="titulo" class="mb-2 block text-sm font-semibold text-gray-300">
                    Titulo <span class="text-red-400">*</span>
                </label>
                <input id="titulo" name="titulo" type="text" class="my-task-input"
                       value="{{ old('titulo', $task->titulo) }}"
                       placeholder="Nombre de la tarea" required autofocus>
                @error('titulo') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="descripcion" class="mb-2 block text-sm font-semibold text-gray-300">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="5" class="my-task-input"
                          placeholder="Describe el alcance o contexto de la tarea">{{ old('descripcion', $task->descripcion) }}</textarea>
                @error('descripcion') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="proyecto_id" class="mb-2 block text-sm font-semibold text-gray-300">
                        Proyecto <span class="text-red-400">*</span>
                    </label>
                    <select id="proyecto_id" name="proyecto_id" class="my-task-input dark-form-select" required>
                        <option value="">Selecciona un proyecto</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ (string) $selectedProject === (string) $project->id ? 'selected' : '' }}>
                                {{ $project->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('proyecto_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="task_status_id" class="mb-2 block text-sm font-semibold text-gray-300">
                        Estado <span class="text-red-400">*</span>
                    </label>
                    <select id="task_status_id" name="task_status_id" class="my-task-input dark-form-select" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" {{ (string) $selectedStatus === (string) $status->id ? 'selected' : '' }}>
                                {{ $status->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('task_status_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="assigned_to" class="mb-2 block text-sm font-semibold text-gray-300">Responsable</label>
                    <select id="assigned_to" name="assigned_to" class="my-task-input dark-form-select">
                        <option value="">Sin responsable</option>
                        @foreach ($assignableUsers as $user)
                            @php $projectIds = $assignableUserProjects[$user->id] ?? []; @endphp
                            <option value="{{ $user->id }}"
                                    data-projects="{{ implode(',', $projectIds) }}"
                                    {{ (string) $selectedAssigned === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fecha_limite" class="mb-2 block text-sm font-semibold text-gray-300">Fecha limite</label>
                    <input id="fecha_limite" name="fecha_limite" type="date"
                           class="my-task-input [color-scheme:dark]"
                           value="{{ old('fecha_limite', $task->fecha_limite?->format('Y-m-d')) }}">
                    @error('fecha_limite') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="sprint_id" class="mb-2 block text-sm font-semibold text-gray-300">Sprint</label>
                    <select id="sprint_id" name="sprint_id" class="my-task-input dark-form-select">
                        <option value="">Sin sprint</option>
                        @foreach ($sprints as $sprint)
                            <option value="{{ $sprint->id }}"
                                    data-project="{{ $sprint->proyecto_id }}"
                                    {{ (string) $selectedSprint === (string) $sprint->id ? 'selected' : '' }}>
                                {{ $sprint->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('sprint_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="user_story_id" class="mb-2 block text-sm font-semibold text-gray-300">Historia de usuario</label>
                    <select id="user_story_id" name="user_story_id" class="my-task-input dark-form-select">
                        <option value="">Sin historia asociada</option>
                        @foreach ($userStories as $userStory)
                            <option value="{{ $userStory->id }}"
                                    data-project="{{ $userStory->requirement->proyecto_id ?? '' }}"
                                    {{ (string) $selectedUserStory === (string) $userStory->id ? 'selected' : '' }}>
                                {{ $userStory->titulo }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_story_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-6 sm:flex-row sm:justify-end">
            <a href="{{ $isEdit ? route('mis-tareas.show', $task) : route('mis-tareas.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-surface px-5 py-2.5 text-sm font-bold text-gray-300 transition hover:bg-white/5">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary-container px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-secondary-container/20 transition hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                {{ $isEdit ? 'Guardar cambios' : 'Guardar tarea' }}
            </button>
        </div>
    </form>
@endif

@push('scripts')
    <script>
        (function () {
            var projectSelect = document.getElementById('proyecto_id');
            if (!projectSelect) return;

            var scopedSelects = [
                document.getElementById('sprint_id'),
                document.getElementById('user_story_id'),
                document.getElementById('assigned_to')
            ].filter(Boolean);

            function optionMatches(option, projectId) {
                if (!option.value || !projectId) return true;

                if (option.dataset.project) {
                    return option.dataset.project === projectId;
                }

                if (option.dataset.projects) {
                    return option.dataset.projects.split(',').indexOf(projectId) !== -1;
                }

                return true;
            }

            function refreshOptions() {
                var projectId = projectSelect.value;

                scopedSelects.forEach(function (select) {
                    Array.prototype.forEach.call(select.options, function (option) {
                        var match = optionMatches(option, projectId);
                        option.hidden = !match;
                        option.disabled = !match;
                    });

                    if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
                        select.value = '';
                    }
                });
            }

            projectSelect.addEventListener('change', refreshOptions);
            refreshOptions();
        })();
    </script>
@endpush
