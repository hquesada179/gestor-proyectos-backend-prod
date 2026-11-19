<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm min-w-0">
            <a href="{{ route('scrum-board.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors flex-shrink-0">Scrum Board</a>
            <span class="text-gray-600 flex-shrink-0">›</span>
            <span class="text-white font-semibold truncate">{{ $proyecto->nombre }}</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        /* Asegurar que el contenedor principal de la vista no desborde verticalmente */
        main {
            overflow: hidden !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Contenedor de columna estilo panel unificado (header + lista dentro del mismo bloque) */
        .kanban-col {
            background-color: #0b1420;
            border: 1px solid #162436;
            border-radius: 1.125rem;
            padding: 1.125rem 0.875rem 0.875rem 0.875rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            min-width: 0;
        }

        .kanban-col-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 0.375rem 0.875rem 0.375rem;
            flex-shrink: 0;
        }

        .kanban-col-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.01em;
        }

        .kanban-col-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #7c8ea6;
        }

        .kanban-col-add {
            width: 1.5rem;
            height: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            color: #64748b;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .kanban-col-add:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
        }

        /* Lista interna desplazable de tarjetas */
        .kanban-list {
            flex: 1 1 0%;
            min-height: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.125rem 0.25rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(124, 142, 166, 0.25) transparent;
        }
        .kanban-list::-webkit-scrollbar {
            width: 5px;
        }
        .kanban-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .kanban-list::-webkit-scrollbar-thumb {
            background: rgba(124, 142, 166, 0.25);
            border-radius: 4px;
        }
        .kanban-list::-webkit-scrollbar-thumb:hover {
            background: rgba(124, 142, 166, 0.45);
        }

        /* Tarjeta de tarea fiel al diseño de referencia */
        .kanban-card {
            background-color: #0e1928;
            border: 1px solid #1c2d42;
            border-radius: 0.875rem;
            padding: 0.95rem 1.05rem;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            cursor: grab;
            user-select: none;
            flex-shrink: 0;
            transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
        }
        .kanban-card:hover {
            background-color: #122033;
            border-color: #294260;
        }
        .kanban-card:active {
            cursor: grabbing;
        }

        .kb-card-top {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .kb-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 9999px;
            flex-shrink: 0;
        }
        .kb-dot--danger { background-color: #f06464; }
        .kb-dot--warn   { background-color: #eab308; }
        .kb-dot--muted  { background-color: #3b526b; }

        .kb-id {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #7c8ea6;
            line-height: 1;
        }

        .kb-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.35;
            margin: 0.1rem 0 0.25rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .kb-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-height: 1.625rem;
        }

        /* Píldoras de fecha */
        .kb-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.625rem;
            border-radius: 0.425rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.2;
            border: 1px solid transparent;
        }
        .kb-pill--neutral {
            background-color: #132235;
            border-color: #223750;
            color: #94a3b8;
        }
        .kb-pill--warn {
            background-color: #2b2111;
            border-color: #5c4316;
            color: #eab308;
        }
        .kb-pill--danger {
            background-color: #2c1519;
            border-color: #63232b;
            color: #f06464;
        }

        /* Asignado */
        .kb-assignee {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }
        .kb-avatar {
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 9999px;
            background-color: #4338ca;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kb-avatar-img {
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 9999px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .kb-assignee-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }

        /* Estado sin asignar */
        .kb-unassigned {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.8125rem;
            font-style: italic;
        }
        .kb-unassigned-circle {
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 9999px;
            border: 1.5px dashed #3b4e68;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            flex-shrink: 0;
        }

        /* Columna vacía */
        .kanban-empty {
            flex: 1 1 0%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            border: 1px dashed #1c2d42;
            border-radius: 0.875rem;
            text-align: center;
            margin: 0.25rem 0;
            pointer-events: none;
        }

        /* Estados interactivos de arrastre con SortableJS */
        .kanban-ghost {
            opacity: 0.35;
            background: rgba(67, 56, 202, 0.12) !important;
            border: 1px dashed #4338ca !important;
            border-radius: 0.875rem;
        }
        .kanban-dragging {
            opacity: 0.96;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.65);
            border-color: #4338ca !important;
            transform: rotate(1deg) scale(1.01);
            cursor: grabbing !important;
        }
    </style>
    @endpush

    <div class="h-full flex flex-col min-h-0">

        {{-- Barra superior de herramientas del tablero --}}
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-3.5 border-b border-white/5 bg-[#0F1117]/60 backdrop-blur-md gap-4 flex-wrap">
            <div class="flex items-center gap-4 min-w-0">
                <div>
                    <h1 class="text-base font-bold text-white leading-tight truncate">{{ $proyecto->nombre }}</h1>
                    @php $totalTareas = $tasks->flatten()->count(); @endphp
                    <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                        <span>Tablero Kanban</span>
                        <span class="text-gray-600">&middot;</span>
                        <span id="total-tasks-indicator" class="font-medium text-slate-300">
                            {{ $totalTareas }} {{ $totalTareas === 1 ? 'tarea' : 'tareas' }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Buscador dinámico de tareas en vivo --}}
                <div class="relative min-w-[200px] sm:min-w-[240px]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">search</span>
                    <input type="text"
                           id="kanban-search"
                           placeholder="Filtrar tareas..."
                           class="w-full pl-9 pr-8 py-1.5 text-xs bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-all" />
                    <button type="button"
                            id="kanban-search-clear"
                            class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white text-xs"
                            title="Limpiar búsqueda">
                        <span class="material-symbols-outlined text-xs">close</span>
                    </button>
                </div>

                {{-- Filtro rápido para alternar "Mis Tareas" --}}
                <button type="button"
                        id="btn-filter-my-tasks"
                        data-user-id="{{ Auth::id() }}"
                        class="px-3 py-1.5 text-xs font-semibold text-gray-400 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">person</span>
                    <span>Mis tareas</span>
                </button>

                {{-- Botón para registrar nueva tarea --}}
                <a href="{{ route('proyectos.tasks.create', ['proyecto' => $proyecto, 'from' => 'scrum-board']) }}"
                   class="flex items-center gap-1.5 px-4 py-1.5 bg-secondary-container hover:bg-opacity-90 text-white text-xs font-bold rounded-xl transition-all active:scale-95 flex-shrink-0 shadow-md shadow-indigo-950/50">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Nueva tarea
                </a>
            </div>
        </div>

        {{-- Contenedor de columnas Kanban con scroll horizontal adaptativo --}}
        <div class="flex-1 min-h-0 w-full overflow-x-auto overflow-y-hidden">
            <div class="kanban-grid"
                 style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.25rem;height:100%;width:100%;min-width:1150px;padding:1.25rem 1.5rem;">

                @foreach($statuses as $status)
                @php $colCount = ($tasks[$status->id] ?? collect())->count(); @endphp
                <div class="kanban-col" data-status-id="{{ $status->id }}">

                    {{-- Cabecera integrada dentro del contenedor de la columna --}}
                    <div class="kanban-col-header">
                        <span class="kanban-col-title truncate">{{ $status->nombre }}</span>

                        <div class="kanban-col-meta">
                            <span class="tabular-nums">
                                <span class="count-badge">{{ $colCount }}</span>
                                <span class="count-unit">{{ $colCount === 1 ? 'tarea' : 'tareas' }}</span>
                            </span>

                            <a href="{{ route('proyectos.tasks.create', ['proyecto' => $proyecto, 'status_id' => $status->id, 'from' => 'scrum-board']) }}"
                               class="kanban-col-add"
                               title="Añadir tarea a {{ $status->nombre }}">
                                <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                            </a>
                        </div>
                    </div>

                    {{-- Lista desplazable de tarjetas dentro del contenedor de columna --}}
                    <div class="kanban-list" data-status-id="{{ $status->id }}">

                        @forelse($tasks[$status->id] ?? [] as $task)
                        @php
                            $prioridad = mb_strtolower((string) ($task->userStory?->prioridad ?? ''));
                            $assignedUserId = (string) $task->assigned_to;
                            $assignedName = $task->assignedTo?->name;
                            // Nombre en formato título para evitar mayúsculas sostenidas
                            $formattedName = $assignedName ? mb_convert_case(mb_strtolower($assignedName), MB_CASE_TITLE) : null;

                            // Cálculo de estado de fecha límite a inicio de día
                            $dueLabel = null;
                            $dueClass = 'kb-pill--neutral';
                            $days = null;
                            if ($task->fecha_limite) {
                                $days = (int) now()->startOfDay()->diffInDays($task->fecha_limite->copy()->startOfDay(), false);
                                if ($days < 0) {
                                    $abs = abs($days);
                                    $dueLabel = 'Vencida hace '.$abs.($abs === 1 ? ' día' : ' días');
                                    $dueClass = 'kb-pill--danger';
                                } elseif ($days === 0) {
                                    $dueLabel = 'Vence hoy';
                                    $dueClass = 'kb-pill--warn';
                                } elseif ($days <= 3) {
                                    $dueLabel = $days === 1 ? 'Vence mañana' : 'Vence en '.$days.' días';
                                    $dueClass = 'kb-pill--warn';
                                } else {
                                    $dueLabel = $task->fecha_limite->format('d/m/Y');
                                    $dueClass = 'kb-pill--neutral';
                                }
                            }

                            // Punto de color según prioridad o urgencia de fecha
                            if (in_array($prioridad, ['alta', 'urgente'], true) || ($days !== null && $days < 0)) {
                                $dotClass = 'kb-dot--danger';
                            } elseif ($prioridad === 'media' || ($days !== null && $days >= 0 && $days <= 3)) {
                                $dotClass = 'kb-dot--warn';
                            } elseif ($prioridad === 'baja') {
                                $dotClass = 'kb-dot--muted';
                            } elseif ($task->fecha_limite) {
                                $dotClass = 'kb-dot--danger';
                            } else {
                                $dotClass = 'kb-dot--muted';
                            }
                        @endphp
                        {{-- Tarjeta con estilos dedicados (sin bordes blancos por defecto de Tailwind) --}}
                        <div class="kanban-card"
                             data-task-id="{{ $task->id }}"
                             data-task-assigned="{{ $assignedUserId }}"
                             data-task-title="{{ mb_strtolower($task->titulo) }}"
                             data-task-url="{{ route('proyectos.tasks.show', [$proyecto, $task, 'from' => 'scrum-board']) }}">

                            {{-- Fila superior: Punto de estado/prioridad + #ID --}}
                            <div class="kb-card-top">
                                <span class="kb-dot {{ $dotClass }}"></span>
                                <span class="kb-id">#{{ $task->id }}</span>
                            </div>

                            {{-- Título de la tarea --}}
                            <h3 class="kb-title">{{ $task->titulo }}</h3>

                            {{-- Fila inferior: Píldora de fecha (solo si tiene fecha) + Asignado --}}
                            <div class="kb-footer">
                                @if($dueLabel)
                                    <span class="kb-pill {{ $dueClass }}">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span>{{ $dueLabel }}</span>
                                    </span>
                                @endif

                                @if($task->assignedTo)
                                    <div class="kb-assignee" title="{{ $task->assignedTo->name }}">
                                        @if($task->assignedTo->profile_photo_path)
                                            <img src="{{ asset('storage/' . $task->assignedTo->profile_photo_path) }}"
                                                 alt="{{ $formattedName }}"
                                                 class="kb-avatar-img" />
                                        @else
                                            <span class="kb-avatar">
                                                {{ strtoupper(mb_substr($task->assignedTo->name, 0, 1)) }}
                                            </span>
                                        @endif
                                        <span class="kb-assignee-name">{{ $formattedName }}</span>
                                    </div>
                                @else
                                    <span class="kb-unassigned">
                                        <span class="kb-unassigned-circle">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                        </span>
                                        <span>Sin asignar</span>
                                    </span>
                                @endif
                            </div>

                        </div>
                        @empty
                        {{-- Indicador visual de columna vacía --}}
                        <div class="kanban-empty">
                            <span class="material-symbols-outlined text-gray-600 mb-2" style="font-size: 30px;">inbox</span>
                            <p class="text-xs font-semibold text-gray-400">Sin tareas en esta etapa</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Arrastra tareas aquí para moverlas</p>
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

        // Bandera para diferenciar clics de navegación vs acciones de arrastre
        var dragging = false;

        // Configuración de SortableJS en cada columna receptora
        document.querySelectorAll('.kanban-list').forEach(function (list) {
            Sortable.create(list, {
                group:             'kanban-board',
                animation:         150,
                ghostClass:        'kanban-ghost',
                dragClass:         'kanban-dragging',
                draggable:         '.kanban-card',
                scroll:            true,
                scrollSensitivity: 80,
                scrollSpeed:       10,

                onStart: function () {
                    dragging = true;
                },

                onEnd: function (evt) {
                    setTimeout(function () { dragging = false; }, 60);

                    const taskId      = evt.item.dataset.taskId;
                    const newStatusId = parseInt(evt.to.dataset.statusId, 10);
                    const oldStatusId = parseInt(evt.from.dataset.statusId, 10);

                    refreshCounts();
                    syncEmptyHints();

                    // Validación defensiva de IDs
                    if (!taskId || isNaN(newStatusId) || isNaN(oldStatusId)) return;
                    if (newStatusId === oldStatusId) return;

                    // Persistir el cambio de estado en el backend
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
                        console.warn('[Kanban] Error al actualizar estado de la tarea:', err);
                        revertCard(evt);
                    });
                }
            });
        });

        // Revertir posición de la tarjeta si la actualización remota falla
        function revertCard(evt) {
            var ref = evt.from.children[evt.oldIndex] || null;
            evt.from.insertBefore(evt.item, ref);
            refreshCounts();
            syncEmptyHints();
        }

        // Actualizar contadores numéricos de cada columna
        function refreshCounts() {
            var grandTotal = 0;
            document.querySelectorAll('.kanban-col').forEach(function (col) {
                var badge = col.querySelector('.count-badge');
                var unit  = col.querySelector('.count-unit');
                var list  = col.querySelector('.kanban-list');
                if (badge && list) {
                    var totalInCol = list.querySelectorAll('.kanban-card').length;
                    badge.textContent = totalInCol;
                    if (unit) {
                        unit.textContent = totalInCol === 1 ? 'tarea' : 'tareas';
                    }
                    grandTotal += totalInCol;
                }
            });

            var indicator = document.getElementById('total-tasks-indicator');
            if (indicator) {
                indicator.textContent = grandTotal + (grandTotal === 1 ? ' tarea' : ' tareas');
            }
        }

        // Sincronizar visibilidad del estado vacío según existan tarjetas en la columna
        function syncEmptyHints() {
            document.querySelectorAll('.kanban-col').forEach(function (col) {
                var list  = col.querySelector('.kanban-list');
                var hint  = list ? list.querySelector('.kanban-empty') : null;
                var cards = list ? list.querySelectorAll('.kanban-card') : [];

                if (!hint) return;
                hint.style.display = cards.length === 0 ? '' : 'none';
            });
        }

        // Navegar al detalle de la tarea sólo al hacer clic sin arrastre
        document.querySelectorAll('.kanban-card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (!dragging) {
                    window.location.href = this.dataset.taskUrl;
                }
            });
        });

        // ── Filtros interactivos en tiempo real (Búsqueda + Mis Tareas) ───
        var searchInput   = document.getElementById('kanban-search');
        var clearBtn      = document.getElementById('kanban-search-clear');
        var myTasksBtn    = document.getElementById('btn-filter-my-tasks');
        var myTasksActive = false;

        function applyFilters() {
            var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            var targetUserId = myTasksBtn ? myTasksBtn.dataset.userId : null;

            if (clearBtn) {
                clearBtn.classList.toggle('hidden', query.length === 0);
            }

            document.querySelectorAll('.kanban-card').forEach(function (card) {
                var title = card.dataset.taskTitle || '';
                var assignedId = card.dataset.taskAssigned || '';

                var matchesQuery = !query || title.includes(query);
                var matchesUser  = !myTasksActive || (assignedId === targetUserId);

                if (matchesQuery && matchesUser) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                applyFilters();
            });
        }

        if (myTasksBtn) {
            myTasksBtn.addEventListener('click', function () {
                myTasksActive = !myTasksActive;
                if (myTasksActive) {
                    myTasksBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-500');
                    myTasksBtn.classList.remove('bg-white/5', 'text-gray-400', 'border-white/10');
                } else {
                    myTasksBtn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-500');
                    myTasksBtn.classList.add('bg-white/5', 'text-gray-400', 'border-white/10');
                }
                applyFilters();
            });
        }

        // Sincronización inicial
        syncEmptyHints();

    })();
    </script>
    @endpush

</x-app-layout>