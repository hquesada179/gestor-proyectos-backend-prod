<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <div class="mb-1 flex items-center gap-2 text-xs text-gray-500">
                <a href="{{ route('mis-tareas.index') }}" class="flex items-center gap-1 transition-colors hover:text-secondary-container">
                    <span class="material-symbols-outlined" style="font-size:14px;">arrow_back</span>
                    Mis tareas
                </a>
                <span>/</span>
                <span>Nueva tarea</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-secondary-container" style="font-size:24px;">add_task</span>
                <h2 class="text-xl font-semibold leading-tight text-gray-300">Nueva tarea</h2>
            </div>
        </div>
    </x-slot>

    <div class="p-8">
        <div class="mx-auto max-w-4xl">
            <div class="glass-panel overflow-hidden rounded-2xl border border-white/5 shadow-2xl">
                <div class="border-b border-white/10 bg-surface/50 p-6">
                    <h3 class="text-xl font-bold text-white">Detalles de la tarea</h3>
                    <p class="mt-1 text-sm text-on-surface-variant">
                        Crea una tarea en un proyecto donde tienes acceso.
                    </p>
                </div>
                <div class="p-6 sm:p-8">
                    @include('mis-tareas._form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
