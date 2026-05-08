<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">
                    <a href="{{ route('proyectos.show', $proyecto) }}" class="hover:text-indigo-600">
                        {{ $proyecto->nombre }}
                    </a>
                </p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('app.nav.tasks_board') }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.tasks.export', array_filter(['proyecto' => $proyecto->id] + request()->only(['estado', 'sprint', 'responsable']))) }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition">
                    {{ __('app.actions.export_csv') }}
                </a>
                <a href="{{ route('proyectos.tasks.create', $proyecto) }}">
                    <x-primary-button>{{ __('app.actions.new_task') }}</x-primary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($statuses->isNotEmpty() || $sprints->isNotEmpty() || $assignees->isNotEmpty())
                <div class="mb-4 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <form method="GET" action="{{ route('proyectos.tasks.index', $proyecto) }}" class="flex flex-wrap items-end gap-3">

                            @if ($statuses->isNotEmpty())
                                <div>
                                    <label for="estado" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.tasks_mod.status_col') }}</label>
                                    <select id="estado" name="estado"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">{{ __('app.form.all') }}</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}" {{ request('estado') == $status->id ? 'selected' : '' }}>
                                                {{ $status->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($sprints->isNotEmpty())
                                <div>
                                    <label for="sprint" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.form.sprint') }}</label>
                                    <select id="sprint" name="sprint"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">{{ __('app.form.all') }}</option>
                                        <option value="sin_sprint" {{ request('sprint') === 'sin_sprint' ? 'selected' : '' }}>{{ __('app.status.no_sprint') }}</option>
                                        @foreach ($sprints as $sprint)
                                            <option value="{{ $sprint->id }}" {{ request('sprint') == $sprint->id ? 'selected' : '' }}>
                                                {{ $sprint->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($assignees->isNotEmpty())
                                <div>
                                    <label for="responsable" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.tasks_mod.responsible_col') }}</label>
                                    <select id="responsable" name="responsable"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">{{ __('app.form.all') }}</option>
                                        <option value="sin_responsable" {{ request('responsable') === 'sin_responsable' ? 'selected' : '' }}>{{ __('app.status.no_responsible') }}</option>
                                        @foreach ($assignees as $assignee)
                                            <option value="{{ $assignee->id }}" {{ request('responsable') == $assignee->id ? 'selected' : '' }}>
                                                {{ $assignee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <x-primary-button type="submit">{{ __('app.actions.filter') }}</x-primary-button>

                            @if (request()->hasAny(['estado', 'sprint', 'responsable']))
                                <a href="{{ route('proyectos.tasks.index', $proyecto) }}"
                                    class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                                    {{ __('app.actions.clear_filters') }}
                                </a>
                            @endif

                        </form>
                    </div>
                </div>
            @endif

            @if ($tasks->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-sm">
                        @if (request()->hasAny(['estado', 'sprint']))
                            {{ __('app.empty.no_tasks_filter') }}
                            <a href="{{ route('proyectos.tasks.index', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">{{ __('app.actions.see_all') }}</a>.
                        @else
                            {{ __('app.empty.no_tasks_project') }}
                            <a href="{{ route('proyectos.tasks.create', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">{{ __('app.empty.add_first_task') }}</a>.
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.tasks_mod.title_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.tasks_mod.status_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.tasks_mod.responsible_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.tasks_mod.deadline_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.tasks_mod.registered_col') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($tasks as $task)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}" class="hover:text-indigo-600">
                                            {{ $task->titulo }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $task->status->nombre ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $task->assignedTo->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $task->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $task->created_at->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('proyectos.tasks.edit', [$proyecto, $task]) }}" class="text-indigo-600 hover:underline text-xs mr-3">{{ __('app.actions.edit') }}</a>
                                        <form method="POST" action="{{ route('proyectos.tasks.destroy', [$proyecto, $task]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:underline text-xs"
                                                onclick="return confirm('¿Eliminar esta tarea?')">
                                                {{ __('app.actions.delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($tasks->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $tasks->links() }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-4 text-sm">
                <a href="{{ route('proyectos.show', $proyecto) }}" class="text-indigo-600 hover:underline">
                    {{ __('app.actions.back_project') }}
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
