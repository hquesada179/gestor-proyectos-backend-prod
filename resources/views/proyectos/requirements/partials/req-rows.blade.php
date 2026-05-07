{{--
  Partial: req-rows.blade.php
  Variables recibidas:
    $items         — Collection de Requirement (con userStories.tasks cargados)
    $proyecto      — Proyecto model
    $currentSprint — Sprint model o null (backlog)
--}}

@foreach($items as $req)
@php
    $reqCode    = $req->codigo ?? 'REQ-' . str_pad($req->id, 3, '0', STR_PAD_LEFT);
    $searchText = strtolower($reqCode . ' ' . $req->titulo);
    $huCount    = $req->userStories->count();

    // Estado visual calculado (no requiere columna en BD)
    $statusLabel = $huCount > 0 ? 'Con HU' : 'Pendiente';
    $statusClass = $huCount > 0
        ? 'bg-indigo-500/15 text-indigo-300 border-indigo-500/20'
        : 'bg-gray-500/10 text-gray-500 border-gray-500/20';

    // Sprint asociado
    $sprintModel      = $currentSprint ?? null;
    $sprintLabel      = $sprintModel ? $sprintModel->nombre : 'Sin sprint';
    $sprintBadgeClass = $sprintModel
        ? ($sprintModel->estado === 'en_progreso'
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/15'
            : 'bg-blue-500/10 text-blue-400 border-blue-500/15')
        : 'bg-gray-500/10 text-gray-600 border-gray-500/15';
@endphp

