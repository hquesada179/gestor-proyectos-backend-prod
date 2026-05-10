<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.tasks.index', $proyecto) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }} / Tareas
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    {{ $task->titulo }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.tasks.edit', [$proyecto, $task]) }}">
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.tasks.destroy', [$proyecto, $task]) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('Eliminar esta tarea? Esta accion no se puede deshacer.')">
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
                    <h3 class="text-lg font-bold text-white">Detalle de la tarea</h3>
                    <p class="mt-1 text-sm text-slate-400">Informacion operativa asociada al proyecto.</p>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Estado</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->status->nombre ?? 'Sin estado' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Fecha limite</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->fecha_limite?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Historia de usuario</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->userStory->titulo ?? 'Sin historia asociada' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sprint</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->sprint->nombre ?? 'Sin sprint' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Responsable</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->assignedTo->name ?? 'Sin responsable' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registrada</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $task->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Descripcion</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-200">
                            {{ $task->descripcion ?: 'Sin descripcion registrada.' }}
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('proyectos.tasks.index', $proyecto) }}" class="inline-flex text-sm font-semibold text-indigo-300 hover:text-indigo-200">
                Volver a tareas
            </a>
        </div>
    </div>
</x-app-layout>
