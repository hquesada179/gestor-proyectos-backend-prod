{{--
  Partial: req-rows.blade.php
  Variables recibidas:
    $items    — Collection de Requirement (con userStories.tasks cargados)
    $proyecto — Proyecto model
--}}

@foreach($items as $req)
@php
    $reqCode = $req->codigo ?? 'REQ-' . str_pad($req->id, 3, '0', STR_PAD_LEFT);
    $searchText = strtolower($reqCode . ' ' . $req->titulo);
@endphp

<div class="req-row-wrapper"
     data-tipo="{{ $req->tipo }}"
     data-prioridad="{{ $req->prioridad }}"
     data-search="{{ $searchText }}">

    {{-- Main row --}}
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

        {{-- Código --}}
        <span class="flex-shrink-0 w-[90px] font-mono text-xs text-gray-500 truncate select-text"
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
            @if($req->prioridad === 'alta')  bg-rose-500/15   text-rose-300   border-rose-500/20
            @elseif($req->prioridad === 'media') bg-amber-500/15  text-amber-300  border-amber-500/20
            @else                            bg-slate-500/10  text-slate-400  border-slate-500/20 @endif">
            {{ ucfirst($req->prioridad) }}
        </span>

        {{-- Historias de usuario count --}}
        <div class="flex-shrink-0 w-12 text-center hidden sm:block">
            @if($req->userStories->count() > 0)
                <span class="text-xs text-gray-400 tabular-nums">{{ $req->userStories->count() }}</span>
                <span class="text-[9px] text-gray-600"> HU</span>
            @else
                <span class="text-[10px] text-gray-700">—</span>
            @endif
        </div>

        {{-- Actions (visible on hover) --}}
        <div class="flex-shrink-0 w-16 flex items-center justify-end gap-0.5
                    opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="{{ route('proyectos.requirements.edit', [$proyecto, $req]) }}"
               onclick="event.stopPropagation()"
               class="p-1 rounded-lg hover:bg-white/5 text-gray-500 hover:text-gray-200 transition-colors"
               title="Editar">
                <span class="material-symbols-outlined" style="font-size: 15px;">edit</span>
            </a>
            <form method="POST" action="{{ route('proyectos.requirements.destroy', [$proyecto, $req]) }}"
                  class="inline" onsubmit="return confirm('¿Eliminar este requerimiento?')">
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

    {{-- Detail panel (hidden by default) --}}
    <div class="req-detail hidden ml-7 mr-2 mb-1.5 rounded-xl bg-white/[0.025] border border-white/[0.06]
                px-4 py-3 space-y-3">

        {{-- Descripción --}}
        <div>
            <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold mb-1.5">
                Descripción
            </p>
            <p class="text-xs text-gray-400 leading-relaxed">{{ $req->descripcion }}</p>
        </div>

        {{-- Historias de usuario --}}
        <div class="pt-2 border-t border-white/5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold">
                    Historias de usuario
                    @if($req->userStories->count() > 0)
                        <span class="text-gray-700 normal-case ml-1">({{ $req->userStories->count() }})</span>
                    @endif
                </p>
                <a href="{{ route('proyectos.requirements.user-stories.create', [$proyecto, $req]) }}"
                   onclick="event.stopPropagation()"
                   class="flex items-center gap-1 text-[10px] text-indigo-400 hover:text-indigo-300 transition-colors">
                    <span class="material-symbols-outlined" style="font-size: 12px;">add</span>
                    Nueva HU
                </a>
            </div>

            @forelse($req->userStories as $us)
            <div class="flex items-center gap-2 py-1.5 border-b border-white/[0.04] last:border-0">
                <span class="material-symbols-outlined text-indigo-500/40 flex-shrink-0"
                      style="font-size: 13px;">bookmark</span>
                <a href="{{ route('proyectos.requirements.user-stories.show', [$proyecto, $req, $us]) }}"
                   onclick="event.stopPropagation()"
                   class="flex-1 text-xs text-gray-300 hover:text-white transition-colors truncate">
                    {{ $us->titulo }}
                </a>
                @if($us->como_usuario)
                <span class="text-[10px] text-gray-600 hidden lg:block truncate max-w-[200px]"
                      title="{{ $us->como_usuario }}">
                    Como {{ $us->como_usuario }}
                </span>
                @endif
                <span class="flex-shrink-0 text-[10px] px-1.5 py-0.5 rounded-full capitalize
                    @if($us->prioridad === 'alta')  bg-rose-500/15  text-rose-300
                    @elseif($us->prioridad === 'media') bg-amber-500/15 text-amber-300
                    @else                           bg-slate-500/10 text-slate-400 @endif">
                    {{ ucfirst($us->prioridad) }}
                </span>
            </div>
            @empty
            <p class="text-xs text-gray-600 italic py-1.5">Sin historias de usuario registradas aún.</p>
            @endforelse
        </div>

        {{-- Meta footer --}}
        <div class="pt-2 border-t border-white/5 flex items-center gap-4 flex-wrap">
            <span class="text-[10px] text-gray-600">
                Creado {{ $req->created_at->diffForHumans() }}
            </span>
            <a href="{{ route('proyectos.requirements.show', [$proyecto, $req]) }}"
               onclick="event.stopPropagation()"
               class="text-[10px] text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-0.5">
                Ver detalle completo
                <span class="material-symbols-outlined" style="font-size: 11px;">open_in_new</span>
            </a>
        </div>

    </div>

</div>
@endforeach
