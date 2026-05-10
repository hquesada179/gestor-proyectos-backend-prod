<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mb-1 text-xs text-slate-500">
                    <a href="{{ route('proyectos.inputs.index', $proyecto) }}" class="hover:text-indigo-300">
                        {{ $proyecto->nombre }} / Insumos
                    </a>
                </p>
                <h2 class="text-xl font-semibold leading-tight text-slate-100">
                    {{ $input->titulo }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proyectos.inputs.edit', [$proyecto, $input]) }}">
                    <x-secondary-button>Editar</x-secondary-button>
                </a>
                <form method="POST" action="{{ route('proyectos.inputs.destroy', [$proyecto, $input]) }}">
                    @csrf
                    @method('DELETE')
                    <x-danger-button onclick="return confirm('Eliminar este insumo? Esta accion no se puede deshacer.')">
                        Eliminar
                    </x-danger-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12 text-slate-100">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-900/80 shadow-xl shadow-black/20">
                <div class="border-b border-slate-700/60 bg-slate-800/50 px-6 py-5">
                    <h3 class="text-lg font-bold text-white">Detalle del insumo</h3>
                    <p class="mt-1 text-sm text-slate-400">Informacion registrada como insumo del proyecto.</p>
                </div>

                <div class="grid gap-4 p-6">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tipo</p>
                        <p class="mt-2 text-sm font-semibold capitalize text-white">{{ $input->tipo }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contenido</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-200">
                            {{ $input->contenido ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registrado</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $input->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('proyectos.inputs.index', $proyecto) }}" class="font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                    Volver a los insumos
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
