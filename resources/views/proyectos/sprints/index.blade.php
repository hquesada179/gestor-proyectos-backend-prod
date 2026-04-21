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
                    Sprints
                </h2>
            </div>
            <a href="{{ route('proyectos.sprints.create', $proyecto) }}">
                <x-primary-button>Nuevo sprint</x-primary-button>
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

            @if ($sprints->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-sm">
                        Este proyecto no tiene sprints todavía.
                        <a href="{{ route('proyectos.sprints.create', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">Agregar el primero</a>.
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Tareas</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Fin</th>
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
                                                'activo'     => 'bg-indigo-100 text-indigo-700',
                                                'finalizado',
                                                'completado' => 'bg-green-100 text-green-700',
                                                'planificado'=> 'bg-gray-100 text-gray-600',
                                                default      => 'bg-yellow-100 text-yellow-700',
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
                                            <span class="text-gray-400 text-xs">Sin tareas</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $sprint->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $sprint->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('proyectos.sprints.edit', [$proyecto, $sprint]) }}" class="text-indigo-600 hover:underline text-xs mr-3">Editar</a>
                                        <form method="POST" action="{{ route('proyectos.sprints.destroy', [$proyecto, $sprint]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:underline text-xs"
                                                onclick="return confirm('¿Eliminar este sprint?')">
                                                Eliminar
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
                    ← Volver al proyecto
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
