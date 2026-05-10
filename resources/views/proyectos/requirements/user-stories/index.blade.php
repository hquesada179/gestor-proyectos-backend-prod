<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.requirements.show', [$proyecto, $requirement]) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }} / Requerimientos / {{ $requirement->titulo }}
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    Historias de usuario
                </h2>
            </div>
            <a href="{{ route('proyectos.requirements.user-stories.create', [$proyecto, $requirement]) }}">
                <x-primary-button>Nueva historia</x-primary-button>
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

            @if ($userStories->isEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="p-6 text-sm text-slate-400">
                        Este requerimiento no tiene historias de usuario todavia.
                        <a href="{{ route('proyectos.requirements.user-stories.create', [$proyecto, $requirement]) }}" class="ml-1 text-indigo-300 hover:underline">Agregar la primera</a>.
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">Titulo</th>
                                    <th class="px-6 py-4 text-left font-semibold">Prioridad</th>
                                    <th class="px-6 py-4 text-left font-semibold">Registrado</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach ($userStories as $userStory)
                                    <tr class="transition hover:bg-slate-800/50">
                                        <td class="px-6 py-4 font-medium text-slate-100">
                                            <a href="{{ route('proyectos.requirements.user-stories.show', [$proyecto, $requirement, $userStory]) }}" class="hover:text-indigo-300">
                                                {{ $userStory->titulo }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 capitalize text-slate-300">{{ $userStory->prioridad }}</td>
                                        <td class="px-6 py-4 text-slate-300">{{ $userStory->created_at->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('proyectos.requirements.user-stories.edit', [$proyecto, $requirement, $userStory]) }}" class="mr-3 text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline">Editar</a>
                                            <form method="POST" action="{{ route('proyectos.requirements.user-stories.destroy', [$proyecto, $requirement, $userStory]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs font-semibold text-red-400 hover:text-red-300 hover:underline"
                                                    onclick="return confirm('Eliminar esta historia de usuario?')">
                                                    Eliminar
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
                <a href="{{ route('proyectos.requirements.show', [$proyecto, $requirement]) }}" class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                    Volver al requerimiento
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
