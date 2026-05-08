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
                    {{ __('app.nav.sprints') }}
                </h2>
            </div>
            <a href="{{ route('proyectos.sprints.create', $proyecto) }}">
                <x-primary-button>{{ __('app.actions.new_sprint') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filtro por estado --}}
            <form method="GET" action="{{ route('proyectos.sprints.index', $proyecto) }}" class="mb-4 flex items-center gap-3">
                <select name="estado" onchange="this.form.submit()"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('app.sprints_mod.all_states') }}</option>
                    <option value="planificado"  {{ request('estado') === 'planificado'  ? 'selected' : '' }}>{{ __('app.status.planned') }}</option>
                    <option value="en_progreso"  {{ request('estado') === 'en_progreso'  ? 'selected' : '' }}>{{ __('app.status.in_progress') }}</option>
                    <option value="completado"   {{ request('estado') === 'completado'   ? 'selected' : '' }}>{{ __('app.status.completed') }}</option>
                </select>
                @if (request('estado'))
                    <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="text-xs text-gray-500 hover:text-gray-700">{{ __('app.actions.clear') }}</a>
                @endif
            </form>

            @if ($sprints->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-sm">
                        @if (request('estado'))
                            {{ __('app.empty.no_sprints_filter') }}
                            <a href="{{ route('proyectos.sprints.index', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">{{ __('app.actions.see_all') }}</a>.
                        @else
                            {{ __('app.empty.no_sprints') }}
                            <a href="{{ route('proyectos.sprints.create', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">{{ __('app.empty.add_first') }}</a>.
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.sprints_mod.name_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.form.status') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.sprints_mod.tasks_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.sprints_mod.start_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.sprints_mod.end_col') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($sprints as $sprint)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <a href="{{ route('proyectos.sprints.show', [$proyecto, $sprint]) }}" class="hover:text-indigo-600">
                                            {{ $sprint->nombre }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $badgeClass = match($sprint->estado) {
                                                'en_progreso' => 'bg-indigo-100 text-indigo-700',
                                                'completado'  => 'bg-green-100 text-green-700',
                                                'planificado' => 'bg-gray-100 text-gray-600',
                                                default       => 'bg-yellow-100 text-yellow-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $badgeClass }}">
                                            {{ str_replace('_', ' ', $sprint->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($sprint->tasks_count > 0)
                                            <span class="text-gray-700">{{ $sprint->tasks_count }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">{{ __('app.sprints_mod.no_tasks') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $sprint->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $sprint->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('proyectos.sprints.edit', [$proyecto, $sprint]) }}" class="text-indigo-600 hover:underline text-xs mr-3">{{ __('app.actions.edit') }}</a>
                                        <form method="POST" action="{{ route('proyectos.sprints.destroy', [$proyecto, $sprint]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:underline text-xs"
                                                onclick="return confirm('¿Eliminar este sprint?')">
                                                {{ __('app.actions.delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
