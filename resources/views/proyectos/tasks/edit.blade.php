<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 mb-1">
                <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}" class="hover:text-indigo-600">
                    {{ $proyecto->nombre }} › Tareas › {{ $task->titulo }}
                </a>
            </p>
            <h2 class="font-semibold text-xl text-slate-100 leading-tight">
                Editar tarea
            </h2>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="p-6">

                    <form method="POST" action="{{ route('proyectos.tasks.update', [$proyecto, $task]) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-4">
                            <x-input-label for="titulo" value="Título" />
                            <x-text-input id="titulo" name="titulo" type="text"
                                class="mt-1 block w-full"
                                value="{{ old('titulo', $task->titulo) }}"
                                required autofocus />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="descripcion" value="Descripción" />
                            <textarea id="descripcion" name="descripcion"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $task->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="task_status_id" value="Estado" />
                            <select id="task_status_id" name="task_status_id"
                                class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" {{ old('task_status_id', $task->task_status_id) == $status->id ? 'selected' : '' }}>
                                        {{ $status->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('task_status_id')" class="mt-1" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="fecha_limite" value="Fecha límite" />
                            <x-text-input id="fecha_limite" name="fecha_limite" type="date"
                                class="mt-1 block w-full"
                                value="{{ old('fecha_limite', $task->fecha_limite?->format('Y-m-d')) }}" />
                            <x-input-error :messages="$errors->get('fecha_limite')" class="mt-1" />
                        </div>

                        @if ($userStories->isNotEmpty())
                            <div class="mb-4">
                                <x-input-label for="user_story_id" value="Historia de usuario (opcional)" />
                                <select id="user_story_id" name="user_story_id"
                                    class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">— Sin historia asociada —</option>
                                    @foreach ($userStories as $userStory)
                                        <option value="{{ $userStory->id }}" {{ old('user_story_id', $task->user_story_id) == $userStory->id ? 'selected' : '' }}>
                                            {{ $userStory->titulo }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('user_story_id')" class="mt-1" />
                            </div>
                        @endif

                        @if ($sprints->isNotEmpty())
                            <div class="mb-4">
                                <x-input-label for="sprint_id" value="Sprint (opcional)" />
                                <select id="sprint_id" name="sprint_id"
                                    class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">— Sin sprint asociado —</option>
                                    @foreach ($sprints as $sprint)
                                        <option value="{{ $sprint->id }}" {{ old('sprint_id', $task->sprint_id) == $sprint->id ? 'selected' : '' }}>
                                            {{ $sprint->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('sprint_id')" class="mt-1" />
                            </div>
                        @endif

                        <div class="mb-4">
                            <x-input-label for="assigned_to" value="Responsable (opcional)" />
                            <select id="assigned_to" name="assigned_to"
                                class="dark-form-select mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— Sin responsable —</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assigned_to')" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-3 mt-6">
                            <x-primary-button>Actualizar tarea</x-primary-button>
                            <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}">
                                <x-secondary-button type="button">Cancelar</x-secondary-button>
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
