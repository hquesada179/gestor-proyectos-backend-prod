<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm min-w-0">
            <a href="{{ route('proyectos.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors flex-shrink-0">Proyectos</a>
            <span class="text-gray-600">›</span>
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-gray-500 hover:text-gray-300 transition-colors truncate max-w-[160px]">{{ $proyecto->nombre }}</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">Actividad</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .activity-card { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:12px; transition:border-color .15s, background .15s; }
        .activity-card:hover { background:rgba(255,255,255,0.045); border-color:rgba(255,255,255,0.12); }
        .sb-input  { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#f1f5f9; border-radius:10px; font-size:12px; padding:7px 11px; outline:none; transition:border-color .15s; }
        .sb-input:focus { border-color:rgba(99,102,241,0.5); }
        .sb-select { background:#111827; border:1px solid rgba(255,255,255,0.1); color:#f1f5f9; border-radius:10px; font-size:12px; padding:7px 11px; outline:none; }
        .timeline-line { position:absolute; left:19px; top:36px; bottom:0; width:1px; background:rgba(255,255,255,0.06); }
        .module-chip { display:inline-flex; align-items:center; gap:3px; padding:1px 7px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; background:rgba(99,102,241,.1); color:#a5b4fc; border:1px solid rgba(99,102,241,.2); }
        .diff-table { width:100%; border-collapse:collapse; font-size:11px; }
        .diff-table th { padding:4px 8px; text-align:left; color:#64748b; font-weight:600; text-transform:uppercase; font-size:9px; letter-spacing:.05em; border-bottom:1px solid rgba(255,255,255,0.05); }
        .diff-table td { padding:5px 8px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,0.03); vertical-align:top; }
        .diff-old { color:#f87171; }
        .diff-new { color:#34d399; }
    </style>
    @endpush

    <div style="padding:1.5rem; max-width:960px; margin:0 auto;">

        {{-- Page header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
            <div>
                <h1 style="font-size:1.1rem; font-weight:800; color:#f1f5f9; margin:0 0 3px;">
                    <span class="material-symbols-outlined" style="font-size:18px; vertical-align:middle; color:#818cf8; margin-right:6px;">history</span>
                    Actividad del proyecto
                </h1>
                <p style="font-size:12px; color:#64748b; margin:0;">
                    Trazabilidad completa de cambios en <strong style="color:#94a3b8;">{{ $proyecto->nombre }}</strong>
                </p>
            </div>
            <div style="display:flex; gap:8px; flex-shrink:0;">
                <a href="{{ route('proyectos.show', $proyecto) }}"
                   style="display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#94a3b8; font-size:12px; font-weight:600; text-decoration:none; transition:all .15s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">
                    <span class="material-symbols-outlined" style="font-size:14px;">arrow_back</span>
                    Volver al proyecto
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('proyectos.actividad', $proyecto) }}"
              style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:12px 14px; margin-bottom:1.25rem;">
            <div style="position:relative; flex:2; min-width:180px;">
                <span class="material-symbols-outlined" style="position:absolute; left:9px; top:50%; transform:translateY(-50%); font-size:13px; color:#475569; pointer-events:none;">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar actividad…"
                       class="sb-input" style="padding-left:28px; width:100%;" />
            </div>
            <select name="user_id" class="sb-select" style="flex:1; min-width:140px;">
                <option value="">Todos los usuarios</option>
                @foreach($actors as $actor)
                <option value="{{ $actor->id }}" {{ request('user_id') == $actor->id ? 'selected' : '' }}>{{ $actor->name }}</option>
                @endforeach
            </select>
            <select name="module" class="sb-select" style="flex:1; min-width:130px;">
                <option value="">Todos los módulos</option>
                @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>
                    {{ match($mod) { 'proyectos'=>'Proyectos', 'tareas'=>'Tareas', 'requerimientos'=>'Requerimientos', 'historias'=>'Historias', 'sprints'=>'Sprints', 'insumos'=>'Insumos', 'equipo'=>'Equipo', 'invitaciones'=>'Invitaciones', default=>ucfirst($mod) } }}
                </option>
                @endforeach
            </select>
            <select name="action" class="sb-select" style="flex:1; min-width:130px;">
                <option value="">Todas las acciones</option>
                @foreach($actions as $act)
                <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                    {{ match($act) { 'created'=>'Creado', 'updated'=>'Editado', 'deleted'=>'Eliminado', 'changed_status'=>'Cambio de estado', 'invited'=>'Invitado', 'added_member'=>'Miembro agregado', 'removed_member'=>'Miembro eliminado', 'accepted_invitation'=>'Invitación aceptada', 'rejected_invitation'=>'Invitación rechazada', default=>ucfirst($act) } }}
                </option>
                @endforeach
            </select>
            <input type="date" name="fecha" value="{{ request('fecha') }}" class="sb-input" style="min-width:130px;" />
            <button type="submit"
                    style="padding:7px 14px; border-radius:9px; background:rgba(99,102,241,.2); color:#a5b4fc; border:1px solid rgba(99,102,241,.3); font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Filtrar
            </button>
            @if(request()->hasAny(['search','user_id','module','action','fecha']))
            <a href="{{ route('proyectos.actividad', $proyecto) }}"
               style="font-size:12px; color:#64748b; text-decoration:none; white-space:nowrap;">× Limpiar</a>
            @endif
        </form>

        {{-- Results count --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
            <p style="font-size:11px; color:#475569;">
                {{ $logs->total() }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }} registrados
                @if($logs->total() > 0) — página {{ $logs->currentPage() }} de {{ $logs->lastPage() }} @endif
            </p>
        </div>

        {{-- Timeline --}}
        @if($logs->isEmpty())
        <div style="padding:4rem; text-align:center; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px;">
            <span class="material-symbols-outlined" style="font-size:44px; color:#374151; display:block; margin-bottom:12px;">history_toggle_off</span>
            <p style="font-size:14px; color:#6b7280; margin:0 0 4px; font-weight:600;">Sin actividad registrada</p>
            <p style="font-size:12px; color:#4b5563; margin:0;">Los cambios que se hagan en este proyecto aparecerán aquí.</p>
        </div>
        @else

        {{-- Group by date --}}
        @php
            $grouped = $logs->getCollection()->groupBy(fn($log) => $log->created_at->format('Y-m-d'));
        @endphp

        <div x-data="{ detail: null }">

            @foreach($grouped as $date => $dayLogs)
            {{-- Date separator --}}
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:.75rem; margin-top:{{ $loop->first ? '0' : '1.5rem' }};">
                <div style="flex:1; height:1px; background:rgba(255,255,255,0.05);"></div>
                <span style="font-size:10px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; padding:2px 10px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:999px;">
                    {{ \Carbon\Carbon::parse($date)->isToday() ? 'Hoy' : (\Carbon\Carbon::parse($date)->isYesterday() ? 'Ayer' : \Carbon\Carbon::parse($date)->format('d \d\e M Y')) }}
                </span>
                <div style="flex:1; height:1px; background:rgba(255,255,255,0.05);"></div>
            </div>

            <div style="display:flex; flex-direction:column; gap:.5rem; margin-bottom:.5rem;">
                @foreach($dayLogs as $log)
                <div class="activity-card" style="padding:1rem 1.25rem;">
                    <div style="display:flex; align-items:flex-start; gap:.875rem;">

                        {{-- Action icon --}}
                        <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07);">
                            <span class="material-symbols-outlined" style="font-size:18px; color:{{ $log->actionColor() }}; font-variation-settings:'FILL' 1;">
                                {{ $log->actionIcon() }}
                            </span>
                        </div>

                        {{-- Content --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; flex-wrap:wrap; margin-bottom:4px;">
                                {{-- Title --}}
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    {{-- User avatar --}}
                                    @if($log->user)
                                    <div style="width:22px; height:22px; border-radius:50%; background:linear-gradient(135deg,#6d28d9,#818cf8); display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:900; color:white; flex-shrink:0;">
                                        {{ strtoupper(mb_substr($log->user->name, 0, 1)) }}
                                    </div>
                                    <span style="font-size:12px; font-weight:700; color:#e2e8f0;">{{ $log->user->name }}</span>
                                    @else
                                    <span style="font-size:12px; color:#64748b; font-style:italic;">Sistema</span>
                                    @endif
                                    <span class="module-chip">
                                        <span class="material-symbols-outlined" style="font-size:10px;">{{ $log->moduleIcon() }}</span>
                                        {{ $log->moduleLabel() }}
                                    </span>
                                </div>
                                {{-- Timestamp --}}
                                <span style="font-size:10px; color:#475569; flex-shrink:0; white-space:nowrap;" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </div>

                            {{-- Action title --}}
                            <p style="font-size:13px; color:#cbd5e1; margin:0 0 4px; line-height:1.4;">{{ $log->title }}</p>

                            {{-- Description --}}
                            @if($log->description)
                            <p style="font-size:11px; color:#64748b; margin:0 0 4px;">{{ $log->description }}</p>
                            @endif

                            {{-- Detail toggle --}}
                            @if($log->hasDetail())
                            <button @click="detail = (detail === {{ $log->id }}) ? null : {{ $log->id }}"
                                    style="margin-top:6px; display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#818cf8; background:none; border:none; cursor:pointer; padding:0;">
                                <span class="material-symbols-outlined" style="font-size:13px;" x-text="detail === {{ $log->id }} ? 'expand_less' : 'expand_more'">expand_more</span>
                                <span x-text="detail === {{ $log->id }} ? 'Ocultar detalle' : 'Ver detalle'">Ver detalle</span>
                            </button>

                            <div x-show="detail === {{ $log->id }}"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 style="display:none; margin-top:8px; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.06); border-radius:8px; overflow:hidden;">
                                <table class="diff-table">
                                    <thead>
                                        <tr>
                                            <th style="width:30%;">Campo</th>
                                            <th style="width:35%;">Antes</th>
                                            <th style="width:35%;">Después</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $oldV = $log->old_values ?? [];
                                            $newV = $log->new_values ?? [];
                                            $allKeys = array_unique(array_merge(array_keys($oldV), array_keys($newV)));
                                        @endphp
                                        @forelse($allKeys as $key)
                                        <tr>
                                            <td style="color:#94a3b8; font-weight:600;">{{ $key }}</td>
                                            <td class="diff-old">{{ isset($oldV[$key]) ? (is_array($oldV[$key]) ? json_encode($oldV[$key]) : $oldV[$key]) : '—' }}</td>
                                            <td class="diff-new">{{ isset($newV[$key]) ? (is_array($newV[$key]) ? json_encode($newV[$key]) : $newV[$key]) : '—' }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3" style="color:#475569; text-align:center; padding:8px;">Sin valores registrados</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @endif

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach

        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div style="margin-top:1.5rem; display:flex; justify-content:center; gap:6px; flex-wrap:wrap;">
            {{-- Previous --}}
            @if($logs->onFirstPage())
            <span style="padding:6px 14px; border-radius:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); color:#374151; font-size:12px; cursor:not-allowed;">← Anterior</span>
            @else
            <a href="{{ $logs->previousPageUrl() }}" style="padding:6px 14px; border-radius:8px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#94a3b8; font-size:12px; text-decoration:none; transition:all .15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">← Anterior</a>
            @endif

            {{-- Page numbers --}}
            @foreach($logs->getUrlRange(max(1, $logs->currentPage()-2), min($logs->lastPage(), $logs->currentPage()+2)) as $page => $url)
            @if($page == $logs->currentPage())
            <span style="padding:6px 12px; border-radius:8px; background:rgba(99,102,241,.2); border:1px solid rgba(99,102,241,.3); color:#a5b4fc; font-size:12px; font-weight:700;">{{ $page }}</span>
            @else
            <a href="{{ $url }}" style="padding:6px 12px; border-radius:8px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#94a3b8; font-size:12px; text-decoration:none; transition:all .15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">{{ $page }}</a>
            @endif
            @endforeach

            {{-- Next --}}
            @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}" style="padding:6px 14px; border-radius:8px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#94a3b8; font-size:12px; text-decoration:none; transition:all .15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">Siguiente →</a>
            @else
            <span style="padding:6px 14px; border-radius:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); color:#374151; font-size:12px; cursor:not-allowed;">Siguiente →</span>
            @endif
        </div>
        @endif

        @endif

    </div>
</x-app-layout>
