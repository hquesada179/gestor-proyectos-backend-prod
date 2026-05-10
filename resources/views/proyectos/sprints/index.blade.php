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
                    {{ __('app.nav.sprints') }}
                </h2>
            </div>
            <a href="{{ route('proyectos.sprints.create', $proyecto) }}">
                <x-primary-button>{{ __('app.actions.new_sprint') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <form method="GET" action="{{ route('proyectos.sprints.index', $proyecto) }}"
                class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-700/60 bg-slate-900/80 p-4 shadow-xl shadow-black/20">
                <select name="estado" onchange="this.form.submit()"
                    class="dark-form-select rounded-lg border border-slate-600 bg-slate-800 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('app.sprints_mod.all_states') }}</option>
                    <option value="planificado"  {{ request('estado') === 'planificado'  ? 'selected' : '' }}>{{ __('app.status.planned') }}</option>
                    <option value="en_progreso"  {{ request('estado') === 'en_progreso'  ? 'selected' : '' }}>{{ __('app.status.in_progress') }}</option>
                    <option value="completado"   {{ request('estado') === 'completado'   ? 'selected' : '' }}>{{ __('app.status.completed') }}</option>
                </select>
                @if (request('estado'))
                    <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="text-xs font-semibold text-slate-400 hover:text-slate-200">{{ __('app.actions.clear') }}</a>
                @endif
            </form>

            @if ($sprints->isEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-6 text-sm text-slate-400">
                        @if (request('estado'))
                            {{ __('app.empty.no_sprints_filter') }}
                            <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="ml-1 text-indigo-300 hover:underline">{{ __('app.actions.see_all') }}</a>.
                        @else
                            {{ __('app.empty.no_sprints') }}
                            <a href="{{ route('proyectos.sprints.create', $proyecto) }}" class="ml-1 text-indigo-300 hover:underline">{{ __('app.empty.add_first') }}</a>.
                        @endif
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.sprints_mod.name_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.form.status') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.sprints_mod.tasks_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.sprints_mod.start_col') }}</th>
                                    <th class="px-6 py-4 text-left font-semibold">{{ __('app.sprints_mod.end_col') }}</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach ($sprints as $sprint)
                                    <tr class="transition hover:bg-slate-800/50">
                                        <td class="px-6 py-4 font-medium text-slate-100">
                                            <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}" class="hover:text-indigo-300">
                                                {{ $sprint->nombre }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $badgeClass = match($sprint->estado) {
                                                    'en_progreso' => 'border-blue-400/60 bg-blue-500/10 text-blue-200',
                                                    'completado'  => 'border-emerald-400/60 bg-emerald-500/10 text-emerald-200',
                                                    'planificado' => 'border-slate-500/70 bg-slate-800/70 text-slate-200',
                                                    default       => 'border-amber-400/60 bg-amber-500/10 text-amber-200',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center justify-center rounded-full border px-3 py-1 text-xs font-bold uppercase leading-none whitespace-nowrap {{ $badgeClass }}">
                                                {{ str_replace('_', ' ', $sprint->estado) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($sprint->tasks_count > 0)
                                                <span class="text-slate-200">{{ $sprint->tasks_count }}</span>
                                            @else
                                                <span class="text-xs text-slate-500">{{ __('app.sprints_mod.no_tasks') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-300">{{ $sprint->fecha_inicio?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-300">{{ $sprint->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('proyectos.sprints.edit', [$proyecto, $sprint]) }}" class="mr-3 text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline">{{ __('app.actions.edit') }}</a>
                                            <form method="POST" action="{{ route('proyectos.sprints.destroy', [$proyecto, $sprint]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs font-semibold text-red-400 hover:text-red-300 hover:underline"
                                                    onclick="return confirm('Eliminar este sprint?')">
                                                    {{ __('app.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
