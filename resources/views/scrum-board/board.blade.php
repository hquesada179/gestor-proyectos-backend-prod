<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm min-w-0">
            <a href="{{ route('scrum-board.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors flex-shrink-0">Scrum Board</a>
            <span class="text-gray-600 flex-shrink-0">›</span>
            <span class="text-white font-semibold truncate">{{ $proyecto->nombre }}</span>
        </div>
    </x-slot>

    <div class="h-full flex flex-col">

        {{-- Board sub-header --}}
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-3 border-b border-white/5">
            <div>
                <h1 class="text-base font-bold text-white leading-tight">{{ $proyecto->nombre }}</h1>
                @php $totalTareas = $tasks->flatten()->count(); @endphp
                <p class="text-xs text-gray-500 mt-0.5">
                    Tablero Kanban &middot; {{ $totalTareas }} {{ $totalTareas === 1 ? 'tarea' : 'tareas' }}
                </p>
            </div>
            <a href="{{ route('proyectos.tasks.create', $proyecto) }}"
               class="flex items-center gap-1.5 px-4 py-2 bg-secondary-container text-white text-xs font-bold rounded-xl hover:opacity-90 transition-all active:scale-95 flex-shrink-0">
                <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
                Nueva tarea
            </a>
        </div>

        {{-- Kanban columns --}}
        <div class="flex-1 min-h-0 w-full overflow-x-auto overflow-y-hidden">
            <div class="kanban-grid"
                 style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;height:100%;width:100%;min-width:1100px;padding:1.25rem 1.5rem;">

                @foreach($statuses as $status)
                <div class="kanban-col flex flex-col" style="height:100%;min-width:0;">

                    {{-- Column header --}}
                    <div class="flex items-center gap-2 mb-3 px-1 flex-shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                              style="background-color: {{ $status->color }};"></span>
                        <span class="text-sm font-semibold text-white flex-1 truncate">{{ $status->nombre }}</span>
                        <span class="count-badge text-xs text-gray-400 bg-white/5 px-2 py-0.5 rounded-full font-medium tabular-nums">
                            {{ ($tasks[$status->id] ?? collect())->count() }}
                        </span>
                    </div>

                    {{-- Drop zone --}}
                    <div class="kanban-list flex-1 min-h-0 overflow-y-auto p-2 rounded-2xl
                                bg-white/[0.03] border border-white/5 flex flex-col gap-2
                                transition-colors duration-150"
                         data-status-id="{{ $status->id }}">

                        @forelse($tasks[$status->id] ?? [] as $task)
                        <div class="kanban-card rounded-xl bg-white/5 border border-white/[0.08] p-3
                                    cursor-grab active:cursor-grabbing
                                    hover:bg-white/10 hover:border-white/20
                                    transition-all duration-150 select-none flex-shrink-0"
                             data-task-id="{{ $task->id }}"
                             data-task-url="{{ route('proyectos.tasks.show', [$proyecto, $task]) }}">

                            {{-- Title --}}
                            <p class="text-sm font-medium text-white leading-snug line-clamp-2 mb-2">
                                {{ $task->titulo }}
                            </p>

                            {{-- Badges --}}
                            @if($task->sprint || $task->fecha_limite)
                            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                @if($task->sprint)
                                <span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/20 px-2 py-0.5 rounded-full font-medium truncate max-w-[140px]">
                                    {{ $task->sprint->nombre }}
                                </span>
                                @endif
                                @if($task->fecha_limite)
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border
                                    {{ $task->fecha_limite->isPast() ? 'bg-red-500/15 text-red-300 border-red-500/25' : 'bg-white/5 text-gray-400 border-white/5' }}">
                                    {{ $task->fecha_limite->format('d/m/Y') }}
                                </span>
                                @endif
                            </div>
                            @endif

                            {{-- Assignee --}}
                            @if($task->assignedTo)
                            <div class="flex items-center gap-1.5 pt-2 border-t border-white/5">
                                <div class="w-4 h-4 rounded-full bg-secondary-container flex items-center justify-center flex-shrink-0">
                                    <span class="text-[8px] font-bold text-white leading-none">
                                        {{ strtoupper(mb_substr($task->assignedTo->name, 0, 1)) }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-gray-400 truncate">{{ $task->assignedTo->name }}</span>
                            </div>
                            @endif

                        </div>
                        @empty
                        <div class="kanban-empty flex-1 flex flex-col items-center justify-center py-6 pointer-events-none">
                            <span class="material-symbols-outlined text-gray-700 mb-1" style="font-size: 28px;">inbox</span>
                            <p class="text-xs text-gray-600">Sin tareas</p>
                        </div>
                        @endforelse

                    </div>
                </div>
                @endforeach

            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <script>
    (function () {
        'use strict';

        const csrf    = document.querySelector('meta[name="csrf-token"]').content;
        const baseUrl = '{{ url("/scrum-board/tasks") }}';

        // Track drag state via SortableJS onStart/onEnd (not DOM events)
        var dragging = false;

        document.querySelectorAll('.kanban-list').forEach(function (list) {
            Sortable.create(list, {
                group:           'kanban-board',
                animation:       150,
                ghostClass:      'kanban-ghost',
                dragClass:       'kanban-dragging',
                draggable:       '.kanban-card',   // only task cards, not the empty hint
                scroll:          true,
                scrollSensitivity: 80,
                scrollSpeed:     10,

                onStart: function () {
                    dragging = true;
                },

                onEnd: function (evt) {
                    setTimeout(function () { dragging = false; }, 50);

                    const taskId      = evt.item.dataset.taskId;
                    const newStatusId = parseInt(evt.to.dataset.statusId, 10);
                    const oldStatusId = parseInt(evt.from.dataset.statusId, 10);

                    refreshCounts();
                    syncEmptyHints();

                    // Guard: taskId or statusId missing / invalid
                    if (!taskId || isNaN(newStatusId) || isNaN(oldStatusId)) return;
                    if (newStatusId === oldStatusId) return;

                    fetch(baseUrl + '/' + taskId + '/status', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({ task_status_id: newStatusId }),
                    })
                    .then(function (r) {
                        if (!r.ok) { throw new Error('HTTP ' + r.status); }
                        return r.json();
                    })
                    .then(function (data) {
                        if (!data.ok) {
                            revertCard(evt);
                        }
                    })
                    .catch(function (err) {
                        console.warn('[Kanban] Error al actualizar estado:', err);
                        revertCard(evt);
                    });
                }
            });
        });

        function revertCard(evt) {
            var ref = evt.from.children[evt.oldIndex] || null;
            evt.from.insertBefore(evt.item, ref);
            refreshCounts();
            syncEmptyHints();
        }

        function refreshCounts() {
            document.querySelectorAll('.kanban-col').forEach(function (col) {
                var badge = col.querySelector('.count-badge');
                var list  = col.querySelector('.kanban-list');
                if (badge && list) {
                    badge.textContent = list.querySelectorAll('.kanban-card').length;
                }
            });
        }

        function syncEmptyHints() {
            document.querySelectorAll('.kanban-col').forEach(function (col) {
                var list  = col.querySelector('.kanban-list');
                var hint  = list ? list.querySelector('.kanban-empty') : null;
                var cards = list ? list.querySelectorAll('.kanban-card') : [];

                if (!hint) return;
                hint.style.display = cards.length === 0 ? '' : 'none';
            });
        }

        // Click a card → navigate to task detail (only when not dragging)
        document.querySelectorAll('.kanban-card').forEach(function (card) {
            card.addEventListener('click', function () {
                if (!dragging) {
                    window.location.href = this.dataset.taskUrl;
                }
            });
        });

        // Initial sync
        syncEmptyHints();

    })();
    </script>
    <style>
        .kanban-ghost {
            opacity: 0.3;
            background: rgba(99, 102, 241, 0.12) !important;
            border: 1px dashed rgba(99, 102, 241, 0.4) !important;
            border-radius: 0.75rem;
        }
        .kanban-dragging {
            opacity: 0.95;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(99, 102, 241, 0.4);
            transform: rotate(1.5deg) scale(1.03);
            cursor: grabbing !important;
        }
        .kanban-list {
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .kanban-list::-webkit-scrollbar { width: 4px; }
        .kanban-list::-webkit-scrollbar-track { background: transparent; }
        .kanban-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
    </style>
    @endpush

</x-app-layout>
