<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.show', $proyecto) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }}
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    {{ __('app.nav.tasks_board') }}
                </h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('proyectos.tasks.export', array_filter(['proyecto' => $proyecto->id] + request()->only(['estado', 'sprint', 'responsable']))) }}"
                    class="inline-flex items-center rounded-lg border border-slate-600 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                    {{ __('app.actions.export_csv') }}
                </a>
                <a href="{{ route('proyectos.tasks.create', $proyecto) }}">
                    <x-primary-button>{{ __('app.actions.new_task') }}</x-primary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($statuses->isNotEmpty() || $sprints->isNotEmpty() || $assignees->isNotEmpty())
                <div class="mb-4 overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-4">
                        <form method="GET" action="{{ route('proyectos.tasks.index', $proyecto) }}" class="flex flex-wrap items-end gap-3">
                            @if ($statuses->isNotEmpty())
                                <div>
                                    <label for="estado" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.tasks_mod.status_col') }}</label>
                                    <select id="estado" name="estado"
                                        class="dark-form-select rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
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
                                    <label for="sprint" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.form.sprint') }}</label>
                                    <select id="sprint" name="sprint"
                                        class="dark-form-select rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
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
                                    <label for="responsable" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.tasks_mod.responsible_col') }}</label>
                                    <select id="responsable" name="responsable"
                                        class="dark-form-select rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
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
                                    class="text-sm font-semibold text-slate-400 hover:text-slate-200 hover:underline">
                                    {{ __('app.actions.clear_filters') }}
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            @if ($tasks->isEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-6 text-sm text-slate-400">
                        @if (request()->hasAny(['estado', 'sprint']))
                            {{ __('app.empty.no_tasks_filter') }}
                            <a href="{{ route('proyectos.tasks.index', $proyecto) }}" class="ml-1 text-indigo-300 hover:underline">{{ __('app.actions.see_all') }}</a>.
                        @else
                            {{ __('app.empty.no_tasks_project') }}
                            <a href="{{ route('proyectos.tasks.create', $proyecto) }}" class="ml-1 text-indigo-300 hover:underline">{{ __('app.empty.add_first_task') }}</a>.
                        @endif
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.tasks_mod.title_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.tasks_mod.status_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.tasks_mod.responsible_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.tasks_mod.deadline_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.tasks_mod.registered_col') }}</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach ($tasks as $task)
                                    <tr class="transition hover:bg-slate-800/50">
                                        <td class="px-6 py-4 font-medium text-slate-100">
                                            <a href="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}" class="hover:text-indigo-300">
                                                {{ $task->titulo }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-300">{{ $task->status->nombre ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-300">{{ $task->assignedTo->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-300">{{ $task->fecha_limite?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-300">{{ $task->created_at->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('proyectos.tasks.edit', [$proyecto, $task]) }}" class="mr-3 text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline">{{ __('app.actions.edit') }}</a>
                                            <form method="POST" action="{{ route('proyectos.tasks.destroy', [$proyecto, $task]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs font-semibold text-red-400 hover:text-red-300 hover:underline"
                                                    onclick="return confirm('Eliminar esta tarea?')">
                                                    {{ __('app.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($tasks->hasPages())
                        <div class="border-t border-slate-800 px-6 py-4">
                            {{ $tasks->links() }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-4 text-sm">
                <a href="{{ route('proyectos.show', $proyecto) }}" class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                    {{ __('app.actions.back_project') }}
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
