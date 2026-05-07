<x-app-layout>
    <x-slot name="header">
        @php
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        @endphp
        <div class="flex items-center gap-2 text-sm min-w-0">
            <span class="text-gray-500 flex-shrink-0">Calendario</span>
            <span class="text-gray-600 flex-shrink-0">›</span>
            <span class="text-white font-semibold">
                {{ $meses[$mesActual->month] }} {{ $mesActual->year }}
            </span>
        </div>
    </x-slot>

    {{--
        Layout: h-full flex-col
        ├── Toolbar (flex-shrink-0)
        └── Three columns (flex-1 min-h-0 flex overflow-hidden)
            ├── Left panel  288px
            ├── Main calendar flex-1 (flat grid: DOW headers + all cells)
            └── Right panel 320px  (hidden until day selected)
    --}}

    @php
        $meses     = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $navParams = array_filter(['proyecto_id' => $proyectoId]);
        $numWeeks  = $days->chunk(7)->count();
    @endphp

    <div class="h-full flex flex-col" style="overflow:hidden;">

        {{-- ═══ TOOLBAR ════════════════════════════════════════════════ --}}
        <div style="flex-shrink:0; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
                    padding:10px 24px; border-bottom:1px solid rgba(255,255,255,0.07);
                    background:rgba(15,17,23,0.7); backdrop-filter:blur(20px);">

            {{-- Breadcrumb-style month title --}}
            <div style="display:flex; align-items:center; gap:6px; font-size:13px; color:rgb(100,116,139);">
                <span class="material-symbols-outlined" style="font-size:16px;">calendar_month</span>
                <span style="color:white; font-weight:700; font-size:14px;">
                    {{ $meses[$mesActual->month] }} {{ $mesActual->year }}
                </span>
            </div>

            {{-- Today + prev/next in one pill --}}
            <div style="display:flex; align-items:center; gap:2px; background:rgba(255,255,255,0.05);
                        padding:4px; border-radius:10px; border:1px solid rgba(255,255,255,0.07);">
                <a href="{{ route('calendario.index', $navParams) }}"
                   style="padding:4px 12px; border-radius:6px; font-size:12px; font-weight:600;
                          text-decoration:none; transition:all 0.15s; white-space:nowrap;
                          {{ $mesActual->isCurrentMonth()
                                ? 'background:rgba(59,130,246,0.2);color:rgb(96,165,250);'
                                : 'color:rgb(148,163,184);' }}"
                   onmouseover="if(!this.style.background.includes('59,130'))this.style.background='rgba(255,255,255,0.07)'"
                   onmouseout="if(!this.style.background.includes('59,130'))this.style.background=''">
                    Hoy
                </a>
                <a href="{{ route('calendario.index', array_merge($navParams, ['mes' => $mesAnterior])) }}"
                   style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;
                          border-radius:6px; color:rgb(148,163,184); text-decoration:none; transition:all 0.15s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='white'"
                   onmouseout="this.style.background='';this.style.color='rgb(148,163,184)'">
                    <span class="material-symbols-outlined" style="font-size:20px;">chevron_left</span>
                </a>
                <a href="{{ route('calendario.index', array_merge($navParams, ['mes' => $mesSiguiente])) }}"
                   style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;
                          border-radius:6px; color:rgb(148,163,184); text-decoration:none; transition:all 0.15s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='white'"
                   onmouseout="this.style.background='';this.style.color='rgb(148,163,184)'">
                    <span class="material-symbols-outlined" style="font-size:20px;">chevron_right</span>
                </a>
            </div>

            <div style="flex:1;"></div>

            {{-- Type filters --}}
            <div id="type-filters"
                 style="display:flex; align-items:center; gap:4px;
                        background:rgba(255,255,255,0.05); padding:4px; border-radius:10px;
                        border:1px solid rgba(255,255,255,0.07);">
                <button class="cal-filter-btn cal-filter-active" data-filter="all">Todos</button>
                <button class="cal-filter-btn" data-filter="tarea">
                    <span style="width:6px;height:6px;border-radius:50%;background:#60a5fa;
                                 display:inline-block;margin-right:4px;"></span>Tareas
                </button>
                <button class="cal-filter-btn" data-filter="sprint">
                    <span style="width:6px;height:6px;border-radius:50%;background:#a78bfa;
                                 display:inline-block;margin-right:4px;"></span>Sprints
                </button>
                <button class="cal-filter-btn" data-filter="proyecto">
                    <span style="width:6px;height:6px;border-radius:50%;background:#34d399;
                                 display:inline-block;margin-right:4px;"></span>Proyectos
                </button>
            </div>

            {{-- Project selector --}}
            <form method="GET" action="{{ route('calendario.index') }}">
                @if(request('mes'))
                    <input type="hidden" name="mes" value="{{ request('mes') }}">
                @endif
                <div style="position:relative;">
                    <span class="material-symbols-outlined"
                          style="position:absolute;left:8px;top:50%;transform:translateY(-50%);
                                 font-size:14px;color:rgb(100,116,139);pointer-events:none;">folder_open</span>
                    <select name="proyecto_id" onchange="this.form.submit()"
                            style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
                                   border-radius:8px; padding:6px 8px 6px 28px; font-size:12px;
                                   color:white; cursor:pointer; min-width:150px; outline:none;
                                   appearance:none; font-family:inherit;">
                        <option value="" style="background:#0f1117;">Todos los proyectos</option>
                        @foreach($proyectos as $proy)
                        <option value="{{ $proy->id }}" style="background:#0f1117;"
                                {{ $proyectoId == $proy->id ? 'selected' : '' }}>
                            {{ $proy->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>

        </div>

        {{-- ═══ THREE-COLUMN BODY ══════════════════════════════════════ --}}
        <div style="flex:1; min-height:0; display:flex; overflow:hidden;">

            {{-- ─── LEFT PANEL (288px) ─────────────────────────────── --}}
            <aside style="width:288px; flex-shrink:0; overflow-y:auto;
                          border-right:1px solid rgba(255,255,255,0.05);
                          background:rgba(1,15,31,0.85);
                          padding:24px 16px; display:flex; flex-direction:column; gap:28px;">

                {{-- Mini calendar ──────────────────────────── --}}
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                        <span style="font-weight:700; font-size:13px; color:rgb(212,228,250);">
                            {{ $meses[$mesActual->month] }} {{ $mesActual->year }}
                        </span>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('calendario.index', array_merge($navParams, ['mes' => $mesAnterior])) }}"
                               style="color:rgb(100,116,139); text-decoration:none; line-height:1;"
                               onmouseover="this.style.color='white'" onmouseout="this.style.color='rgb(100,116,139)'">
                                <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
                            </a>
                            <a href="{{ route('calendario.index', array_merge($navParams, ['mes' => $mesSiguiente])) }}"
                               style="color:rgb(100,116,139); text-decoration:none; line-height:1;"
                               onmouseover="this.style.color='white'" onmouseout="this.style.color='rgb(100,116,139)'">
                                <span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
                            </a>
                        </div>
                    </div>
                    {{-- DOW letters: D L M X J V S --}}
                    <div style="display:grid; grid-template-columns:repeat(7,1fr);
                                text-align:center; font-size:10px; font-weight:700;
                                color:rgb(100,116,139); margin-bottom:6px;">
                        @foreach(['D','L','M','X','J','V','S'] as $l)
                        <div>{{ $l }}</div>
                        @endforeach
                    </div>
                    {{-- Mini day cells --}}
                    <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:2px 0; text-align:center;">
                        @foreach($days as $md)
                        <button class="mini-day"
                                data-date="{{ $md['date'] }}"
                                style="width:28px; height:28px; display:flex; align-items:center;
                                       justify-content:center; margin:0 auto; border:none;
                                       border-radius:50%; cursor:pointer; font-size:11px;
                                       font-weight:{{ $md['isToday'] ? '700' : '400' }};
                                       {{ $md['isToday']
                                           ? 'background:#3b82f6;color:white;'
                                           : ($md['inMonth']
                                               ? 'background:transparent;color:rgb(100,116,139);'
                                               : 'background:transparent;color:rgb(51,65,85);') }}
                                       transition:background 0.1s;">
                            {{ $md['day'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- CALENDARIOS / categorías ─────────────── --}}
                <div>
                    <h3 style="font-size:10px; font-weight:700; text-transform:uppercase;
                               letter-spacing:0.08em; color:rgb(100,116,139);
                               margin-bottom:14px; display:flex; align-items:center;
                               justify-content:space-between;">
                        CALENDARIOS
                    </h3>
                    <ul style="list-style:none; padding:0; margin:0;
                               display:flex; flex-direction:column; gap:10px;">
                        <li style="display:flex; align-items:center; gap:10px;
                                   font-size:13px; color:rgb(203,213,225);">
                            <span style="width:10px;height:10px;border-radius:50%;
                                         background:#60a5fa;flex-shrink:0;"></span>
                            Mis Tareas
                        </li>
                        <li style="display:flex; align-items:center; gap:10px;
                                   font-size:13px; color:rgb(203,213,225);">
                            <span style="width:10px;height:10px;border-radius:50%;
                                         background:#a78bfa;flex-shrink:0;"></span>
                            Sprints activos
                        </li>
                        <li style="display:flex; align-items:center; gap:10px;
                                   font-size:13px; color:rgb(203,213,225);">
                            <span style="width:10px;height:10px;border-radius:50%;
                                         background:#34d399;flex-shrink:0;"></span>
                            Proyectos / Hitos
                        </li>
                        <li style="display:flex; align-items:center; gap:10px;
                                   font-size:13px; color:rgb(203,213,225);">
                            <span style="width:10px;height:10px;border-radius:50%;
                                         background:#f87171;flex-shrink:0;"></span>
                            Vencidos
                        </li>
                    </ul>
                </div>

                {{-- PRÓXIMOS eventos ─────────────────────── --}}
                <div style="flex:1;">
                    <h3 style="font-size:10px; font-weight:700; text-transform:uppercase;
                               letter-spacing:0.08em; color:rgb(100,116,139); margin-bottom:14px;">
                        PRÓXIMOS
                    </h3>
                    @if($upcoming->isNotEmpty())
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        @foreach($upcoming->take(5) as $ev)
                        @php
                            $evAccent = match(true) {
                                $ev['vencida']             => '#f87171',
                                $ev['tipo'] === 'sprint'   => '#a78bfa',
                                $ev['tipo'] === 'proyecto' => '#34d399',
                                default                    => '#60a5fa',
                            };
                        @endphp
                        <a href="{{ $ev['url'] }}"
                           style="display:block; padding:10px 12px; border-radius:10px;
                                  background:rgba(255,255,255,0.03);
                                  border:1px solid rgba(255,255,255,0.07);
                                  border-left:3px solid {{ $evAccent }};
                                  text-decoration:none; transition:background 0.15s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.06)'"
                           onmouseout="this.style.background='rgba(255,255,255,0.03)'">
                            <p style="font-size:12px; font-weight:600; color:rgb(212,228,250);
                                      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                                      margin-bottom:3px; line-height:1.3;">
                                {{ $ev['titulo'] }}
                            </p>
                            <p style="font-size:10px; color:rgb(100,116,139);">
                                {{ \Carbon\Carbon::parse($ev['fecha'])->format('d M Y') }}
                            </p>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div style="text-align:center; padding:20px 0;">
                        <span class="material-symbols-outlined"
                              style="font-size:28px; color:rgb(51,65,85); display:block; margin-bottom:6px;">
                            event_available
                        </span>
                        <p style="font-size:11px; color:rgb(71,85,105);">Sin eventos próximos.</p>
                    </div>
                    @endif
                </div>

            </aside>

            {{-- ─── MAIN CALENDAR (flex-1) ─────────────────────────── --}}
            <section style="flex:1; overflow:hidden; display:flex; flex-direction:column; min-width:0;">

                {{-- DOW header row (DOM LUN MAR MIÉ JUE VIE SÁB) --}}
                <div style="display:grid; grid-template-columns:repeat(7,minmax(0,1fr));
                            border-bottom:1px solid rgba(255,255,255,0.1);
                            background:rgba(13,28,45,0.7); flex-shrink:0;">
                    @foreach(['DOM','LUN','MAR','MIÉ','JUE','VIE','SÁB'] as $i => $dow)
                    <div style="padding:10px 8px; text-align:center; font-size:11px;
                                font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
                                color:{{ $i >= 5 ? 'rgb(51,65,85)' : 'rgb(100,116,139)' }};
                                {{ $i < 6 ? 'border-right:1px solid rgba(255,255,255,0.05);' : '' }}">
                        {{ $dow }}
                    </div>
                    @endforeach
                </div>

                {{-- Flat calendar grid: all day cells in one grid --}}
                <div style="flex:1; overflow:auto; display:grid;
                            grid-template-columns:repeat(7,minmax(0,1fr));
                            grid-template-rows:repeat({{ $numWeeks }},minmax(0,1fr));">

                    @if($days->isEmpty())
                    <div style="grid-column:span 7; display:flex; flex-direction:column;
                                align-items:center; justify-content:center; padding:40px; text-align:center;">
                        <span class="material-symbols-outlined"
                              style="font-size:36px; color:rgb(51,65,85); margin-bottom:10px;">
                            calendar_month
                        </span>
                        <p style="font-size:13px; color:rgb(71,85,105);">No hay datos de calendario.</p>
                    </div>
                    @else

                    @foreach($days as $day)
                    @php
                        $dayEvs   = $day['events'];
                        $shown    = $dayEvs->take(3);
                        $extra    = max(0, $dayEvs->count() - 3);
                        // Cell background
                        if ($day['isToday']) {
                            $cellBg = 'background:rgba(59,130,246,0.07);';
                        } else {
                            $cellBg = '';
                        }
                        $cellOp = $day['inMonth'] ? '' : 'opacity:0.35;';
                    @endphp
                    <div class="cal-cell"
                         data-date="{{ $day['date'] }}"
                         style="border-right:1px solid rgba(255,255,255,0.05);
                                border-bottom:1px solid rgba(255,255,255,0.05);
                                padding:8px; cursor:pointer; position:relative;
                                transition:background 0.1s; overflow:hidden;
                                {{ $cellBg }}{{ $cellOp }}"
                         onmouseover="if(!this.classList.contains('selected'))this.style.background='rgba(255,255,255,0.03)'"
                         onmouseout="if(!this.classList.contains('selected'))this.style.background='{{ $day['isToday'] ? 'rgba(59,130,246,0.07)' : '' }}'">

                        {{-- Day number --}}
                        @if($day['isToday'])
                        <div style="display:inline-flex; align-items:center; justify-content:center;
                                    width:26px; height:26px; background:#3b82f6; border-radius:50%;
                                    font-size:12px; font-weight:700; color:white; margin-bottom:4px;">
                            {{ $day['day'] }}
                        </div>
                        @else
                        <div style="font-size:12px; font-weight:500; margin-bottom:4px;
                                    color:{{ $day['inMonth'] ? 'rgb(100,116,139)' : 'rgb(51,65,85)' }};">
                            {{ $day['day'] }}
                        </div>
                        @endif

                        {{-- Events container: clips at cell boundary, never causes row growth --}}
                        <div style="overflow:hidden; display:flex; flex-direction:column; gap:2px;">

                            @foreach($shown as $ev)
                            @php
                                if ($ev['vencida']) {
                                    $css = 'background:rgba(239,68,68,0.15);color:rgb(252,165,165);border:1px solid rgba(239,68,68,0.25);';
                                } elseif ($ev['tipo'] === 'sprint') {
                                    $css = 'background:rgba(139,92,246,0.15);color:rgb(196,181,253);border:1px solid rgba(139,92,246,0.25);';
                                } elseif ($ev['tipo'] === 'proyecto') {
                                    $css = 'background:rgba(16,185,129,0.15);color:rgb(110,231,183);border:1px solid rgba(16,185,129,0.25);';
                                } else {
                                    $css = 'background:rgba(59,130,246,0.15);color:rgb(147,197,253);border:1px solid rgba(59,130,246,0.25);';
                                }
                            @endphp
                            <div class="cal-chip"
                                 data-tipo="{{ $ev['tipo'] }}"
                                 style="font-size:10px; font-weight:500; line-height:1.3;
                                        padding:1px 5px; border-radius:4px;
                                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                                        flex-shrink:0; {{ $css }}">
                                {{ $ev['titulo'] }}
                            </div>
                            @endforeach

                            @if($extra > 0)
                            {{-- "+X más" chip — styled as subtle pill, updated by JS on filter change --}}
                            <div class="cal-extra"
                                 style="display:inline-flex; align-self:flex-start;
                                        font-size:10px; font-weight:600; line-height:1.3;
                                        padding:1px 6px; border-radius:4px;
                                        background:rgba(255,255,255,0.06);
                                        border:1px solid rgba(255,255,255,0.1);
                                        color:rgb(148,163,184);
                                        white-space:nowrap; flex-shrink:0; cursor:pointer;">
                                +{{ $extra }} más
                            </div>
                            @endif

                        </div>{{-- /events container --}}

                    </div>
                    @endforeach

                    @endif
                </div>

                {{-- Empty-month notice (overlaid below grid) --}}
                @php $totalMes = $days->sum(fn($d) => $d['events']->count()); @endphp
                @if($totalMes === 0 && $days->isNotEmpty())
                <div style="position:absolute; left:50%; transform:translateX(-50%);
                            bottom:16px; pointer-events:none;
                            background:rgba(15,17,23,0.9); border:1px solid rgba(255,255,255,0.07);
                            border-radius:12px; padding:10px 20px; white-space:nowrap;">
                    <p style="font-size:12px; color:rgb(71,85,105); text-align:center;">
                        Sin eventos este mes — crea tareas o sprints con fechas
                    </p>
                </div>
                @endif

            </section>

            {{-- ─── RIGHT PANEL (320px, hidden until day selected) ─── --}}
            <div id="day-panel"
                 style="display:none; width:320px; flex-shrink:0;
                        border-left:1px solid rgba(255,255,255,0.08);
                        background:rgba(18,33,49,0.95);
                        flex-direction:column; overflow:hidden;">

                {{-- Panel header --}}
                <div style="flex-shrink:0; padding:20px 20px 16px;
                            border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                        <div style="min-width:0;">
                            <p id="panel-weekday"
                               style="font-size:10px; font-weight:700; text-transform:uppercase;
                                      letter-spacing:0.08em; color:#60a5fa; margin-bottom:4px;"></p>
                            <p id="panel-fulldate"
                               style="font-size:22px; font-weight:900; color:white;
                                      line-height:1.1; margin-bottom:6px;"></p>
                            <p id="panel-count"
                               style="font-size:11px; color:rgb(100,116,139);"></p>
                        </div>
                        <button id="panel-close"
                                style="width:28px; height:28px; display:flex; align-items:center;
                                       justify-content:center; border-radius:8px; border:none;
                                       background:transparent; color:rgb(71,85,105); cursor:pointer;
                                       flex-shrink:0; transition:all 0.15s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='rgb(148,163,184)'"
                                onmouseout="this.style.background='transparent';this.style.color='rgb(71,85,105)'">
                            <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                        </button>
                    </div>
                </div>

                {{-- Section label --}}
                <div style="padding:16px 20px 8px; flex-shrink:0;">
                    <p style="font-size:10px; font-weight:700; text-transform:uppercase;
                               letter-spacing:0.08em; color:rgb(71,85,105);">A CONTINUACIÓN</p>
                </div>

                {{-- Events list --}}
                <div id="panel-events"
                     style="flex:1; overflow-y:auto; padding:0 12px 16px;
                            display:flex; flex-direction:column; gap:10px;">
                    {{-- Populated by JS --}}
                </div>

            </div>

        </div>
    </div>

    @push('scripts')
    <style>
        /* ── Filter chip buttons ─────────────────────────────────────── */
        .cal-filter-btn {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            color: rgb(148,163,184);
            background: transparent;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            font-family: inherit;
        }
        .cal-filter-btn:hover {
            background: rgba(255,255,255,0.07);
            color: white;
        }
        .cal-filter-btn.cal-filter-active {
            background: rgba(59,130,246,0.2);
            color: rgb(96,165,250);
        }
        [data-filter="sprint"].cal-filter-active {
            background: rgba(139,92,246,0.2);
            color: rgb(167,139,250);
        }
        [data-filter="proyecto"].cal-filter-active {
            background: rgba(16,185,129,0.2);
            color: rgb(52,211,153);
        }
        /* ── Selected day cell ───────────────────────────────────────── */
        .cal-cell.selected {
            background: rgba(59,130,246,0.1) !important;
            box-shadow: inset 0 0 0 1px rgba(59,130,246,0.4);
        }
        /* ── Mini calendar day hover ─────────────────────────────────── */
        .mini-day:hover {
            background: rgba(255,255,255,0.07) !important;
            color: white !important;
        }
        /* ── Scrollbar ───────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
        }
    </style>

    <script>
    (function () {
        'use strict';

        // All events grouped by date (from controller)
        var allEvents    = @json($eventsByDate);
        var activeFilter = 'all';
        var selectedDate = null;

        // ── Type filter chips ────────────────────────────────────────
        document.querySelectorAll('.cal-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeFilter = this.dataset.filter;
                document.querySelectorAll('.cal-filter-btn')
                    .forEach(function (b) { b.classList.remove('cal-filter-active'); });
                this.classList.add('cal-filter-active');
                applyTypeFilter();
                if (selectedDate) {
                    populatePanel(selectedDate, allEvents[selectedDate] || []);
                }
            });
        });

        function applyTypeFilter() {
            document.querySelectorAll('.cal-cell').forEach(function (cell) {
                var chips = cell.querySelectorAll('.cal-chip');
                var visibleCount = 0;

                // Show or hide each rendered chip
                chips.forEach(function (chip) {
                    var show = (activeFilter === 'all' || chip.dataset.tipo === activeFilter);
                    chip.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                // Recalculate "+X más" counter for this cell based on the active filter
                var extra = cell.querySelector('.cal-extra');
                if (extra) {
                    var date = cell.dataset.date;
                    var dayAll = allEvents[date] || [];
                    var totalFiltered = activeFilter === 'all'
                        ? dayAll.length
                        : dayAll.filter(function (e) { return e.tipo === activeFilter; }).length;
                    var remaining = Math.max(0, totalFiltered - visibleCount);
                    if (remaining > 0) {
                        extra.textContent = '+' + remaining + ' más';
                        extra.style.display = '';
                    } else {
                        extra.style.display = 'none';
                    }
                }
            });
        }

        // ── Main calendar cell clicks ────────────────────────────────
        document.querySelectorAll('.cal-cell').forEach(function (cell) {
            cell.addEventListener('click', function () {
                var date = this.dataset.date;
                document.querySelectorAll('.cal-cell')
                    .forEach(function (c) { c.classList.remove('selected'); });
                this.classList.add('selected');
                selectedDate = date;
                populatePanel(date, allEvents[date] || []);
                openPanel();
            });
        });

        // ── Mini calendar day clicks ─────────────────────────────────
        document.querySelectorAll('.mini-day').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var date = this.dataset.date;
                var mainCell = document.querySelector('.cal-cell[data-date="' + date + '"]');
                document.querySelectorAll('.cal-cell')
                    .forEach(function (c) { c.classList.remove('selected'); });
                if (mainCell) {
                    mainCell.classList.add('selected');
                    mainCell.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                selectedDate = date;
                populatePanel(date, allEvents[date] || []);
                openPanel();
            });
        });

        // ── Panel open/close ─────────────────────────────────────────
        function openPanel() {
            var p = document.getElementById('day-panel');
            p.style.display = 'flex';
            p.style.flexDirection = 'column';
        }

        document.getElementById('panel-close')?.addEventListener('click', function () {
            document.getElementById('day-panel').style.display = 'none';
            document.querySelectorAll('.cal-cell')
                .forEach(function (c) { c.classList.remove('selected'); });
            selectedDate = null;
        });

        // ── Populate right panel ─────────────────────────────────────
        function populatePanel(date, events) {
            var d = new Date(date + 'T12:00:00');

            var weekday  = d.toLocaleDateString('es-CO', { weekday: 'long' });
            var fulldate = d.toLocaleDateString('es-CO', {
                day: 'numeric', month: 'long', year: 'numeric'
            });

            var wdEl = document.getElementById('panel-weekday');
            var fdEl = document.getElementById('panel-fulldate');
            var coEl = document.getElementById('panel-count');
            if (wdEl) wdEl.textContent = cap(weekday);
            if (fdEl) fdEl.textContent = cap(fulldate);

            var filtered = activeFilter === 'all'
                ? events
                : events.filter(function (e) { return e.tipo === activeFilter; });

            if (coEl) coEl.textContent =
                filtered.length + (filtered.length === 1 ? ' Evento' : ' Eventos') +
                (filtered.some(function(e){ return e.vencida; })
                    ? ' • ' + filtered.filter(function(e){ return e.vencida; }).length + ' Vencido(s)'
                    : '');

            var container = document.getElementById('panel-events');
            container.innerHTML = '';

            if (filtered.length === 0) {
                container.innerHTML =
                    '<div style="display:flex;flex-direction:column;align-items:center;' +
                    'justify-content:center;padding:40px 20px;text-align:center;">' +
                    '<span class="material-symbols-outlined" style="font-size:32px;' +
                    'color:rgb(51,65,85);margin-bottom:10px;">event_busy</span>' +
                    '<p style="font-size:12px;color:rgb(71,85,105);font-weight:500;">Sin eventos para este día.</p>' +
                    '<p style="font-size:11px;color:rgb(51,65,85);margin-top:4px;">Selecciona otro día del calendario.</p>' +
                    '</div>';
                return;
            }

            filtered.forEach(function (ev) {
                container.appendChild(buildCard(ev));
            });
        }

        // ── Build event card ─────────────────────────────────────────
        function buildCard(ev) {
            var div = document.createElement('div');

            var accentColor, bgColor, badgeBg, badgeColor, icon, tipoLabel;

            if (ev.vencida) {
                accentColor = '#ef4444'; bgColor = 'rgba(239,68,68,0.07)';
                badgeBg = 'rgba(239,68,68,0.2)'; badgeColor = '#fca5a5';
                icon = 'warning'; tipoLabel = 'VENCIDA';
            } else if (ev.tipo === 'sprint') {
                accentColor = '#8b5cf6'; bgColor = 'rgba(139,92,246,0.07)';
                badgeBg = 'rgba(139,92,246,0.2)'; badgeColor = '#c4b5fd';
                icon = 'sprint'; tipoLabel = 'SPRINT';
            } else if (ev.tipo === 'proyecto') {
                accentColor = '#10b981'; bgColor = 'rgba(16,185,129,0.07)';
                badgeBg = 'rgba(16,185,129,0.2)'; badgeColor = '#6ee7b7';
                icon = 'folder_open'; tipoLabel = 'PROYECTO';
            } else {
                accentColor = '#3b82f6'; bgColor = 'rgba(59,130,246,0.07)';
                badgeBg = 'rgba(59,130,246,0.2)'; badgeColor = '#93c5fd';
                icon = 'task_alt'; tipoLabel = 'TAREA';
            }

            // flex-shrink:0 is the critical fix — prevents the parent flex container
            // from compressing cards when there are many events. Without it, all cards
            // squeeze to fit the panel height instead of triggering overflow-y scroll.
            div.style.cssText =
                'background:' + bgColor + ';' +
                'border:1px solid rgba(255,255,255,0.08);' +
                'border-left:4px solid ' + accentColor + ';' +
                'border-radius:12px;' +
                'padding:12px 12px 12px 12px;' +
                'flex-shrink:0;' +
                'min-height:84px;' +
                'display:flex;' +
                'flex-direction:column;' +
                'gap:6px;' +
                'transition:transform 0.15s;';
            div.onmouseenter = function () { this.style.transform = 'translateX(2px)'; };
            div.onmouseleave = function () { this.style.transform = ''; };

            div.innerHTML =
                // Row 1: type badge
                '<div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">' +
                '  <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:999px;' +
                '               background:' + badgeBg + ';color:' + badgeColor + ';' +
                '               letter-spacing:0.06em;flex-shrink:0;">' +
                tipoLabel + '</span>' +
                '</div>' +
                // Row 2: title (wraps freely, never clipped)
                '<h4 style="font-size:13px;font-weight:700;color:white;line-height:1.45;' +
                '           word-break:break-word;margin:0;flex-shrink:0;">' +
                '<a href="' + escHtml(ev.url) + '" style="color:inherit;text-decoration:none;"' +
                ' onmouseover="this.style.textDecoration=\'underline\'"' +
                ' onmouseout="this.style.textDecoration=\'none\'">' +
                escHtml(ev.titulo) + '</a></h4>' +
                // Row 3: icon + project name
                '<div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">' +
                '  <span class="material-symbols-outlined" ' +
                '        style="font-size:12px;color:' + accentColor + ';opacity:0.6;flex-shrink:0;">' +
                icon + '</span>' +
                '  <span style="font-size:11px;color:rgb(100,116,139);' +
                '               overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' +
                escHtml(ev.proyecto) + '</span>' +
                '</div>' +
                // Row 4: estado (optional)
                (ev.estado
                    ? '<p style="font-size:10px;color:rgb(71,85,105);margin:0;flex-shrink:0;">' +
                      escHtml(ev.estado) + '</p>'
                    : '');

            return div;
        }

        function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
        function escHtml(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(s || '')));
            return d.innerHTML;
        }

    })();
    </script>
    @endpush

</x-app-layout>
