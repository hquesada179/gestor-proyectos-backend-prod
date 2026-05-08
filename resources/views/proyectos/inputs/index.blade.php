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
                    {{ __('app.inputs_mod.project_inputs') }}
                </h2>
            </div>
            <a href="{{ route('proyectos.inputs.create', $proyecto) }}">
                <x-primary-button>{{ __('app.actions.new_input') }}</x-primary-button>
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

            @if ($inputs->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-sm">
                        {{ __('app.empty.no_inputs') }}
                        <a href="{{ route('proyectos.inputs.create', $proyecto) }}" class="text-indigo-600 hover:underline ml-1">{{ __('app.empty.add_first') }}</a>.
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.inputs_mod.title_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.inputs_mod.type_col') }}</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">{{ __('app.inputs_mod.registered_col') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($inputs as $input)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <a href="{{ route('proyectos.inputs.show', [$proyecto, $input]) }}" class="hover:text-indigo-600">
                                            {{ $input->titulo }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 capitalize">{{ $input->tipo }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $input->created_at->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('proyectos.inputs.edit', [$proyecto, $input]) }}" class="text-indigo-600 hover:underline text-xs mr-3">{{ __('app.actions.edit') }}</a>
                                        <form method="POST" action="{{ route('proyectos.inputs.destroy', [$proyecto, $input]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:underline text-xs"
                                                onclick="return confirm('¿Eliminar este insumo?')">
                                                {{ __('app.actions.delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($inputs->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $inputs->links() }}
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
