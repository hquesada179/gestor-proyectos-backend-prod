<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-400" style="font-size:20px;font-variation-settings:'FILL' 1">auto_awesome</span>
                <span class="text-sm font-bold text-white">Asistente IA</span>
                <span class="text-gray-600 text-sm mx-1">·</span>
                <span class="text-xs text-gray-500">Crea o mejora proyectos con inteligencia artificial.</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full animate-pulse {{ $ollamaOnline ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                <span class="text-xs {{ $ollamaOnline ? 'text-emerald-400' : 'text-red-400' }}">
                    Ollama {{ $ollamaOnline ? 'en línea' : 'desconectado' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="h-[calc(100vh-64px)] flex flex-col overflow-hidden">

        {{-- ══ MAIN AREA ═══════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex min-h-0 overflow-hidden">

            {{-- ── LEFT PANEL ──────────────────────────────────────────────────── --}}
            <div class="w-[380px] flex-shrink-0 border-r border-white/6 overflow-y-auto"
                 style="background:rgba(11,13,20,0.98);scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.06) transparent">
                <div class="p-5 space-y-4">

                    @unless($ollamaOnline)
                    <div class="rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined" style="font-size:15px">warning</span>Ollama no está corriendo
                        </p>
                        <p class="text-[11px] text-amber-200/70 mt-1">
                            Ejecuta <code class="bg-black/30 px-1 rounded font-mono">ollama serve</code>
                            y luego <code class="bg-black/30 px-1 rounded font-mono">ollama pull gemma3</code>
                        </p>
                    </div>
                    @endunless

                    {{-- ── CONTEXT BADGE (shown when reutilizing a history record) ── --}}
                    <div id="context-badge" class="hidden rounded-xl border border-violet-500/30 bg-violet-500/10 px-3 py-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold text-violet-300 uppercase tracking-widest mb-0.5">
                                    Continuando historial
                                    <span class="text-violet-400" id="ctx-badge-id"></span>
                                </p>
                                <p class="text-xs text-gray-300 truncate" id="ctx-badge-proyecto"></p>
                                <p class="text-[10px] text-gray-600 truncate mt-0.5 italic" id="ctx-badge-prompt"></p>
                            </div>
                            <button onclick="clearContext()"
                                    class="flex-shrink-0 text-gray-600 hover:text-red-400 transition-colors mt-0.5"
                                    title="Cancelar continuación">
                                <span class="material-symbols-outlined" style="font-size:16px">close</span>
                            </button>
                        </div>
                    </div>

                    {{-- ── MODE ────────────────────────────────────────────────────── --}}
                    <div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Modo</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button id="mode-crear" onclick="setMode('crear')"
                                    class="mode-btn active text-left rounded-xl border px-3 py-2.5 transition-all">
                                <span class="material-symbols-outlined block mb-1" style="font-size:17px">add_circle</span>
                                <span class="text-xs font-semibold block">Crear proyecto</span>
                                <span class="text-[10px] block" style="color:#64748b">nuevo</span>
                            </button>
                            <button id="mode-mejorar" onclick="setMode('mejorar')"
                                    class="mode-btn text-left rounded-xl border px-3 py-2.5 transition-all">
                                <span class="material-symbols-outlined block mb-1" style="font-size:17px">edit_note</span>
                                <span class="text-xs font-semibold block">Mejorar proyecto</span>
                                <span class="text-[10px] block" style="color:#64748b">existente</span>
                            </button>
                        </div>
                    </div>

                    {{-- ── PROJECT SELECTOR ─────────────────────────────────────────── --}}
                    <div id="project-selector-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">
                            Proyecto a mejorar
                        </label>
                        @if($proyectos->isEmpty())
                            <p class="text-xs text-gray-600 italic">Sin proyectos creados.</p>
                        @else
                            <select id="proyecto-select"
                                    class="w-full rounded-xl border border-white/10 px-3 py-2 text-sm text-white
                                           focus:outline-none focus:border-indigo-500/50 transition-colors"
                                    style="background:#111827">
                                <option value="">— Selecciona un proyecto —</option>
                                @foreach($proyectos as $p)
                                <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">
                                    {{ $p->nombre }}
                                </option>
                                @endforeach
                            </select>
                            <p id="project-hint" class="text-[10px] text-amber-400/80 mt-1 hidden">
                                Selecciona un proyecto para continuar.
                            </p>
                        @endif
                    </div>

                    {{-- ── MODEL ────────────────────────────────────────────────────── --}}
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">
                            Modelo de IA
                        </label>
                        @if(count($models) > 0)
                        <select id="model-select"
                                class="w-full rounded-xl border border-white/10 px-3 py-2 text-sm text-white
                                       focus:outline-none focus:border-indigo-500/50 transition-colors"
                                style="background:#111827">
                            @foreach($models as $m)
                            <option value="{{ $m }}" {{ $m === $defaultModel || str_starts_with($m, $defaultModel) ? 'selected' : '' }}>
                                {{ $m }}
                            </option>
                            @endforeach
                        </select>
                        @else
                        <input type="text" id="model-select" value="{{ $defaultModel }}"
                               placeholder="ej: gemma3, phi3, llama3.2"
                               class="w-full rounded-xl border border-white/10 px-3 py-2 text-sm text-white
                                      placeholder-gray-600 focus:outline-none focus:border-indigo-500/50 transition-colors"
                               style="background:#111827" />
                        @endif
                    </div>

                    {{-- ── PROMPT TEXTAREA ──────────────────────────────────────────── --}}
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">
                            <span id="textarea-label">Descripción del proyecto</span>
                        </label>
                        <textarea id="prompt-input" rows="8"
                                  class="w-full rounded-xl border border-white/10 px-4 py-3 text-sm resize-none
                                         leading-relaxed focus:outline-none focus:border-indigo-500/50 transition-colors"
                                  style="background:#111827;color:#f1f5f9;caret-color:#818cf8;"
                                  placeholder="Ej: Quiero crear una aplicación web para gestionar proyectos académicos…"
                        ></textarea>
                        <div class="flex justify-between mt-1">
                            <span id="prompt-hint" class="text-[10px] text-red-400/80"></span>
                            <span id="prompt-count" class="text-[10px]" style="color:#475569">0 / 3000</span>
                        </div>
                    </div>

                    {{-- ── ERROR BOX ────────────────────────────────────────────────── --}}
                    <div id="error-box" class="hidden rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 space-y-1">
                        <p class="text-xs font-semibold text-red-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined" style="font-size:15px">error</span>Error al generar
                        </p>
                        <p id="error-msg" class="text-xs text-red-200/80 leading-relaxed"></p>
                        <details id="raw-details" class="hidden">
                            <summary class="text-[10px] text-red-300/50 cursor-pointer hover:text-red-300 select-none">
                                Ver respuesta cruda
                            </summary>
                            <pre id="raw-content" class="mt-1.5 text-[10px] text-gray-400 bg-black/30 rounded-lg p-2
                                                          overflow-x-auto whitespace-pre-wrap max-h-28 font-mono"></pre>
                            <button onclick="copyRaw()" class="text-[10px] text-indigo-400 hover:text-indigo-300">Copiar</button>
                        </details>
                    </div>

                    {{-- ── SUBMIT ───────────────────────────────────────────────────── --}}
                    <button id="submit-btn"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4
                                   bg-gradient-to-r from-indigo-600 to-violet-600
                                   hover:from-indigo-500 hover:to-violet-500
                                   active:scale-95 transition-all text-white text-sm font-bold rounded-xl
                                   disabled:opacity-40 disabled:cursor-not-allowed disabled:scale-100">
                        <span class="material-symbols-outlined" id="btn-icon" style="font-size:18px">auto_awesome</span>
                        <span id="btn-label">Generar borrador de proyecto</span>
                    </button>

                    <p class="text-[10px] leading-relaxed" style="color:#374151">
                        Los resultados son una propuesta generada por IA. No se guardan hasta que confirmes.
                    </p>
                </div>
            </div>

            {{-- ── RIGHT PANEL ─────────────────────────────────────────────────── --}}
            <div class="flex-1 overflow-y-auto"
                 style="background:rgba(8,10,17,0.99);scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.05) transparent">

                {{-- Empty --}}
                <div id="panel-empty" class="flex flex-col items-center justify-center h-full p-8 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20
                                flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-indigo-400" style="font-size:28px;font-variation-settings:'FILL' 1">auto_awesome</span>
                    </div>
                    <p class="text-sm font-semibold text-white mb-1">Listo para generar</p>
                    <p class="text-xs max-w-xs leading-relaxed" style="color:#475569">
                        Selecciona un modo, escribe tu instrucción y pulsa <strong class="text-gray-400">Generar</strong>.
                        La propuesta aparecerá aquí antes de guardarse.
                    </p>
                </div>

                {{-- Loading --}}
                <div id="panel-loading" class="hidden flex-col items-center justify-center h-full p-8 text-center">
                    <div class="flex gap-1.5 mb-4">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce" style="animation-delay:130ms"></span>
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay:260ms"></span>
                    </div>
                    <p class="text-sm font-semibold text-white mb-1">Analizando tu solicitud…</p>
                    <p class="text-xs" style="color:#475569">Esto puede tardar hasta 2 minutos según el modelo.</p>
                </div>

                {{-- Context panel (shown after "Reutilizar") --}}
                <div id="panel-context" class="hidden p-6 max-w-2xl mx-auto space-y-4">
                    <div class="rounded-2xl border border-violet-500/20 px-5 py-4" style="background:rgba(139,92,246,0.06)">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-violet-400" style="font-size:15px;font-variation-settings:'FILL' 1">history</span>
                            <span class="text-[10px] font-bold text-violet-300 uppercase tracking-widest">
                                Continuando historial <span id="ctx-id" class="text-violet-400"></span>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs mb-3">
                            <div>
                                <p style="color:#64748b" class="mb-0.5">Modo anterior</p>
                                <p class="font-semibold text-white" id="ctx-modo-label"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">Estado</p>
                                <p class="font-semibold" id="ctx-estado-label"></p>
                            </div>
                            <div class="col-span-2" id="ctx-proyecto-wrap">
                                <p style="color:#64748b" class="mb-0.5">Proyecto asociado</p>
                                <a id="ctx-proyecto-link" href="#" class="text-indigo-400 hover:text-indigo-300 font-semibold" id="ctx-proyecto-label"></a>
                            </div>
                        </div>
                        <div>
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">Prompt anterior</p>
                            <p class="text-sm text-gray-300 leading-relaxed bg-black/20 rounded-lg px-3 py-2" id="ctx-prompt-text"></p>
                        </div>
                        <div id="ctx-response-wrap" class="hidden mt-3">
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">Resumen de la respuesta anterior</p>
                            <p class="text-sm text-gray-400 leading-relaxed bg-black/20 rounded-lg px-3 py-2 italic" id="ctx-response-text"></p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-white/6 px-4 py-3" style="background:rgba(255,255,255,0.02)">
                        <p class="text-xs text-gray-400 leading-relaxed">
                            <span class="text-indigo-300 font-semibold">↓ Escribe una nueva instrucción</span> en el panel izquierdo para continuar desde este punto.
                            La IA recibirá el contexto del proyecto y el historial anterior.
                        </p>
                    </div>
                </div>

                {{-- Detail panel (shown after "Ver") --}}
                <div id="panel-detail" class="hidden p-6 max-w-2xl mx-auto space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <button onclick="closeDetail()"
                                class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-white transition-colors">
                            <span class="material-symbols-outlined" style="font-size:15px">arrow_back</span>
                            Volver
                        </button>
                        <span style="color:#334155">|</span>
                        <span class="text-xs font-semibold text-gray-400">Detalle del registro IA</span>
                    </div>

                    {{-- Detail header --}}
                    <div class="rounded-2xl border border-white/8 px-5 py-4" style="background:rgba(255,255,255,0.03)">
                        <div class="grid grid-cols-2 gap-3 text-xs mb-4">
                            <div>
                                <p style="color:#64748b" class="mb-0.5">Modo</p>
                                <p class="font-semibold text-white" id="det-modo"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">Estado</p>
                                <p class="font-semibold" id="det-estado"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">Fecha</p>
                                <p style="color:#94a3b8" id="det-fecha"></p>
                            </div>
                            <div id="det-proyecto-wrap">
                                <p style="color:#64748b" class="mb-0.5">Proyecto</p>
                                <a id="det-proyecto-link" href="#" class="text-indigo-400 hover:text-indigo-300 font-semibold truncate block"></a>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">Prompt del usuario</p>
                                <p class="text-sm text-gray-200 leading-relaxed bg-black/20 rounded-lg px-3 py-2" id="det-prompt"></p>
                            </div>
                            <div id="det-response-wrap" class="hidden">
                                <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">Resumen de la propuesta IA</p>
                                <p class="text-sm text-gray-400 leading-relaxed bg-black/20 rounded-lg px-3 py-2 italic" id="det-response"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Detail actions --}}
                    <div class="flex flex-wrap gap-3">
                        <button id="det-reutilizar-btn"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold
                                       bg-violet-600 hover:bg-violet-500 text-white active:scale-95 transition-all">
                            <span class="material-symbols-outlined" style="font-size:15px">replay</span>
                            Reutilizar este historial
                        </button>
                        <button id="det-aplicar-btn"
                                class="hidden flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold
                                       bg-emerald-600 hover:bg-emerald-500 text-white active:scale-95 transition-all">
                            <span class="material-symbols-outlined" style="font-size:15px">check</span>
                            Aplicar esta propuesta
                        </button>
                    </div>
                </div>

                {{-- Proposal panel --}}
                <div id="panel-proposal" class="hidden p-6 space-y-4 max-w-2xl mx-auto">
                    {{-- Proposal header --}}
                    <div id="prop-header" class="rounded-2xl border border-emerald-500/20 px-5 py-4"
                         style="background:rgba(16,185,129,0.06)">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-emerald-400" style="font-size:15px;font-variation-settings:'FILL' 1">check_circle</span>
                            <span class="text-[10px] uppercase tracking-widest font-bold text-emerald-400">Propuesta generada</span>
                            <span id="prop-type-badge" class="text-[10px]" style="color:#64748b"></span>
                        </div>
                        <p class="text-xl font-black text-white leading-tight" id="prop-nombre"></p>
                        <div id="prop-nombre-sep" class="hidden"></div>
                        <p id="prop-descripcion" class="text-sm leading-relaxed mt-1" style="color:#94a3b8"></p>
                        <div id="prop-resumen-wrap" class="hidden mt-3 pt-3 border-t border-white/6">
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">Resumen de cambios</p>
                            <p id="prop-resumen" class="text-sm text-gray-300 leading-relaxed"></p>
                        </div>
                    </div>

                    <div id="prop-sections" class="space-y-3"></div>

                    <div id="prop-warnings" class="hidden rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-300 mb-1">Advertencias</p>
                        <div id="prop-warnings-list" class="space-y-0.5 text-xs text-amber-200/80"></div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button id="btn-confirm"
                                class="flex items-center gap-1.5 px-5 py-2.5 rounded-xl
                                       bg-emerald-600 hover:bg-emerald-500 active:scale-95
                                       text-white text-sm font-bold transition-all">
                            <span class="material-symbols-outlined" style="font-size:17px">check</span>
                            Confirmar y guardar
                        </button>
                        <button id="btn-cancel"
                                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-white/12
                                       hover:border-white/25 text-gray-400 hover:text-white text-sm font-medium transition-all">
                            <span class="material-symbols-outlined" style="font-size:17px">close</span>
                            Cancelar
                        </button>
                        <button id="btn-edit"
                                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-white/8
                                       hover:border-indigo-500/40 text-gray-500 hover:text-indigo-300 text-sm font-medium transition-all ml-auto">
                            <span class="material-symbols-outlined" style="font-size:16px">edit</span>
                            Editar instrucción
                        </button>
                    </div>

                    <div id="success-box" class="hidden rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-5 py-4">
                        <p class="text-sm font-bold text-emerald-300" id="success-msg"></p>
                        <div id="success-counts" class="text-xs mt-2 space-y-0.5" style="color:#94a3b8"></div>
                        <a id="success-link" href="#" class="hidden mt-3 inline-block text-xs font-bold text-indigo-400 hover:text-indigo-300">
                            Ver proyecto →
                        </a>
                    </div>
                </div>

            </div>{{-- end right panel --}}
        </div>{{-- end main area --}}

        {{-- ══ HISTORY SECTION ═══════════════════════════════════════════════════ --}}
        <div class="flex-shrink-0 border-t border-white/6" style="background:rgba(11,13,20,0.98)">

            {{-- Toggle bar --}}
            <div class="flex items-center justify-between px-5 py-2 cursor-pointer select-none"
                 onclick="toggleHistory()">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined" id="history-chevron"
                          style="font-size:16px;color:#4b5563;transition:transform 0.2s">expand_less</span>
                    <span class="text-xs font-semibold" style="color:#6b7280">Historial IA</span>
                    <span id="history-badge"
                          class="hidden text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-1.5 py-0.5 rounded-full"></span>
                </div>
                <div class="flex items-center gap-3" onclick="event.stopPropagation()">
                    <button id="btn-clear-project"
                            class="text-[10px] hidden hover:text-amber-400 transition-colors"
                            style="color:#4b5563" onclick="clearHistory('project')">
                        Limpiar proyecto
                    </button>
                    <button class="text-[10px] hover:text-red-400 transition-colors"
                            style="color:#4b5563" onclick="clearHistory('all')">
                        Limpiar todo
                    </button>
                </div>
            </div>

            {{-- History content (collapsible) --}}
            <div id="history-content" style="display:none">

                {{-- Filter + search row --}}
                <div class="flex items-center gap-2 px-4 py-2 border-t border-white/5">
                    <button class="hist-filter-btn active" data-f="all" onclick="setHistoryFilter('all')">Todos</button>
                    <button class="hist-filter-btn" data-f="project" onclick="setHistoryFilter('project')" id="filter-project-btn">
                        Este proyecto
                    </button>
                    <div class="flex-1 relative">
                        <input type="text" id="history-search"
                               placeholder="Buscar en historial…"
                               class="w-full rounded-lg px-3 py-1 text-[11px] transition-colors focus:outline-none"
                               style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);color:#f1f5f9;"
                               oninput="filterHistoryItems()" />
                    </div>
                </div>

                {{-- List --}}
                <div id="history-list"
                     style="max-height:200px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.06) transparent">
                    <div class="text-center py-4">
                        <span class="text-[11px]" style="color:#374151">Haz clic en "Historial IA" para cargar registros.</span>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- end outer --}}

    @push('scripts')
    <style>
        /* ── Mode buttons ─────────────────────────────────────────────────── */
        .mode-btn { background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08); color:rgba(156,163,175,1); }
        .mode-btn:hover { background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.15); color:#fff; }
        .mode-btn.active { background:rgba(99,102,241,0.12); border-color:rgba(99,102,241,0.4); color:rgb(165,180,252); }

        /* ── Textarea ─────────────────────────────────────────────────────── */
        #prompt-input::placeholder { color:#475569; }
        #prompt-input:focus { outline:none; border-color:rgba(99,102,241,0.5); }

        /* ── Proposal sections ────────────────────────────────────────────── */
        .prop-section { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; overflow:hidden; }
        .prop-section-hdr { padding:7px 13px; display:flex; align-items:center; gap:8px; border-bottom:1px solid rgba(255,255,255,0.05); background:rgba(255,255,255,0.02); }
        .prop-section-body { padding:8px 13px; }
        .prop-item { padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.04); }
        .prop-item:last-child { border-bottom:none; }
        .bx { display:inline-flex; align-items:center; padding:1px 6px; border-radius:99px; font-size:10px; font-weight:600; border:1px solid; }
        .b-alta  { background:rgba(239,68,68,.12); color:rgb(252,165,165); border-color:rgba(239,68,68,.2); }
        .b-media { background:rgba(245,158,11,.12); color:rgb(253,230,138); border-color:rgba(245,158,11,.2); }
        .b-baja  { background:rgba(100,116,139,.1); color:rgb(148,163,184); border-color:rgba(100,116,139,.2); }
        .b-func  { background:rgba(59,130,246,.12); color:rgb(147,197,253); border-color:rgba(59,130,246,.2); }
        .b-nofunc{ background:rgba(139,92,246,.12); color:rgb(196,181,253); border-color:rgba(139,92,246,.2); }
        .b-new   { background:rgba(16,185,129,.12); color:rgb(110,231,183); border-color:rgba(16,185,129,.2); }
        .b-upd   { background:rgba(245,158,11,.12); color:rgb(253,230,138); border-color:rgba(245,158,11,.2); }

        /* ── History ────────────────────────────────────────────────────────── */
        .hist-filter-btn { padding:2px 10px; border-radius:6px; font-size:10px; font-weight:500; color:rgba(107,114,128,1); border:1px solid transparent; transition:all 0.15s; }
        .hist-filter-btn:hover { color:#fff; background:rgba(255,255,255,0.05); }
        .hist-filter-btn.active { color:rgb(167,139,250); background:rgba(139,92,246,0.12); border-color:rgba(139,92,246,0.3); }

        .hist-item { display:flex; align-items:flex-start; gap:10px; padding:8px 20px; border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.12s; }
        .hist-item:hover { background:rgba(255,255,255,0.02); }
        .he { display:inline-flex; align-items:center; padding:1px 6px; border-radius:99px; font-size:9px; font-weight:700; border:1px solid; flex-shrink:0; margin-top:2px; }
        .h-borrador   { background:rgba(245,158,11,.12); color:rgb(253,230,138); border-color:rgba(245,158,11,.25); }
        .h-aplicado   { background:rgba(16,185,129,.12); color:rgb(110,231,183); border-color:rgba(16,185,129,.25); }
        .h-descartado { background:rgba(100,116,139,.1); color:rgb(148,163,184); border-color:rgba(100,116,139,.2); }
        .h-error      { background:rgba(239,68,68,.12);  color:rgb(252,165,165); border-color:rgba(239,68,68,.2);  }
    </style>

    <script>
    (function () {
        'use strict';

        // ── State ──────────────────────────────────────────────────────────────
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const S = {
            mode:          'crear',
            proyectoId:    null,
            pendingId:     null,      // chat_id awaiting confirm/discard
            parentChatId:  null,      // chat_id being "reutilized"
            detailChatId:  null,      // chat_id currently viewed in detail
            historyOpen:   false,
            historyFilter: 'all',
            allItems:      [],        // full history list (for client-side filter)
        };

        // ── DOM ─────────────────────────────────────────────────────────────────
        const submitBtn   = document.getElementById('submit-btn');
        const promptInput = document.getElementById('prompt-input');
        const promptCount = document.getElementById('prompt-count');
        const promptHint  = document.getElementById('prompt-hint');
        const errorBox    = document.getElementById('error-box');
        const panels      = {
            empty:    document.getElementById('panel-empty'),
            loading:  document.getElementById('panel-loading'),
            context:  document.getElementById('panel-context'),
            detail:   document.getElementById('panel-detail'),
            proposal: document.getElementById('panel-proposal'),
        };

        // ── Char counter ───────────────────────────────────────────────────────
        promptInput.addEventListener('input', function () {
            const len = this.value.length;
            promptCount.textContent = len + ' / 3000';
            promptHint.textContent  = len > 0 && len < 10 ? 'Mínimo 10 caracteres' : '';
        });

        // ── Mode selector ──────────────────────────────────────────────────────
        window.setMode = function (mode, keepContext) {
            S.mode = mode;
            document.getElementById('mode-crear').classList.toggle('active', mode === 'crear');
            document.getElementById('mode-mejorar').classList.toggle('active', mode === 'mejorar');

            const pw = document.getElementById('project-selector-wrap');
            if (pw) pw.classList.toggle('hidden', mode === 'crear');

            document.getElementById('btn-label').textContent = mode === 'crear'
                ? 'Generar borrador de proyecto'
                : 'Generar propuesta de mejora';

            document.getElementById('textarea-label').textContent = mode === 'crear'
                ? 'Descripción del proyecto'
                : 'Instrucción para mejorar';

            promptInput.placeholder = mode === 'crear'
                ? 'Ej: Quiero crear una aplicación web para gestionar proyectos académicos, requerimientos, tareas, sprints e insumos…'
                : 'Ej: Agrega autenticación con Google, mejora los nombres de las tareas y crea un sprint inicial…';

            const cpBtn = document.getElementById('btn-clear-project');
            if (cpBtn) cpBtn.classList.toggle('hidden', mode === 'crear' || !S.proyectoId);

            if (!keepContext) hideError();
        };

        // Project selector
        const proyectoSelect = document.getElementById('proyecto-select');
        if (proyectoSelect) {
            proyectoSelect.addEventListener('change', function () {
                S.proyectoId = this.value ? parseInt(this.value) : null;
                document.getElementById('project-hint')?.classList.add('hidden');
                const cpBtn = document.getElementById('btn-clear-project');
                if (cpBtn) cpBtn.classList.toggle('hidden', !S.proyectoId);
            });
        }

        // ── Submit ────────────────────────────────────────────────────────────
        submitBtn.addEventListener('click', function () {
            const prompt  = promptInput.value.trim();
            const modelo  = document.getElementById('model-select').value.trim();

            if (prompt.length < 10) { showError('El prompt debe tener al menos 10 caracteres.'); return; }
            if (!modelo)             { showError('Selecciona o escribe un modelo.'); return; }
            if (S.mode === 'mejorar' && !S.proyectoId) {
                document.getElementById('project-hint')?.classList.remove('hidden');
                showError('Selecciona un proyecto para mejorar.');
                return;
            }

            setLoading(true);
            hideError();

            fetch('{{ route("asistente-ia.submit") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    modo:           S.mode,
                    prompt:         prompt,
                    modelo:         modelo,
                    proyecto_id:    S.proyectoId   || null,
                    parent_chat_id: S.parentChatId || null,  // link to parent
                }),
            })
            .then(r => r.json())
            .then(data => {
                setLoading(false);
                if (!data.ok) {
                    showError(data.error || 'Error al procesar.', data.raw || null);
                    return;
                }
                // Clear parent context after successful submission (context was used)
                S.parentChatId = null;
                updateContextBadge();

                S.pendingId = data.chat_id;
                if (data.type === 'create_proposal')  renderCreateProposal(data);
                else                                   renderImproveProposal(data);

                if (S.historyOpen) loadHistory();
            })
            .catch(() => { setLoading(false); showError('Error de conexión.'); });
        });

        // ── Confirm ───────────────────────────────────────────────────────────
        document.getElementById('btn-confirm').addEventListener('click', function () {
            if (!S.pendingId) return;
            this.disabled = true;
            this.textContent = 'Guardando…';

            fetch('{{ route("asistente-ia.apply") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ chat_id: S.pendingId, action: 'confirm' }),
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('btn-confirm').disabled = false;
                document.getElementById('btn-confirm').innerHTML = '<span class="material-symbols-outlined" style="font-size:17px">check</span> Confirmar y guardar';
                if (!data.ok) { showError(data.error || 'Error al aplicar.'); return; }
                showSuccess(data);
                S.pendingId = null;
                if (S.historyOpen) loadHistory();
            })
            .catch(() => { showError('Error de conexión.'); });
        });

        // ── Cancel ────────────────────────────────────────────────────────────
        document.getElementById('btn-cancel').addEventListener('click', function () {
            if (S.pendingId) {
                fetch('{{ route("asistente-ia.apply") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ chat_id: S.pendingId, action: 'discard' }),
                });
                S.pendingId = null;
            }
            showPanel('context'); // go back to context if active, else empty
            if (S.parentChatId) showPanel('context');
            else showPanel('empty');
            if (S.historyOpen) loadHistory();
        });

        // ── Edit instruction ──────────────────────────────────────────────────
        document.getElementById('btn-edit').addEventListener('click', function () {
            if (S.pendingId) {
                fetch('{{ route("asistente-ia.apply") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ chat_id: S.pendingId, action: 'discard' }),
                });
                S.pendingId = null;
            }
            if (S.parentChatId) showPanel('context');
            else showPanel('empty');
            promptInput.focus();
        });

        // ── Context badge ─────────────────────────────────────────────────────
        function updateContextBadge() {
            document.getElementById('context-badge').classList.toggle('hidden', !S.parentChatId);
        }

        window.clearContext = function () {
            S.parentChatId = null;
            updateContextBadge();
            if (!S.pendingId) showPanel('empty');
        };

        // ── View states ───────────────────────────────────────────────────────
        function showPanel(name) {
            Object.keys(panels).forEach(k => {
                const p = panels[k];
                if (k === 'loading') p.style.display = 'none';
                else p.classList.add('hidden');
            });
            if (name === 'loading') { panels.loading.style.display = 'flex'; }
            else { panels[name]?.classList.remove('hidden'); }
        }

        function setLoading(on) {
            submitBtn.disabled = on;
            document.getElementById('btn-icon').textContent = on ? 'hourglass_top' : 'auto_awesome';
            document.getElementById('btn-label').textContent = on ? 'Generando…'
                : (S.mode === 'crear' ? 'Generar borrador de proyecto' : 'Generar propuesta de mejora');
            if (on) showPanel('loading');
        }

        // ── Error / helpers ───────────────────────────────────────────────────
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function showError(msg, raw) {
            errorBox.classList.remove('hidden');
            document.getElementById('error-msg').textContent = msg;
            const rd = document.getElementById('raw-details');
            const rc = document.getElementById('raw-content');
            if (raw) { rd.classList.remove('hidden'); rc.textContent = typeof raw === 'string' ? raw : JSON.stringify(raw, null, 2); }
            else      { rd.classList.add('hidden'); }
        }
        function hideError() { errorBox.classList.add('hidden'); }
        window.copyRaw = function () {
            navigator.clipboard?.writeText(document.getElementById('raw-content').textContent)
                .then(() => alert('Copiado.'));
        };

        // ── Render proposals ──────────────────────────────────────────────────
        function prioClass(p) {
            return p === 'alta' ? 'bx b-alta' : p === 'baja' ? 'bx b-baja' : 'bx b-media';
        }

        function propSection(icon, color, title, count, bodyHtml) {
            if (!bodyHtml) return '';
            return `<div class="prop-section">
                <div class="prop-section-hdr">
                    <span style="font-size:14px;color:${color}">${icon}</span>
                    <span style="font-size:11px;font-weight:700;color:${color}">${title}</span>
                    <span style="font-size:10px;color:#475569">(${count})</span>
                </div>
                <div class="prop-section-body">${bodyHtml}</div>
            </div>`;
        }

        function renderCreateProposal(res) {
            const d = res.data;
            showPanel('proposal');
            document.getElementById('prop-nombre').textContent = d.nombre || '—';
            document.getElementById('prop-descripcion').textContent = d.descripcion || '';
            document.getElementById('prop-type-badge').textContent = '· Nuevo proyecto';
            document.getElementById('prop-resumen-wrap').classList.add('hidden');
            document.getElementById('prop-warnings').classList.add('hidden');
            document.getElementById('success-box').classList.add('hidden');
            document.getElementById('btn-confirm').style.display = '';
            document.getElementById('btn-cancel').style.display  = '';

            document.getElementById('prop-sections').innerHTML = buildCreateSections(d);
        }

        function renderImproveProposal(res) {
            const d = res.data;
            showPanel('proposal');
            document.getElementById('prop-nombre').textContent = '';
            document.getElementById('prop-descripcion').textContent = '';
            document.getElementById('prop-type-badge').textContent = '· Mejora de «' + esc(res.proyecto_nombre) + '»';
            document.getElementById('prop-resumen-wrap').classList.remove('hidden');
            document.getElementById('prop-resumen').textContent = d.resumen || '—';
            document.getElementById('prop-warnings').classList.add('hidden');
            document.getElementById('success-box').classList.add('hidden');
            document.getElementById('btn-confirm').style.display = '';
            document.getElementById('btn-cancel').style.display  = '';

            document.getElementById('prop-sections').innerHTML = buildImproveSections(d);
        }

        function buildCreateSections(d) {
            let html = '';
            const reqs = d.requerimientos || [];
            if (reqs.length) {
                html += propSection('📋', '#a5b4fc', 'Requerimientos', reqs.length,
                    reqs.map(r => `<div class="prop-item">
                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                            <span class="bx ${r.tipo === 'no_funcional' ? 'b-nofunc' : 'b-func'}">${esc(r.tipo||'funcional')}</span>
                            <span class="${prioClass(r.prioridad)}">${esc(r.prioridad||'media')}</span>
                        </div>
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(r.titulo||'—')}</p>
                        ${r.descripcion?`<p style="font-size:11px;color:#64748b;margin-top:2px">${esc(r.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            const tasks = d.tareas || [];
            if (tasks.length) {
                html += propSection('✅', '#6ee7b7', 'Tareas', tasks.length,
                    tasks.map(t => `<div class="prop-item">
                        <span class="${prioClass(t.prioridad)}">${esc(t.prioridad||'media')}</span>
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9;margin-top:2px">${esc(t.titulo||'—')}</p>
                        ${t.descripcion?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(t.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            const sprints = d.sprints || [];
            if (sprints.length) {
                html += propSection('🏃', '#fbbf24', 'Sprints', sprints.length,
                    sprints.map(s => `<div class="prop-item">
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(s.nombre||'—')}</p>
                        ${s.objetivo?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(s.objetivo)}</p>`:''}
                        ${(s.semanas||s.duracion_semanas)?`<span style="font-size:10px;color:#818cf8">${s.semanas||s.duracion_semanas} semanas</span>`:''}
                    </div>`).join(''));
            }
            const insumos = d.insumos || [];
            if (insumos.length) {
                html += propSection('📦', '#fb923c', 'Insumos', insumos.length,
                    insumos.map(i => `<div class="prop-item">
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(i.titulo||i.nombre||'—')}</p>
                        ${i.tipo?`<span style="font-size:10px;color:#94a3b8">[${esc(i.tipo)}]</span>`:''}
                        ${(i.contenido||i.descripcion)?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(i.contenido||i.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            return html || '<p style="font-size:12px;color:#475569;padding:8px 0">Sin propuestas concretas.</p>';
        }

        function buildImproveSections(d) {
            let html = '';
            const rn = d.requerimientos_nuevos || [];
            if (rn.length) {
                html += propSection('📋', '#a5b4fc', 'Requerimientos a agregar', rn.length,
                    rn.map(r => `<div class="prop-item">
                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                            <span class="bx b-new">nuevo</span>
                            <span class="bx ${r.tipo==='no_funcional'?'b-nofunc':'b-func'}">${esc(r.tipo||'funcional')}</span>
                            <span class="${prioClass(r.prioridad)}">${esc(r.prioridad||'media')}</span>
                        </div>
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(r.titulo||'—')}</p>
                        ${r.descripcion?`<p style="font-size:11px;color:#64748b;margin-top:2px">${esc(r.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            const tn = d.tareas_nuevas || [];
            if (tn.length) {
                html += propSection('✅', '#6ee7b7', 'Tareas a agregar', tn.length,
                    tn.map(t => `<div class="prop-item">
                        <span class="bx b-new">nueva</span>
                        <span class="${prioClass(t.prioridad)}">${esc(t.prioridad||'media')}</span>
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9;margin-top:2px">${esc(t.titulo||'—')}</p>
                        ${t.descripcion?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(t.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            const tu = d.actualizaciones_tareas || [];
            if (tu.length) {
                html += propSection('🔄', '#fcd34d', 'Tareas a actualizar', tu.length,
                    tu.map(u => `<div class="prop-item">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="bx b-upd">actualizar</span>
                            <span style="font-size:11px;color:#fca5a5;text-decoration:line-through">${esc(u.titulo_actual||'—')}</span>
                            <span style="color:#64748b">→</span>
                            <span style="font-size:12px;font-weight:600;color:#6ee7b7">${esc(u.titulo_nuevo||'—')}</span>
                        </div>
                        ${u.descripcion_nueva?`<p style="font-size:11px;color:#64748b;margin-top:3px">${esc(u.descripcion_nueva)}</p>`:''}
                    </div>`).join(''));
            }
            const sn = d.sprints_nuevos || [];
            if (sn.length) {
                html += propSection('🏃', '#fbbf24', 'Sprints a agregar', sn.length,
                    sn.map(s => `<div class="prop-item">
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(s.nombre||'—')}</p>
                        ${s.objetivo?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(s.objetivo)}</p>`:''}
                        ${s.semanas?`<span style="font-size:10px;color:#818cf8">${s.semanas} semanas</span>`:''}
                    </div>`).join(''));
            }
            const inp = d.insumos_nuevos || [];
            if (inp.length) {
                html += propSection('📦', '#fb923c', 'Insumos a agregar', inp.length,
                    inp.map(i => `<div class="prop-item">
                        <p style="font-size:12px;font-weight:600;color:#f1f5f9">${esc(i.titulo||i.nombre||'—')}</p>
                        ${i.tipo?`<span style="font-size:10px;color:#94a3b8">[${esc(i.tipo)}]</span>`:''}
                        ${(i.contenido||i.descripcion)?`<p style="font-size:11px;color:#64748b;margin-top:1px">${esc(i.contenido||i.descripcion)}</p>`:''}
                    </div>`).join(''));
            }
            return html || '<p style="font-size:12px;color:#475569;padding:8px 0">La IA no propuso cambios concretos. Sé más específico.</p>';
        }

        function showSuccess(data) {
            document.getElementById('btn-confirm').style.display = 'none';
            document.getElementById('btn-cancel').style.display  = 'none';
            const sb = document.getElementById('success-box');
            sb.classList.remove('hidden');
            document.getElementById('success-msg').textContent = data.message || 'Cambios guardados.';
            const c = data.counts || {};
            let html = '';
            if (c.requerimientos)  html += `<p>📋 ${c.requerimientos} requerimiento(s)</p>`;
            if (c.tareas)          html += `<p>✅ ${c.tareas} tarea(s) — visibles en Scrum Board</p>`;
            if (c.reqs_new)        html += `<p>📋 ${c.reqs_new} requerimiento(s) nuevo(s)</p>`;
            if (c.tasks_new)       html += `<p>✅ ${c.tasks_new} tarea(s) nueva(s)</p>`;
            if (c.tasks_updated)   html += `<p>🔄 ${c.tasks_updated} tarea(s) actualizada(s)</p>`;
            if (c.sprints)         html += `<p>🏃 ${c.sprints} sprint(s)</p>`;
            if (c.insumos)         html += `<p>📦 ${c.insumos} insumo(s)</p>`;
            document.getElementById('success-counts').innerHTML = html;
            const link = document.getElementById('success-link');
            if (data.url) { link.href = data.url; link.classList.remove('hidden'); }
            if (data.warnings && data.warnings.length) {
                const wEl = document.getElementById('prop-warnings');
                wEl.classList.remove('hidden');
                document.getElementById('prop-warnings-list').innerHTML =
                    data.warnings.map(w => `<p>• ${esc(w)}</p>`).join('');
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        //  HISTORY
        // ══════════════════════════════════════════════════════════════════════
        window.toggleHistory = function () {
            S.historyOpen = !S.historyOpen;
            const content  = document.getElementById('history-content');
            const chevron  = document.getElementById('history-chevron');
            content.style.display = S.historyOpen ? 'block' : 'none';
            chevron.style.transform = S.historyOpen ? 'rotate(180deg)' : '';
            if (S.historyOpen) loadHistory();
        };

        function loadHistory() {
            const list = document.getElementById('history-list');
            list.innerHTML = '<div class="text-center py-4"><span style="font-size:11px;color:#374151">Cargando…</span></div>';

            fetch('{{ route("asistente-ia.historial") }}', { headers: { 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json())
            .then(data => {
                S.allItems = data.history || [];
                filterHistoryItems();
            })
            .catch(() => {
                document.getElementById('history-list').innerHTML = '<p style="font-size:11px;color:#ef4444;text-align:center;padding:12px">Error al cargar historial.</p>';
            });
        }

        window.setHistoryFilter = function (f) {
            S.historyFilter = f;
            document.querySelectorAll('.hist-filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`[data-f="${f}"]`)?.classList.add('active');
            filterHistoryItems();
        };

        window.filterHistoryItems = function () {
            const search = (document.getElementById('history-search')?.value || '').toLowerCase();
            const filtered = S.allItems.filter(h => {
                if (S.historyFilter === 'project' && h.proyecto_id !== S.proyectoId) return false;
                if (search) {
                    const haystack = (h.prompt_usuario + ' ' + (h.proyecto_nombre || '') + ' ' + h.modo).toLowerCase();
                    if (!haystack.includes(search)) return false;
                }
                return true;
            });
            renderHistoryList(filtered);
        };

        function estadoClass(e) { return 'he h-' + (e || 'borrador'); }

        function renderHistoryList(items) {
            const list  = document.getElementById('history-list');
            const badge = document.getElementById('history-badge');
            badge.textContent = S.allItems.length > 0 ? String(S.allItems.length) : '';
            badge.classList.toggle('hidden', S.allItems.length === 0);

            if (items.length === 0) {
                list.innerHTML = '<p style="font-size:11px;color:#374151;text-align:center;padding:16px">Sin registros.</p>';
                return;
            }

            list.innerHTML = items.map(h => {
                const isActive = S.parentChatId === h.id;
                return `
                <div class="hist-item${isActive ? ' bg-violet-500/5 border-l-2 border-violet-500/40' : ''}">
                    <span class="${estadoClass(h.estado)}">${esc(h.estado)}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap" style="font-size:10px;color:#475569">
                            <span>${esc(h.created_at_human)}</span>
                            <span>·</span>
                            <span>${esc(h.modo)}</span>
                            ${h.proyecto_nombre ? `<span>·</span><a href="${esc(h.proyecto_url)}" style="color:#818cf8" onclick="event.stopPropagation()">${esc(h.proyecto_nombre)}</a>` : ''}
                        </div>
                        <p style="font-size:11px;color:#cbd5e1;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px"
                           title="${esc(h.prompt_completo)}">${esc(h.prompt_usuario)}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 mt-0.5">
                        <button onclick="viewHistoryItem(${h.id})"
                                style="font-size:10px;color:#6366f1" class="hover:underline hover:text-indigo-400">
                            Ver
                        </button>
                        <button onclick="reuseHistoryItem(${h.id})"
                                style="font-size:10px;color:#a855f7" class="hover:underline hover:text-violet-400">
                            Reutilizar
                        </button>
                        ${h.estado === 'borrador' ? `
                        <button onclick="applyHistoryItem(${h.id})"
                                style="font-size:10px;color:#10b981" class="hover:underline hover:text-emerald-400">
                            Aplicar
                        </button>` : ''}
                        <button onclick="deleteHistoryItem(${h.id})"
                                style="font-size:10px;color:#475569" class="hover:text-red-400 transition-colors">
                            ✕
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        // ── VIEW a single history record ───────────────────────────────────────
        window.viewHistoryItem = async function (id) {
            const r = await fetch('{{ url("asistente-ia/historial") }}/' + id, { headers: { 'X-CSRF-TOKEN': csrf } });
            const data = await r.json();
            if (!data.ok) { alert('Error al cargar el registro.'); return; }

            const rec = data.record;
            S.detailChatId = rec.id;

            // Populate detail panel
            document.getElementById('det-modo').textContent    = rec.modo_label;
            document.getElementById('det-fecha').textContent   = rec.created_at_full + ' (' + rec.created_at_human + ')';
            document.getElementById('det-prompt').textContent  = rec.prompt_usuario;
            setEstadoStyle(document.getElementById('det-estado'), rec.estado);

            const proyWrap = document.getElementById('det-proyecto-wrap');
            if (rec.proyecto_nombre) {
                proyWrap.classList.remove('hidden');
                const link = document.getElementById('det-proyecto-link');
                link.textContent = rec.proyecto_nombre;
                link.href = rec.proyecto_url || '#';
            } else {
                proyWrap.classList.add('hidden');
            }

            const dResp = document.getElementById('det-response-wrap');
            const dd    = rec.datos_detectados;
            if (dd) {
                const summary = dd.resumen || dd.nombre || null;
                if (summary) {
                    dResp.classList.remove('hidden');
                    document.getElementById('det-response').textContent = summary;
                } else dResp.classList.add('hidden');
            } else dResp.classList.add('hidden');

            // Reutilizar button
            document.getElementById('det-reutilizar-btn').onclick = () => reuseHistoryItem(rec.id);

            // Aplicar button
            const applyBtn = document.getElementById('det-aplicar-btn');
            if (rec.estado === 'borrador' && rec.datos_detectados) {
                applyBtn.classList.remove('hidden');
                applyBtn.onclick = () => applyHistoryItem(rec.id);
            } else {
                applyBtn.classList.add('hidden');
            }

            showPanel('detail');
        };

        window.closeDetail = function () {
            S.detailChatId = null;
            if (S.parentChatId) showPanel('context');
            else if (S.pendingId) showPanel('proposal');
            else showPanel('empty');
        };

        // ── REUTILIZE a history record ─────────────────────────────────────────
        window.reuseHistoryItem = async function (id) {
            const r = await fetch('{{ url("asistente-ia/historial") }}/' + id, { headers: { 'X-CSRF-TOKEN': csrf } });
            const data = await r.json();
            if (!data.ok) { alert('Error al cargar el registro.'); return; }

            const rec = data.record;

            // 1. Update state
            S.parentChatId = rec.id;
            S.mode         = rec.modo === 'crear' ? 'crear' : 'mejorar';
            if (rec.proyecto_id) {
                S.proyectoId = rec.proyecto_id;
            }

            // 2. Update mode UI (silently, keepContext=true)
            setMode(S.mode, true);

            // 3. Select project in dropdown if available
            const sel = document.getElementById('proyecto-select');
            if (sel && rec.proyecto_id) {
                sel.value = rec.proyecto_id;
                const cpBtn = document.getElementById('btn-clear-project');
                if (cpBtn) cpBtn.classList.remove('hidden');
            }

            // 4. Update context badge in left panel
            document.getElementById('ctx-badge-id').textContent = '#' + rec.id;
            document.getElementById('ctx-badge-proyecto').textContent = rec.proyecto_nombre
                ? '📁 ' + rec.proyecto_nombre
                : '(sin proyecto)';
            document.getElementById('ctx-badge-prompt').textContent = rec.prompt_usuario;
            document.getElementById('context-badge').classList.remove('hidden');

            // 5. Populate context panel (right)
            document.getElementById('ctx-id').textContent = '#' + rec.id;
            document.getElementById('ctx-modo-label').textContent = rec.modo_label;
            setEstadoStyle(document.getElementById('ctx-estado-label'), rec.estado);

            const ctxProjWrap = document.getElementById('ctx-proyecto-wrap');
            if (rec.proyecto_nombre) {
                ctxProjWrap.classList.remove('hidden');
                const pLink = document.getElementById('ctx-proyecto-link');
                pLink.textContent = rec.proyecto_nombre;
                pLink.href = rec.proyecto_url || '#';
            } else {
                ctxProjWrap.classList.add('hidden');
            }

            document.getElementById('ctx-prompt-text').textContent = rec.prompt_usuario;

            const ctxRespWrap = document.getElementById('ctx-response-wrap');
            const dd          = rec.datos_detectados;
            if (dd) {
                const summary = dd.resumen || dd.nombre || null;
                if (summary) {
                    ctxRespWrap.classList.remove('hidden');
                    document.getElementById('ctx-response-text').textContent = summary;
                } else ctxRespWrap.classList.add('hidden');
            } else ctxRespWrap.classList.add('hidden');

            // 6. Show context panel + clear prompt
            showPanel('context');
            promptInput.value = '';
            promptInput.dispatchEvent(new Event('input'));
            promptInput.focus();

            // 7. Refresh history list to highlight active item
            if (S.historyOpen) filterHistoryItems();
        };

        // ── APPLY a borrador record from history ──────────────────────────────
        window.applyHistoryItem = async function (id) {
            if (!confirm('¿Aplicar esta propuesta guardada? Se guardarán cambios en la base de datos.')) return;
            const r = await fetch('{{ route("asistente-ia.apply") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ chat_id: id, action: 'confirm' }),
            });
            const data = await r.json();
            if (data.ok) {
                alert(data.message || 'Cambios aplicados correctamente.');
                loadHistory();
            } else {
                alert(data.error || 'Error al aplicar la propuesta.');
            }
        };

        // ── DELETE a history record ────────────────────────────────────────────
        window.deleteHistoryItem = async function (id) {
            if (!confirm('¿Seguro que deseas borrar este registro del historial?\n\nEsto NO eliminará datos del proyecto (tareas, requerimientos, sprints, insumos).')) return;
            const r = await fetch('{{ url("asistente-ia/historial") }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            });
            const data = await r.json();
            if (data.ok) {
                // If we were viewing/reutilizing this record, clear context
                if (S.parentChatId === id) { S.parentChatId = null; updateContextBadge(); }
                if (S.detailChatId === id) closeDetail();
                S.allItems = S.allItems.filter(h => h.id !== id);
                filterHistoryItems();
            } else {
                alert('No se pudo borrar el registro.');
            }
        };

        window.clearHistory = async function (scope) {
            const label = scope === 'project' && S.proyectoId ? 'del proyecto actual' : 'completo';
            if (!confirm(`¿Limpiar el historial ${label}?\n\nEsto NO elimina proyectos, tareas, requerimientos, sprints ni insumos.`)) return;
            const payload = {};
            if (scope === 'project' && S.proyectoId) payload.proyecto_id = S.proyectoId;
            const r = await fetch('{{ route("asistente-ia.historial.clear") }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            if (data.ok) {
                S.parentChatId = null; S.detailChatId = null;
                updateContextBadge();
                loadHistory();
                if (!S.pendingId) showPanel('empty');
            }
        };

        // ── Utility: set estado styles ─────────────────────────────────────────
        function setEstadoStyle(el, estado) {
            const styles = {
                aplicado:   '#10b981',
                borrador:   '#f59e0b',
                descartado: '#64748b',
                error:      '#ef4444',
            };
            el.textContent  = estado || '—';
            el.style.color  = styles[estado] || '#94a3b8';
            el.style.fontWeight = '700';
        }

    })();
    </script>
    @endpush

</x-app-layout>
