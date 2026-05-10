<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.requirements.index', $proyecto) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }} / Requerimientos
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    {{ $requirement->titulo }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.requirements.edit', [$proyecto, $requirement]) }}">
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.requirements.destroy', [$proyecto, $requirement]) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('Eliminar este requerimiento? Esta accion no se puede deshacer.')">
                        Eliminar
                    </x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="p-8">
        <div class="mx-auto max-w-4xl space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Detalle del requerimiento</h3>
                    <p class="mt-1 text-sm text-slate-400">Especificacion funcional o no funcional del proyecto.</p>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    @if ($requirement->codigo)
                        <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Codigo</p>
                            <p class="mt-2 font-mono text-sm font-semibold text-white">{{ $requirement->codigo }}</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tipo</p>
                        <p class="mt-2 text-sm font-semibold text-white">
                            {{ $requirement->tipo === 'no_funcional' ? 'No funcional' : 'Funcional' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Prioridad</p>
                        <p class="mt-2 text-sm font-semibold capitalize text-white">{{ $requirement->prioridad }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registrado</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $requirement->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Descripcion</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-200">{{ $requirement->descripcion }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-700/60 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Elementos relacionados</h3>
                <a href="{{ route('proyectos.requirements.user-stories.index', [$proyecto, $requirement]) }}"
                   class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                    Historias de usuario
                </a>
            </div>

            <a href="{{ route('proyectos.requirements.index', $proyecto) }}" class="inline-flex text-sm font-semibold text-indigo-300 hover:text-indigo-200">
                Volver a los requerimientos
            </a>
        </div>
    </div>
</x-app-layout>
