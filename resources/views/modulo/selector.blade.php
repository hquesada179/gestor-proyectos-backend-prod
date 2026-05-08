<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">
                {{ __('app.nav.dashboard') }}
            </a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">{{ $info['label'] }}</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        /* ── Grid layout (3-col responsive via native CSS) ─────────────── */
        #projectsGrid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
        }
        @media (min-width: 768px)  { #projectsGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { #projectsGrid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

        /* ── List layout ───────────────────────────────────────────────── */
        #projectsList { display: flex; flex-direction: column; gap: .625rem; }

        /* ── Hidden state ──────────────────────────────────────────────── */
        .pv-hidden { display: none !important; }

        /* ── Toggle button states ──────────────────────────────────────── */
        .view-btn       { padding:6px; border-radius:8px; border:none; cursor:pointer;
                          background:transparent; color:#64748b; transition: background .15s, color .15s; }
        .view-btn:hover { background:rgba(255,255,255,0.07); color:#e2e8f0; }
        .view-btn.active{ background:rgba(99,102,241,0.25); color:#a5b4fc; }

        #project-search:focus { border-color:rgba(99,102,241,0.5) !important; }

        .project-card.pv-hidden,
        .project-list-item.pv-hidden { display: none !important; }
    </style>
    @endpush

    @php
        $moduleIcons = [
            'sprints'        => 'sprint',
            'tareas'         => 'task_alt',
            'requerimientos' => 'checklist',
            'insumos'        => 'inventory_2',
        ];
        $countIcon = $moduleIcons[$modulo] ?? 'tag';
    @endphp

    <div style="padding: 1.5rem;">

        {{-- ── Page header ────────────────────────────────────────────────── --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <div>
                <h1 style="font-size:1.2rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">
                    {{ $info['label'] }}
                </h1>
                <p style="font-size:12px; color:#64748b; margin:0;">
                    {{ $info['desc'] }} {{ __('app.selector.choose_project') }}
                </p>
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
                    <button id="btn-grid" class="view-btn active" onclick="setView('grid')" title="Vista bloques">
                        <span class="material-symbols-outlined" style="font-size:18px;display:block;">grid_view</span>
                    </button>
                    <button id="btn-list" class="view-btn" onclick="setView('list')" title="Vista lista">
                        <span class="material-symbols-outlined" style="font-size:18px;display:block;">view_list</span>
                    </button>
                </div>
            </div>
        </div>

        @if($proyectos->isEmpty())
            <div style="border-radius:1rem;border:1px solid rgba(255,255,255,0.06);
                        background:rgba(255,255,255,0.02);padding:4rem;text-align:center;">
                <span class="material-symbols-outlined" style="font-size:44px;color:#374151;display:block;margin-bottom:12px;">folder_open</span>
                <p style="font-size:14px;color:#6b7280;margin:0 0 12px;">{{ __('app.empty.no_reg_projects') }}</p>
                <a href="{{ route('proyectos.create') }}" style="font-size:12px;color:#818cf8;text-decoration:underline;">
                    {{ __('app.actions.create_first_project') }}
                </a>
            </div>
        @else
            <p style="font-size:11px;color:#475569;margin-bottom:1rem;">
                {{ $proyectos->count() }} {{ $proyectos->count() === 1 ? 'proyecto' : 'proyectos' }}
            </p>

            {{-- ── GRID VIEW ──────────────────────────────────────────────── --}}
            <div id="projectsGrid">
                @foreach($proyectos as $proyecto)
                    <x-project-module-card
                        :proyecto="$proyecto"
                        :countValue="$proyecto->{$info['conteo']}"
                        countLabel="{{ $info['conteo_label'] }}"
                        countIcon="{{ $countIcon }}"
                        actionLabel="{{ __('app.actions.enter') }}"
                        :actionUrl="route($info['ruta'], $proyecto)"
                    />
                @endforeach
            </div>

            {{-- ── LIST VIEW ──────────────────────────────────────────────── --}}
            <div id="projectsList" class="pv-hidden">
                @foreach($proyectos as $proyecto)
                    <x-project-module-list-item
                        :proyecto="$proyecto"
                        :countValue="$proyecto->{$info['conteo']}"
                        countLabel="{{ $info['conteo_label'] }}"
                        countIcon="{{ $countIcon }}"
                        actionLabel="{{ __('app.actions.enter') }}"
                        :actionUrl="route($info['ruta'], $proyecto)"
                    />
                @endforeach
            </div>

            <p id="no-results" style="display:none;text-align:center;font-size:13px;color:#475569;padding:2rem 0;">
                Sin proyectos para esa búsqueda.
            </p>
        @endif

        <div style="margin-top:1.5rem;">
            <a href="{{ route('dashboard') }}"
               style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#475569;text-decoration:none;"
               onmouseover="this.style.color='#9ca3af'"
               onmouseout="this.style.color='#475569'">
                <span class="material-symbols-outlined" style="font-size:14px;">arrow_back</span>
                {{ __('app.actions.back_panel') }}
            </a>
        </div>

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
            var isGrid = mode === 'grid';

            if (grid)  { if (isGrid) grid.classList.remove('pv-hidden'); else grid.classList.add('pv-hidden'); }
            if (list)  { if (isGrid) list.classList.add('pv-hidden');    else list.classList.remove('pv-hidden'); }

            if (btnGrid) { if (isGrid) btnGrid.classList.add('active');  else btnGrid.classList.remove('active'); }
            if (btnList) { if (isGrid) btnList.classList.remove('active'); else btnList.classList.add('active'); }

            try { localStorage.setItem(LS_KEY, mode); } catch(e) {}

            applySearch((document.getElementById('project-search') || {}).value || '');
        };

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
