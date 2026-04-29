<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm min-w-0">
            <a href="{{ route('modulo.selector', 'requerimientos') }}"
               class="text-gray-500 hover:text-gray-300 transition-colors flex-shrink-0">
                Requerimientos
            </a>
            <span class="text-gray-600 flex-shrink-0">›</span>
            <span class="text-white font-semibold truncate">{{ $proyecto->nombre }}</span>
        </div>
    </x-slot>

    <div class="h-full flex flex-col">

        {{-- ─── TOOLBAR ──────────────────────────────────────────────── --}}
        <div class="flex-shrink-0 flex items-center gap-3 flex-wrap
                    px-6 py-3 border-b border-white/5 bg-white/[0.01]">

            {{-- Search --}}
            <div class="relative flex-shrink-0">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"
                      style="font-size: 17px;">search</span>
                <input type="text" id="req-search"
                       placeholder="Buscar por código o título…"
                       autocomplete="off"
                       class="w-56 bg-white/5 border border-white/10 rounded-lg pl-8 pr-3 py-1.5
                              text-sm text-white placeholder-gray-500
                              focus:outline-none focus:border-indigo-500/50 focus:bg-white/8 transition-colors" />
            </div>

            {{-- Filters --}}
            <div class="flex items-center gap-1.5 flex-wrap" id="req-filters">
                {{-- Tipo --}}
                <button class="filter-btn filter-active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="funcional">Funcional</button>
                <button class="filter-btn" data-filter="no_funcional">No funcional</button>

                <span class="w-px h-4 bg-white/10 flex-shrink-0"></span>

                {{-- Prioridad --}}
                <button class="filter-btn" data-filter="alta">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block mr-1"></span>Alta
                </button>
                <button class="filter-btn" data-filter="media">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block mr-1"></span>Media
                </button>
                <button class="filter-btn" data-filter="baja">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500 inline-block mr-1"></span>Baja
                </button>
            </div>

            {{-- Spacer --}}
            <div class="flex-1 hidden sm:block"></div>

            {{-- CTA --}}
            <a href="{{ route('proyectos.requirements.create', $proyecto) }}"
               class="flex items-center gap-1.5 px-4 py-1.5 bg-secondary-container text-white
                      text-xs font-bold rounded-xl hover:opacity-90 transition-all active:scale-95 flex-shrink-0">
                <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
                Nuevo requerimiento
            </a>
        </div>

        {{-- ─── BOARD BODY ────────────────────────────────────────────── --}}
        <div class="flex-1 min-h-0 overflow-y-auto" id="req-board">
            <div class="px-4 sm:px-6 py-4 space-y-2 max-w-screen-xl mx-auto">

                {{-- Flash success --}}
                @if(session('success'))
                <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl
                            bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm mb-2">
                    <span class="material-symbols-outlined flex-shrink-0" style="font-size: 18px;">check_circle</span>
                    {{ session('success') }}
                </div>
                @endif

                {{-- ── EMPTY STATE ─────────────────────────────────────── --}}
                @if($requirements->isEmpty())
                <div class="glass-panel rounded-2xl p-16 text-center mt-4">
                    <span class="material-symbols-outlined text-gray-600 block mb-3"
                          style="font-size: 52px;">assignment</span>
                    <p class="text-sm font-semibold text-white mb-1">Sin requerimientos todavía</p>
                    <p class="text-xs text-gray-500 mb-5 max-w-xs mx-auto">
                        Documenta los requerimientos funcionales y no funcionales de tu proyecto.
                    </p>
                    <a href="{{ route('proyectos.requirements.create', $proyecto) }}"
                       class="inline-flex items-center gap-1.5 px-5 py-2 bg-secondary-container
                              text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all active:scale-95">
                        <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                        Nuevo requerimiento
                    </a>
                </div>

                @else

                {{-- ── SPRINT SECTIONS ─────────────────────────────────── --}}
                @foreach($sprintGroups as $group)
                    @if($group['items']->isNotEmpty())
                    <section class="req-section" data-section="{{ $group['sprint']->id }}">

                        {{-- Section header --}}
                        <div class="section-toggle flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                                    cursor-pointer select-none hover:bg-white/[0.03] transition-colors
                                    border-l-2 pl-3
                                    {{ $group['sprint']->estado === 'en_progreso'
                                        ? 'border-emerald-500/60'
                                        : 'border-blue-500/40' }}">

                            <span class="material-symbols-outlined toggle-icon text-gray-400 transition-transform flex-shrink-0"
                                  style="font-size: 18px;">expand_more</span>

                            <span class="w-2 h-2 rounded-full flex-shrink-0
                                {{ $group['sprint']->estado === 'en_progreso'
                                    ? 'bg-emerald-400'
                                    : 'bg-blue-400' }}"></span>

                            <span class="text-sm font-semibold text-white truncate">
                                {{ $group['sprint']->nombre }}
                            </span>

                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border flex-shrink-0
                                {{ $group['sprint']->estado === 'en_progreso'
                                    ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/20'
                                    : 'bg-blue-500/15 text-blue-300 border-blue-500/20' }}">
                                {{ $group['sprint']->estado === 'en_progreso' ? 'Activo' : 'Planificado' }}
                            </span>

                            @if($group['sprint']->fecha_inicio || $group['sprint']->fecha_fin)
                            <span class="text-xs text-gray-500 hidden md:block flex-shrink-0">
                                @if($group['sprint']->fecha_inicio)
                                    {{ $group['sprint']->fecha_inicio->format('d/m/Y') }}
                                @endif
                                @if($group['sprint']->fecha_fin)
                                    → {{ $group['sprint']->fecha_fin->format('d/m/Y') }}
                                @endif
                            </span>
                            @endif

                            <span class="section-count text-xs text-gray-400 bg-white/5 px-2 py-0.5
                                         rounded-full font-medium ml-auto tabular-nums flex-shrink-0">
                                {{ $group['items']->count() }}
                            </span>
                        </div>

                        {{-- Section body --}}
                        <div class="section-body mt-0.5 ml-2 pl-1 border-l border-white/5 space-y-0.5 py-0.5">
                            @include('proyectos.requirements.partials.req-rows', [
                                'items'    => $group['items'],
                                'proyecto' => $proyecto,
                            ])
                        </div>

                    </section>
                    @endif
                @endforeach

                {{-- ── BACKLOG SECTION ──────────────────────────────────── --}}
                @if($backlog->isNotEmpty())
                <section class="req-section" data-section="backlog">

                    {{-- Section header --}}
                    <div class="section-toggle flex items-center gap-2.5 px-3 py-2.5 rounded-xl
                                cursor-pointer select-none hover:bg-white/[0.03] transition-colors
                                border-l-2 border-gray-600/40 pl-3">

                        <span class="material-symbols-outlined toggle-icon text-gray-400 transition-transform flex-shrink-0"
                              style="font-size: 18px;">expand_more</span>

                        <span class="w-2 h-2 rounded-full bg-gray-500 flex-shrink-0"></span>

                        <span class="text-sm font-semibold text-white">Backlog</span>

                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border flex-shrink-0
                                     bg-gray-500/10 text-gray-400 border-gray-500/20">
                            Sin sprint
                        </span>

                        <span class="section-count text-xs text-gray-400 bg-white/5 px-2 py-0.5
                                     rounded-full font-medium ml-auto tabular-nums flex-shrink-0">
                            {{ $backlog->count() }}
                        </span>
                    </div>

                    {{-- Backlog body --}}
                    <div class="section-body mt-0.5 ml-2 pl-1 border-l border-white/5 space-y-0.5 py-0.5">
                        @include('proyectos.requirements.partials.req-rows', [
                            'items'    => $backlog,
                            'proyecto' => $proyecto,
                        ])
                    </div>

                </section>
                @endif

                @endif {{-- end if $requirements->isEmpty() --}}

                {{-- No results after filter --}}
                <div id="no-results" class="hidden text-center py-14">
                    <span class="material-symbols-outlined text-gray-600 block mb-2"
                          style="font-size: 36px;">search_off</span>
                    <p class="text-sm text-gray-500">Sin resultados para este filtro o búsqueda.</p>
                    <button id="clear-filters"
                            class="mt-2 text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                        Limpiar filtros
                    </button>
                </div>

            </div>
        </div>

    </div>

    @push('scripts')
    <style>
        .filter-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(156,163,175,1);
            transition: all 0.1s;
            cursor: pointer;
            background: transparent;
            white-space: nowrap;
        }
        .filter-btn:hover {
            border-color: rgba(255,255,255,0.2);
            color: #fff;
        }
        .filter-btn.filter-active {
            border-color: rgba(99,102,241,0.4);
            background: rgba(99,102,241,0.12);
            color: rgb(165,180,252);
        }
    </style>

    <script>
    (function () {
        'use strict';

        // ── Search ───────────────────────────────────────────────────
        var searchInput = document.getElementById('req-search');
        var searchQuery = '';

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                searchQuery = this.value.toLowerCase().trim();
                applyFilters();
            });
        }

        // ── Filters ──────────────────────────────────────────────────
        var activeFilter = 'all';
        var filterBtns   = document.querySelectorAll('.filter-btn');

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeFilter = this.dataset.filter;

                filterBtns.forEach(function (b) {
                    b.classList.remove('filter-active');
                });
                this.classList.add('filter-active');

                applyFilters();
            });
        });

        var clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                activeFilter = 'all';
                searchQuery  = '';
                if (searchInput) searchInput.value = '';
                filterBtns.forEach(function (b) { b.classList.remove('filter-active'); });
                var allBtn = document.querySelector('[data-filter="all"]');
                if (allBtn) allBtn.classList.add('filter-active');
                applyFilters();
            });
        }

        // ── Apply filters + search ───────────────────────────────────
        function applyFilters() {
            var anyVisible = false;

            document.querySelectorAll('.req-section').forEach(function (section) {
                var sectionVisible = 0;

                section.querySelectorAll('.req-row-wrapper').forEach(function (wrapper) {
                    var tipo      = wrapper.dataset.tipo;
                    var prioridad = wrapper.dataset.prioridad;
                    var text      = wrapper.dataset.search || '';

                    var matchesFilter =
                        activeFilter === 'all' ||
                        tipo === activeFilter   ||
                        prioridad === activeFilter;

                    var matchesSearch = !searchQuery || text.includes(searchQuery);

                    if (matchesFilter && matchesSearch) {
                        wrapper.style.display = '';
                        sectionVisible++;
                        anyVisible = true;
                    } else {
                        wrapper.style.display = 'none';
                    }
                });

                // Update count badge
                var badge = section.querySelector('.section-count');
                if (badge) badge.textContent = sectionVisible;

                // Hide section if no visible items under current filter
                section.style.display = sectionVisible > 0 ? '' : 'none';
            });

            var noResults = document.getElementById('no-results');
            if (noResults) {
                noResults.style.display = anyVisible ? 'none' : '';
            }
        }

        // ── Section collapse / expand ────────────────────────────────
        document.querySelectorAll('.section-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var section = this.closest('.req-section');
                var body    = section && section.querySelector('.section-body');
                var icon    = this.querySelector('.toggle-icon');

                if (!body) return;

                var isOpen = !body.classList.contains('hidden');
                body.classList.toggle('hidden', isOpen);
                if (icon) icon.textContent = isOpen ? 'chevron_right' : 'expand_more';
            });
        });

        // ── Row expand / collapse ────────────────────────────────────
        document.querySelectorAll('.req-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                if (e.target.closest('a, button, form')) return;

                var wrapper = this.closest('.req-row-wrapper');
                var detail  = wrapper && wrapper.querySelector('.req-detail');
                var icon    = this.querySelector('.expand-icon');

                if (!detail) return;

                var isHidden = detail.classList.contains('hidden');
                detail.classList.toggle('hidden', !isHidden);
                if (icon) icon.textContent = isHidden ? 'expand_more' : 'chevron_right';
            });
        });

    })();
    </script>
    @endpush

</x-app-layout>