<div class="req-row-wrapper"
     data-tipo="{{ $req->tipo }}"
     data-prioridad="{{ $req->prioridad }}"
     data-search="{{ $searchText }}">

    {{-- ── Fila principal ──────────────────────────────────────────── --}}
    <div class="req-row flex items-center gap-2 px-3 py-2 rounded-xl
                hover:bg-white/[0.04] cursor-pointer transition-colors duration-100 group
                border border-transparent hover:border-white/[0.06]">

        {{-- Expand toggle --}}
        <button class="req-expand-btn flex-shrink-0 w-5 h-5 flex items-center justify-center
                        text-gray-600 hover:text-gray-300 transition-colors"
                title="Ver detalle" type="button">
            <span class="material-symbols-outlined expand-icon leading-none"
                  style="font-size: 16px;">chevron_right</span>
        </button>

        {{-- Código (RF-001, RNF-002…) --}}
        <span class="flex-shrink-0 w-[88px] font-mono text-xs text-indigo-400/80 font-semibold truncate select-text"
              title="{{ $reqCode }}">
            {{ $reqCode }}
        </span>

        {{-- Título --}}
        <div class="flex-1 min-w-0">
            <a href="{{ route('proyectos.requirements.show', [$proyecto, $req]) }}"
               class="text-sm font-medium text-white hover:text-indigo-300 transition-colors truncate block"
               onclick="event.stopPropagation()">
                {{ $req->titulo }}
            </a>
        </div>

        {{-- Tipo badge --}}
        <span class="flex-shrink-0 hidden sm:inline-flex text-[10px] px-2 py-0.5 rounded-full font-medium border
            {{ $req->tipo === 'funcional'
                ? 'bg-blue-500/15 text-blue-300 border-blue-500/20'
                : 'bg-violet-500/15 text-violet-300 border-violet-500/20' }}">
            {{ $req->tipo === 'funcional' ? 'Funcional' : 'No funcional' }}
        </span>

        {{-- Prioridad badge --}}
        <span class="flex-shrink-0 text-[10px] px-2 py-0.5 rounded-full font-medium border capitalize
            @if($req->prioridad === 'alta')      bg-rose-500/15   text-rose-300   border-rose-500/20
            @elseif($req->prioridad === 'media')  bg-amber-500/15  text-amber-300  border-amber-500/20
            @else                                 bg-slate-500/10  text-slate-400  border-slate-500/20 @endif">
            {{ ucfirst($req->prioridad) }}
        </span>

        {{-- Estado visual --}}
        <span class="flex-shrink-0 hidden sm:inline-flex text-[10px] px-2 py-0.5 rounded-full font-medium border {{ $statusClass }}">
            {{ $statusLabel }}
        </span>

        {{-- HU count --}}
        <div class="flex-shrink-0 w-12 hidden sm:flex items-center justify-center gap-0.5">
            <span class="text-xs tabular-nums {{ $huCount > 0 ? 'text-gray-300 font-semibold' : 'text-gray-700' }}">
                {{ $huCount }}
            </span>
            <span class="text-[9px] text-gray-600">HU</span>
        </div>

        {{-- Sprint badge --}}
        <span class="flex-shrink-0 hidden md:inline-flex items-center gap-1 text-[10px] px-2 py-0.5
                     rounded-full font-medium border {{ $sprintBadgeClass }} max-w-[120px] truncate"
              title="{{ $sprintLabel }}">
            @if($sprintModel)
                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                    {{ $sprintModel->estado === 'en_progreso' ? 'bg-emerald-400' : 'bg-blue-400' }}"></span>
            @endif
            {{ $sprintLabel }}
        </span>

        {{-- Acciones hover --}}
        <div class="flex-shrink-0 flex items-center justify-end gap-0.5 ml-auto
                    opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="{{ route('proyectos.requirements.edit', [$proyecto, $req]) }}"
               onclick="event.stopPropagation()"
               class="p-1 rounded-lg hover:bg-white/5 text-gray-500 hover:text-gray-200 transition-colors"
               title="Editar">
                <span class="material-symbols-outlined" style="font-size: 15px;">edit</span>
            </a>
            <form method="POST" action="{{ route('proyectos.requirements.destroy', [$proyecto, $req]) }}"
                  class="inline" onsubmit="return confirm('¿Eliminar este requerimiento?\nEsta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="event.stopPropagation()"
                        class="p-1 rounded-lg hover:bg-rose-500/10 text-gray-500 hover:text-rose-400 transition-colors"
                        title="Eliminar">
                    <span class="material-symbols-outlined" style="font-size: 15px;">delete</span>
                </button>
            </form>
        </div>

    </div>

    {{-- ── Panel expandido ─────────────────────────────────────────── --}}
    <div class="req-detail hidden ml-7 mr-2 mb-2 rounded-xl overflow-hidden
                border border-white/[0.07] bg-white/[0.015]">

        {{-- Cabecera del panel --}}
        <div class="flex items-center gap-3 px-4 py-2 bg-white/[0.025] border-b border-white/[0.05]">
            <span class="font-mono text-xs text-indigo-400/80 font-semibold flex-shrink-0">{{ $reqCode }}</span>
            <span class="text-xs font-medium text-white truncate flex-1">{{ $req->titulo }}</span>
            <span class="text-[10px] text-gray-600 flex-shrink-0 hidden sm:block">
                Creado {{ $req->created_at->diffForHumans() }}
            </span>
        </div>

        <div class="px-4 py-3 space-y-4">

            {{-- Sección: Descripción ──────────────────────────────── --}}
            <div>
                <p class="req-section-label">
                    <span class="material-symbols-outlined" style="font-size: 12px;">description</span>
                    Descripción
                </p>
                <p class="text-xs text-gray-400 leading-relaxed bg-white/[0.02] rounded-lg
                           px-3 py-2.5 border border-white/[0.05]">
                    {{ $req->descripcion }}
                </p>
            </div>

            {{-- Sección: Historias de usuario ───────────────────── --}}
            <div class="border-t border-white/[0.05] pt-3">
                <div class="flex items-center justify-between mb-2.5">
                    <p class="req-section-label">
                        <span class="material-symbols-outlined" style="font-size: 12px;">bookmark</span>
                        Historias de usuario
                        @if($huCount > 0)
                            <span class="normal-case ml-1 bg-indigo-500/15 text-indigo-300 border border-indigo-500/20
                                         px-1.5 rounded-full font-medium text-[10px]">{{ $huCount }}</span>
                        @endif
                    </p>
                    <a href="{{ route('proyectos.requirements.user-stories.create', [$proyecto, $req]) }}"
                       onclick="event.stopPropagation()"
                       class="flex items-center gap-1 text-[10px] font-semibold text-indigo-400 hover:text-indigo-300
                              transition-colors px-2.5 py-1 rounded-lg hover:bg-indigo-500/10
                              border border-indigo-500/25 hover:border-indigo-400/40">
                        <span class="material-symbols-outlined" style="font-size: 12px;">add</span>
                        Nueva HU
                    </a>
                </div>

                @forelse($req->userStories as $us)
                <div class="flex items-center gap-2.5 py-1.5 border-b border-white/[0.04] last:border-0">
                    <span class="material-symbols-outlined text-indigo-500/50 flex-shrink-0"
                          style="font-size: 13px;">bookmark</span>
                    <a href="{{ route('proyectos.requirements.user-stories.show', [$proyecto, $req, $us]) }}"
                       onclick="event.stopPropagation()"
                       class="flex-1 text-xs text-gray-300 hover:text-white transition-colors truncate">
                        {{ $us->titulo }}
                    </a>
                    @if($us->como_usuario)
                    <span class="text-[10px] text-gray-600 hidden lg:block truncate max-w-[180px]"
                          title="Como {{ $us->como_usuario }}">
                        Como {{ $us->como_usuario }}
                    </span>
                    @endif
                    <span class="flex-shrink-0 text-[10px] px-1.5 py-0.5 rounded-full border capitalize
                        @if($us->prioridad === 'alta')      bg-rose-500/15  text-rose-300  border-rose-500/15
                        @elseif($us->prioridad === 'media')  bg-amber-500/15 text-amber-300 border-amber-500/15
                        @else                                bg-slate-500/10 text-slate-400 border-slate-500/10 @endif">
                        {{ ucfirst($us->prioridad) }}
                    </span>
                </div>
                @empty
                {{-- Empty state metodológico --}}
                <div class="flex flex-col items-center py-5 text-center bg-white/[0.01] rounded-lg
                            border border-dashed border-white/[0.07] mt-1">
                    <span class="material-symbols-outlined text-gray-700 mb-2"
                          style="font-size: 26px;">bookmark_add</span>
                    <p class="text-xs text-gray-500 font-medium mb-0.5">
                        Aún no hay historias de usuario registradas.
                    </p>
                    <p class="text-[11px] text-gray-600 mb-3 max-w-xs">
                        Crea historias para convertir este requerimiento en trabajo planificable.
                    </p>
                    <a href="{{ route('proyectos.requirements.user-stories.create', [$proyecto, $req]) }}"
                       onclick="event.stopPropagation()"
                       class="flex items-center gap-1.5 text-[10px] font-semibold text-indigo-400
                              hover:text-indigo-300 transition-colors px-3 py-1.5 rounded-lg
                              bg-indigo-500/10 hover:bg-indigo-500/15 border border-indigo-500/20">
                        <span class="material-symbols-outlined" style="font-size: 12px;">add</span>
                        Crear primera historia de usuario
                    </a>
                </div>
                @endforelse
            </div>

            {{-- Sección: Acciones ────────────────────────────────── --}}
            <div class="border-t border-white/[0.05] pt-3">
                <p class="req-section-label mb-2">
                    <span class="material-symbols-outlined" style="font-size: 12px;">more_horiz</span>
                    Acciones
                </p>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('proyectos.requirements.show', [$proyecto, $req]) }}"
                       onclick="event.stopPropagation()"
                       class="req-action-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px;">open_in_new</span>
                        Ver detalle completo
                    </a>
                    <a href="{{ route('proyectos.requirements.edit', [$proyecto, $req]) }}"
                       onclick="event.stopPropagation()"
                       class="req-action-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit</span>
                        Editar
                    </a>
                    <form method="POST" action="{{ route('proyectos.requirements.destroy', [$proyecto, $req]) }}"
                          class="inline"
                          onsubmit="return confirm('¿Eliminar este requerimiento?\nEsta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="event.stopPropagation()"
                                class="req-action-btn req-action-danger">
                            <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>
@endforeach
