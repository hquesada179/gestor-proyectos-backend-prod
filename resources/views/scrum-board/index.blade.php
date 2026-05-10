<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">
                {{ __('app.nav.dashboard') }}
            </a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">{{ __('app.nav.scrum_board') }}</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        /* ── Grid layout (3-col responsive via native CSS) ─────────────── */
        #projectsGrid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
            min-width: 0;
            overflow: hidden;
            contain: layout paint style;
        }
        @media (min-width: 768px)  { #projectsGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { #projectsGrid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

        /* ── List layout ───────────────────────────────────────────────── */
        #projectsList { display: flex; flex-direction: column; gap: .625rem; min-width: 0; overflow: hidden; contain: layout paint style; }

        /* ── Hidden state (works even if Tailwind 'hidden' isn't compiled) */
        .pv-hidden { display: none !important; }
        #projectsGrid.pv-view-hidden {
            display: grid !important;
            position: absolute !important;
            left: -10000px !important;
            top: 0 !important;
            width: 100% !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        #projectsList.pv-view-hidden {
            display: flex !important;
            position: absolute !important;
            left: -10000px !important;
            top: 0 !important;
            width: 100% !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        /* ── Toggle button states ──────────────────────────────────────── */
        .view-btn       { padding:6px; border-radius:8px; border:none; cursor:pointer;
                          background:transparent; color:#64748b; transition: background .15s, color .15s; }
        .view-btn:hover { background:rgba(255,255,255,0.07); color:#e2e8f0; }
        .view-btn.active{ background:rgba(99,102,241,0.25); color:#a5b4fc; }

        /* ── Search focus ──────────────────────────────────────────────── */
        #project-search:focus { border-color:rgba(99,102,241,0.5) !important; }

        /* ── Card/list hidden via search ───────────────────────────────── */
        .project-card.pv-hidden,
        .project-list-item.pv-hidden { display: none !important; }
    </style>
    @endpush

    @php
        $activeProjectViewMode = request()->cookie('project_view_mode', 'grid');
        $isListViewActive = $activeProjectViewMode === 'list';
        $preloadCoverUrls = $proyectos
            ->filter(fn ($project) => !empty($project->cover_image))
            ->take(6)
            ->map(fn ($project) => asset('storage/'.$project->cover_image).'?v='.(optional($project->updated_at)->timestamp ?? '1'))
            ->values();
    @endphp

    @push('head')
        @foreach($preloadCoverUrls as $coverUrl)
            <link rel="preload" as="image" href="{{ $coverUrl }}" fetchpriority="{{ $loop->first ? 'high' : 'auto' }}">
        @endforeach
    @endpush

    <div style="padding: 1.5rem; min-width:0; overflow-x:hidden;">

        {{-- ── Page header ────────────────────────────────────────────────── --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <div>
                <h1 style="font-size:1.2rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">
                    {{ __('app.nav.scrum_board') }}
                </h1>
                <p style="font-size:12px; color:#64748b; margin:0;">{{ __('app.scrum.description') }}</p>
            </div>

            <div style="display:flex; align-items:center; gap:10px;">
                {{-- Search --}}
                <div style="position:relative;">
                    <span class="material-symbols-outlined"
                          style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
                                 font-size:14px;color:#475569;pointer-events:none;">search</span>
                    <input id="project-search" type="text" placeholder="Buscar proyecto…"
                           style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
                                  color:#f1f5f9;border-radius:12px;font-size:12px;
                                  padding:7px 14px 7px 32px;width:200px;outline:none;transition:border-color .15s;" />
                </div>

                {{-- View toggle --}}
                <div style="display:flex;align-items:center;gap:4px;
                            background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);
                            border-radius:10px;padding:4px;">
                    <button id="btn-grid" class="view-btn {{ $isListViewActive ? '' : 'active' }}" onclick="setView('grid')" title="Vista bloques">
                        <span class="material-symbols-outlined" style="font-size:18px;display:block;">grid_view</span>
                    </button>
                    <button id="btn-list" class="view-btn {{ $isListViewActive ? 'active' : '' }}" onclick="setView('list')" title="Vista lista">
                        <span class="material-symbols-outlined" style="font-size:18px;display:block;">view_list</span>
                    </button>
                </div>
            </div>
        </div>

        @if($proyectos->isEmpty())
            <div style="border-radius:1rem;border:1px solid rgba(255,255,255,0.06);
                        background:rgba(255,255,255,0.02);padding:4rem;text-align:center;">
                <span class="material-symbols-outlined" style="font-size:44px;color:#374151;display:block;margin-bottom:12px;">view_kanban</span>
                <p style="font-size:14px;color:#6b7280;margin:0 0 12px;">{{ __('app.empty.no_reg_projects') }}</p>
                <a href="{{ route('proyectos.create') }}" style="font-size:12px;color:#818cf8;text-decoration:underline;">
                    {{ __('app.actions.create_first_project') }}
                </a>
            </div>
        @else
            <p style="font-size:11px;color:#475569;margin-bottom:1rem;">
                {{ $proyectos->count() }} {{ $proyectos->count() === 1 ? 'proyecto' : 'proyectos' }}
            </p>

            {{-- ── GRID VIEW (default) ────────────────────────────────────── --}}
            <div id="projectsGrid" class="{{ $isListViewActive ? 'pv-view-hidden' : '' }}">
                @foreach($proyectos as $proyecto)
                    <x-project-module-card
                        :proyecto="$proyecto"
                        :countValue="$proyecto->tasks_count"
                        countLabel="{{ __('app.projects.tasks_short') }}"
                        countIcon="task_alt"
                        actionLabel="{{ __('app.scrum.open_board') }}"
                        :actionUrl="route('scrum-board.show', $proyecto)"
                        :coverPriority="!$isListViewActive && $loop->iteration <= 6 && !empty($proyecto->cover_image)"
                    />
                @endforeach
            </div>

            {{-- ── LIST VIEW ──────────────────────────────────────────────── --}}
            <div id="projectsList" class="{{ $isListViewActive ? '' : 'pv-view-hidden' }}">
                @foreach($proyectos as $proyecto)
                    <x-project-module-list-item
                        :proyecto="$proyecto"
                        :countValue="$proyecto->tasks_count"
                        countLabel="{{ __('app.projects.tasks_short') }}"
                        countIcon="task_alt"
                        actionLabel="{{ __('app.scrum.open_board') }}"
                        :actionUrl="route('scrum-board.show', $proyecto)"
                        :coverPriority="$isListViewActive && $loop->iteration <= 6 && !empty($proyecto->cover_image)"
                    />
                @endforeach
            </div>

            <p id="no-results" style="display:none;text-align:center;font-size:13px;color:#475569;padding:2rem 0;">
                Sin proyectos para esa búsqueda.
            </p>
        @endif

    </div>

    @push('scripts')
    <script>
    (function () {
        'use strict';

        var LS_KEY    = 'projectViewMode';
        var grid      = document.getElementById('projectsGrid');
        var list      = document.getElementById('projectsList');
        var btnGrid   = document.getElementById('btn-grid');
        var btnList   = document.getElementById('btn-list');
        var noResults = document.getElementById('no-results');

        // ── View toggle ───────────────────────────────────────────────────
        window.setView = function (mode) {
            var pre = document.getElementById('pv-pre');
            if (pre) pre.parentNode.removeChild(pre);

            var isGrid = mode === 'grid';

            if (grid)    { if (isGrid) grid.classList.remove('pv-view-hidden'); else grid.classList.add('pv-view-hidden'); }
            if (list)    { if (isGrid) list.classList.add('pv-view-hidden');    else list.classList.remove('pv-view-hidden'); }

            if (btnGrid) { if (isGrid) btnGrid.classList.add('active');  else btnGrid.classList.remove('active'); }
            if (btnList) { if (isGrid) btnList.classList.remove('active'); else btnList.classList.add('active'); }

            try {
                localStorage.setItem(LS_KEY, mode);
                document.cookie = 'project_view_mode=' + encodeURIComponent(mode) + ';path=/;max-age=31536000;SameSite=Lax';
            } catch(e) {}

            // Re-apply search filter so hidden items stay hidden
            applySearch((document.getElementById('project-search') || {}).value || '');
            warmVisibleProjectCovers(mode);
        };

        function warmVisibleProjectCovers(mode) {
            var root = mode === 'list' ? list : grid;
            var inactiveRoot = mode === 'list' ? grid : list;
            if (!root) return;

            if (inactiveRoot) {
                inactiveRoot.querySelectorAll('img[data-project-cover="true"]').forEach(function (img) {
                    img.fetchPriority = 'low';
                    img.dataset.priorityCover = 'false';
                });
            }

            root.querySelectorAll('img[data-project-cover="true"]').forEach(function (img, index) {
                var priority = index < 6;
                if (priority) {
                    img.loading = 'eager';
                    img.decoding = 'sync';
                    img.fetchPriority = 'high';
                    img.dataset.priorityCover = 'true';
                } else {
                    img.fetchPriority = 'low';
                    img.dataset.priorityCover = 'false';
                }

                if (priority && img.decode && (!img.complete || !img.naturalWidth)) {
                    img.decode().catch(function () {});
                }
            });
        }

        // ── Search ────────────────────────────────────────────────────────
        function applySearch(raw) {
            var q = raw.trim().toLowerCase();
            var visible = 0;

            document.querySelectorAll('.project-card, .project-list-item').forEach(function (el) {
                var match = !q || (el.dataset.name || '').includes(q);
                if (match) { el.classList.remove('pv-hidden'); visible++; }
                else        { el.classList.add('pv-hidden'); }
            });

            if (noResults) noResults.style.display = (visible === 0 && q) ? 'block' : 'none';
        }

        var searchInput = document.getElementById('project-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () { applySearch(this.value); });
        }

        // ── Init: restore saved preference ────────────────────────────────
        var saved = 'grid';
        try { saved = localStorage.getItem(LS_KEY) || 'grid'; } catch(e) {}
        setView(saved);

    })();
    </script>
    @endpush

</x-app-layout>
