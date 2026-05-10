<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-400" style="font-size:20px;font-variation-settings:'FILL' 1">auto_awesome</span>
                <span class="text-sm font-bold text-white">{{ __('app.ai.title') }}</span>
                <span class="text-gray-600 text-sm mx-1">·</span>
                <span class="text-xs text-gray-500">{{ __('app.ai.subtitle') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full animate-pulse {{ $aiOnline ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                <span class="text-xs {{ $aiOnline ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $aiOnline ? 'Asistente disponible' : 'Servicio no disponible' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="h-[calc(100vh-64px)] flex flex-col overflow-hidden ai-assistant-wrapper">

        {{-- ══ MAIN AREA ═══════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col md:flex-row min-h-0 overflow-hidden ai-panel-row">

            {{-- ── LEFT PANEL ──────────────────────────────────────────────────── --}}
            <div class="ai-left-panel w-full md:w-[380px] md:flex-shrink-0 border-b md:border-b-0 md:border-r border-white/6 overflow-y-auto"
                 style="background:rgba(11,13,20,0.98);scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.06) transparent">
                <div class="p-5 space-y-4">

                    @unless($aiOnline)
                    <div class="rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined" style="font-size:15px">warning</span>Asistente no disponible
                        </p>
                        <p class="text-[11px] text-amber-200/70 mt-1">
                            El servicio de generacion IA no esta activo. Contacta al administrador.
                        </p>
                    </div>
                    @endunless

                    {{-- ── CONTEXT BADGE (shown when reutilizing a history record) ── --}}
                    <div id="context-badge" class="hidden rounded-xl border border-violet-500/30 bg-violet-500/10 px-3 py-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold text-violet-300 uppercase tracking-widest mb-0.5">
                                    {{ __('app.ai.ctx_continuing') }}
                                    <span class="text-violet-400" id="ctx-badge-id"></span>
                                </p>
                                <p class="text-xs text-gray-300 truncate" id="ctx-badge-proyecto"></p>
                                <p class="text-[10px] text-gray-600 truncate mt-0.5 italic" id="ctx-badge-prompt"></p>
                            </div>
                            <button onclick="clearContext()"
                                    class="flex-shrink-0 text-gray-600 hover:text-red-400 transition-colors mt-0.5"
                                    title="{{ __('app.ai.ctx_cancel_title') }}">
                                <span class="material-symbols-outlined" style="font-size:16px">close</span>
                            </button>
                        </div>
                    </div>

                    {{-- ── MODE ────────────────────────────────────────────────────── --}}
                    <div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">{{ __('app.ai.mode_label') }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button id="mode-crear" onclick="setMode('crear')"
                                    class="mode-btn active text-left rounded-xl border px-3 py-2.5 transition-all">
                                <span class="material-symbols-outlined block mb-1" style="font-size:17px">add_circle</span>
                                <span class="text-xs font-semibold block">{{ __('app.ai.create_project') }}</span>
                                <span class="text-[10px] block" style="color:#64748b">{{ __('app.ai.create_new') }}</span>
                            </button>
                            <button id="mode-mejorar" onclick="setMode('mejorar')"
                                    class="mode-btn text-left rounded-xl border px-3 py-2.5 transition-all">
                                <span class="material-symbols-outlined block mb-1" style="font-size:17px">edit_note</span>
                                <span class="text-xs font-semibold block">{{ __('app.ai.improve_project') }}</span>
                                <span class="text-[10px] block" style="color:#64748b">{{ __('app.ai.improve_existing') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- ── PROJECT SELECTOR ─────────────────────────────────────────── --}}
                    <div id="project-selector-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">
                            {{ __('app.ai.project_to_improve') }}
                        </label>
                        @if($proyectos->isEmpty())
                            <p class="text-xs text-gray-600 italic">{{ __('app.ai.no_projects') }}</p>
                        @else
                            <select id="proyecto-select"
                                    class="dark-form-select w-full rounded-xl border border-white/10 px-3 py-2 text-sm text-white
                                           focus:outline-none focus:border-indigo-500/50 transition-colors"
                                    style="background:#111827">
                                <option value="">{{ __('app.ai.select_project') }}</option>
                                @foreach($proyectos as $p)
                                <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">
                                    {{ $p->nombre }}
                                </option>
                                @endforeach
                            </select>
                            <div class="mt-2 flex items-center gap-2">
                                <p id="project-hint" class="text-[10px] text-amber-400/80 hidden flex-1">
                                    {{ __('app.ai.select_hint') }}
                                </p>
                                <button id="btn-load-structure" type="button"
                                        class="hidden inline-flex items-center gap-1 rounded-lg border border-indigo-500/40 px-2.5 py-1 text-[11px] font-semibold text-indigo-300 transition hover:bg-indigo-500/10">
                                    <span class="material-symbols-outlined" style="font-size:13px">download</span>
                                    Cargar estructura
                                </button>
                            </div>
                            {{-- Loaded project stats --}}
                            <div id="project-loaded-info" class="hidden mt-2 rounded-xl border border-emerald-500/25 bg-emerald-500/8 px-3 py-2">
                                <p class="text-[10px] font-bold text-emerald-400 mb-0.5">Estructura cargada</p>
                                <p id="project-loaded-counts" class="text-[10px] text-slate-400 leading-relaxed"></p>
                            </div>
                        @endif
                    </div>

                    {{-- Model is resolved internally; not exposed to users --}}
                    <input type="hidden" id="model-select" value="{{ $defaultModel }}">

                    {{-- ── PROMPT TEXTAREA ──────────────────────────────────────────── --}}
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">
                            <span id="textarea-label">{{ __('app.ai.project_desc_label') }}</span>
                        </label>
                        <textarea id="prompt-input" rows="8"
                                  class="w-full rounded-xl border border-white/10 px-4 py-3 text-sm resize-none
                                         leading-relaxed focus:outline-none focus:border-indigo-500/50 transition-colors"
                                  style="background:#111827;color:#f1f5f9;caret-color:#818cf8;"
                                  placeholder="{{ __('app.ai.ph_create') }}"
                        ></textarea>
                        <div class="flex justify-between mt-1">
                            <span id="prompt-hint" class="text-[10px] text-red-400/80"></span>
                            <span id="prompt-count" class="text-[10px]" style="color:#475569">0 / 3000</span>
                        </div>
                    </div>

                    {{-- ── ERROR BOX ────────────────────────────────────────────────── --}}
                    <div id="error-box" class="hidden rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 space-y-1">
                        <p class="text-xs font-semibold text-red-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined" style="font-size:15px">error</span>{{ __('app.ai.error_title') }}
                        </p>
                        <p id="error-msg" class="text-xs text-red-200/80 leading-relaxed"></p>
                        <details id="raw-details" class="hidden">
                            <summary class="text-[10px] text-red-300/50 cursor-pointer hover:text-red-300 select-none">
                                {{ __('app.ai.error_raw_view') }}
                            </summary>
                            <pre id="raw-content" class="mt-1.5 text-[10px] text-gray-400 bg-black/30 rounded-lg p-2
                                                          overflow-x-auto whitespace-pre-wrap max-h-28 font-mono"></pre>
                            <button onclick="copyRaw()" class="text-[10px] text-indigo-400 hover:text-indigo-300">{{ __('app.ai.error_copy') }}</button>
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
                        <span id="btn-label">{{ __('app.ai.btn_gen_create') }}</span>
                    </button>

                    {{-- ── Costo de la acción actual ──────────────────────── --}}
                    <p class="text-[10px]" style="color:#374151">
                        <span class="material-symbols-outlined align-middle"
                              style="font-size:10px;color:#4b5563;font-variation-settings:'FILL' 1">stars</span>
                        Costo: <span id="action-cost" class="font-semibold" style="color:#6366f1">10</span> créditos IA
                    </p>

                    {{-- ── Widget créditos IA ──────────────────────────────── --}}
                    {{-- Ocupa el ancho del panel izquierdo (380 px en desktop, 100% en mobile) --}}
                    {{-- El estilo diferenciado (fondo indigo sutil) lo hace visualmente compacto --}}
                    <div id="credit-widget"
                         class="rounded-2xl p-3 space-y-2"
                         style="background:rgba(99,102,241,0.07);
                                border:1px solid rgba(99,102,241,0.18);">

                        {{-- Encabezado --}}
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span class="material-symbols-outlined"
                                  style="font-size:13px;color:#818cf8;font-variation-settings:'FILL' 1">stars</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest"
                                  style="color:#6366f1">Créditos IA</span>
                        </div>

                        {{-- Disponibles --}}
                        <div>
                            <span id="credit-available"
                                  class="text-3xl font-black leading-none"
                                  style="color:#fff">—</span>
                            <span class="text-[11px] ml-1.5" style="color:#64748b">disponibles</span>
                        </div>

                        {{-- Barra de progreso --}}
                        <div class="h-1.5 rounded-full overflow-hidden"
                             style="background:rgba(255,255,255,0.07)">
                            <div id="credit-bar"
                                 class="h-full rounded-full transition-all duration-700"
                                 style="width:0%;
                                        background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div>
                        </div>

                        {{-- Usados --}}
                        <div class="flex items-baseline gap-1">
                            <span class="text-[10px]" style="color:#475569">Usados:</span>
                            <span id="credit-ratio"
                                  class="text-[11px] font-bold"
                                  style="color:#a5b4fc">— / —</span>
                        </div>

                        {{-- Separador + metadatos --}}
                        <div class="pt-1.5 space-y-0.5"
                             style="border-top:1px solid rgba(99,102,241,0.15)">
                            <p class="text-[10px]" style="color:#475569">
                                Plan: <span id="credit-plan"
                                            class="font-semibold"
                                            style="color:#818cf8">—</span>
                            </p>
                            <p class="text-[10px]" style="color:#475569">
                                Vence: <span id="credit-ends"
                                             style="color:#64748b">—</span>
                            </p>
                        </div>

                        {{-- Nota --}}
                        <p class="text-[9px] leading-relaxed" style="color:#374151">
                            Solo se descuentan créditos cuando la IA responde correctamente.
                        </p>

                        {{-- Ver planes --}}
                        <a href="{{ route('planes.index') }}"
                           class="flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg text-[10px] font-semibold transition-all hover:opacity-80"
                           style="background:rgba(99,102,241,0.1);color:#818cf8;border:1px solid rgba(99,102,241,0.2);">
                            <span class="material-symbols-outlined" style="font-size:12px;">workspace_premium</span>
                            Ver planes
                        </a>

                        <span id="credit-today" class="hidden"></span>
                    </div>

                    <p class="text-[10px] leading-relaxed" style="color:#374151">
                        {{ __('app.ai.disclaimer') }}
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
                    <p class="text-sm font-semibold text-white mb-1">{{ __('app.ai.ready_title') }}</p>
                    <p class="text-xs max-w-xs leading-relaxed" style="color:#475569">
                        {{ __('app.ai.ready_hint') }}
                    </p>
                </div>

                {{-- Loading --}}
                <div id="panel-loading" class="hidden flex-col items-center justify-center h-full p-8 text-center">
                    <div class="flex gap-1.5 mb-4">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce" style="animation-delay:130ms"></span>
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay:260ms"></span>
                    </div>
                    <p class="text-sm font-semibold text-white mb-1">{{ __('app.ai.analyzing') }}</p>
                    <p class="text-xs" style="color:#475569">{{ __('app.ai.analyzing_hint') }}</p>
                </div>

                {{-- Context panel (shown after "Reutilizar") --}}
                <div id="panel-context" class="hidden p-6 max-w-2xl mx-auto space-y-4">
                    <div class="rounded-2xl border border-violet-500/20 px-5 py-4" style="background:rgba(139,92,246,0.06)">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-violet-400" style="font-size:15px;font-variation-settings:'FILL' 1">history</span>
                            <span class="text-[10px] font-bold text-violet-300 uppercase tracking-widest">
                                {{ __('app.ai.ctx_continuing') }} <span id="ctx-id" class="text-violet-400"></span>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs mb-3">
                            <div>
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.ctx_prev_mode') }}</p>
                                <p class="font-semibold text-white" id="ctx-modo-label"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.ctx_status') }}</p>
                                <p class="font-semibold" id="ctx-estado-label"></p>
                            </div>
                            <div class="col-span-2" id="ctx-proyecto-wrap">
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.ctx_project') }}</p>
                                <a id="ctx-proyecto-link" href="#" class="text-indigo-400 hover:text-indigo-300 font-semibold" id="ctx-proyecto-label"></a>
                            </div>
                        </div>
                        <div>
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">{{ __('app.ai.ctx_prev_prompt') }}</p>
                            <p class="text-sm text-gray-300 leading-relaxed bg-black/20 rounded-lg px-3 py-2" id="ctx-prompt-text"></p>
                        </div>
                        <div id="ctx-response-wrap" class="hidden mt-3">
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">{{ __('app.ai.ctx_prev_resp') }}</p>
                            <p class="text-sm text-gray-400 leading-relaxed bg-black/20 rounded-lg px-3 py-2 italic" id="ctx-response-text"></p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-white/6 px-4 py-3" style="background:rgba(255,255,255,0.02)">
                        <p class="text-xs text-gray-400 leading-relaxed">
                            {{ __('app.ai.ctx_write_hint') }}
                        </p>
                    </div>
                </div>

                {{-- Detail panel (shown after "Ver") --}}
                <div id="panel-detail" class="hidden p-6 max-w-2xl mx-auto space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <button onclick="closeDetail()"
                                class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-white transition-colors">
                            <span class="material-symbols-outlined" style="font-size:15px">arrow_back</span>
                            {{ __('app.ai.btn_back') }}
                        </button>
                        <span style="color:#334155">|</span>
                        <span class="text-xs font-semibold text-gray-400">{{ __('app.ai.detail_title') }}</span>
                    </div>

                    {{-- Detail header --}}
                    <div class="rounded-2xl border border-white/8 px-5 py-4" style="background:rgba(255,255,255,0.03)">
                        <div class="grid grid-cols-2 gap-3 text-xs mb-4">
                            <div>
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.det_mode') }}</p>
                                <p class="font-semibold text-white" id="det-modo"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.det_status') }}</p>
                                <p class="font-semibold" id="det-estado"></p>
                            </div>
                            <div>
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.det_date') }}</p>
                                <p style="color:#94a3b8" id="det-fecha"></p>
                            </div>
                            <div id="det-proyecto-wrap">
                                <p style="color:#64748b" class="mb-0.5">{{ __('app.ai.det_project') }}</p>
                                <a id="det-proyecto-link" href="#" class="text-indigo-400 hover:text-indigo-300 font-semibold truncate block"></a>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">{{ __('app.ai.det_prompt') }}</p>
                                <p class="text-sm text-gray-200 leading-relaxed bg-black/20 rounded-lg px-3 py-2" id="det-prompt"></p>
                            </div>
                            <div id="det-response-wrap" class="hidden">
                                <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">{{ __('app.ai.det_response') }}</p>
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
                            {{ __('app.ai.btn_reuse') }}
                        </button>
                        <button id="det-aplicar-btn"
                                class="hidden flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold
                                       bg-emerald-600 hover:bg-emerald-500 text-white active:scale-95 transition-all">
                            <span class="material-symbols-outlined" style="font-size:15px">check</span>
                            {{ __('app.ai.btn_apply_hist') }}
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
                            <span class="text-[10px] uppercase tracking-widest font-bold text-emerald-400">{{ __('app.ai.proposal_generated') }}</span>
                            <span id="prop-type-badge" class="text-[10px]" style="color:#64748b"></span>
                        </div>
                        <p class="text-xl font-black text-white leading-tight" id="prop-nombre"></p>
                        <div id="prop-nombre-sep" class="hidden"></div>
                        <p id="prop-descripcion" class="text-sm leading-relaxed mt-1" style="color:#94a3b8"></p>
                        <div id="prop-resumen-wrap" class="hidden mt-3 pt-3 border-t border-white/6">
                            <p style="color:#64748b;font-size:10px" class="mb-1 uppercase tracking-widest font-bold">{{ __('app.ai.changes_summary') }}</p>
                            <p id="prop-resumen" class="text-sm text-gray-300 leading-relaxed"></p>
                        </div>
                    </div>

                    <div id="draft-tools" class="hidden rounded-2xl border border-indigo-500/20 bg-indigo-500/5 px-4 py-3 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-indigo-200">Ajustar borrador actual</p>
                                <p class="text-[11px] text-slate-500">Pide cambios sin perder la propuesta visible.</p>
                            </div>
                            <span id="draft-status" class="hidden text-[11px] font-semibold text-indigo-300"></span>
                        </div>
                        <textarea id="draft-adjust-input" rows="2"
                                  class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Ej: hazlo mas simple, agrega seguridad, reduce los sprints a 2..."></textarea>
                        <div class="flex flex-wrap gap-2">
                            <button id="btn-refine-draft"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-indigo-500">
                                <span class="material-symbols-outlined" style="font-size:15px">auto_fix_high</span>
                                Pedir ajuste a la IA
                                <span class="text-[9px] font-normal opacity-60">(4 créd.)</span>
                            </button>
                            <button id="btn-regenerate-draft"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-indigo-500/50 hover:text-indigo-200">
                                <span class="material-symbols-outlined" style="font-size:15px">refresh</span>
                                Regenerar propuesta completa
                                <span class="text-[9px] font-normal opacity-50">(10 créd.)</span>
                            </button>
                        </div>
                    </div>

                    <div id="prop-sections" class="space-y-3"></div>

                    <div id="prop-warnings" class="hidden rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-300 mb-1">{{ __('app.ai.warnings_title') }}</p>
                        <div id="prop-warnings-list" class="space-y-0.5 text-xs text-amber-200/80"></div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button id="btn-confirm"
                                class="flex items-center gap-1.5 px-5 py-2.5 rounded-xl
                                       bg-emerald-600 hover:bg-emerald-500 active:scale-95
                                       text-white text-sm font-bold transition-all">
                            <span class="material-symbols-outlined" style="font-size:17px">check</span>
                            {{ __('app.ai.btn_confirm') }}
                        </button>
                        <button id="btn-cancel"
                                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-white/12
                                       hover:border-white/25 text-gray-400 hover:text-white text-sm font-medium transition-all">
                            <span class="material-symbols-outlined" style="font-size:17px">close</span>
                            {{ __('app.ai.btn_cancel') }}
                        </button>
                        <button id="btn-edit"
                                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-white/8
                                       hover:border-indigo-500/40 text-gray-500 hover:text-indigo-300 text-sm font-medium transition-all ml-auto">
                            <span class="material-symbols-outlined" style="font-size:16px">edit</span>
                            {{ __('app.ai.btn_edit_instr') }}
                        </button>
                    </div>

                    <div id="success-box" class="hidden rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-5 py-4">
                        <p class="text-sm font-bold text-emerald-300" id="success-msg"></p>
                        <div id="success-counts" class="text-xs mt-2 space-y-0.5" style="color:#94a3b8"></div>
                        <a id="success-link" href="#" class="hidden mt-3 inline-block text-xs font-bold text-indigo-400 hover:text-indigo-300">
                            {{ __('app.ai.view_project') }}
                        </a>
                    </div>
                </div>

                <div id="draft-modal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-black/70 backdrop-blur-sm">
                    <div class="w-full max-w-2xl mx-4 rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl max-h-[85vh] overflow-y-auto">
                        <div class="mb-5 flex items-start justify-between gap-3">
                            <div>
                                <p id="draft-modal-title" class="text-sm font-bold text-white">Editar elemento</p>
                                <p class="text-xs text-slate-500 mt-0.5">Los cambios se aplican al borrador, no se guardan hasta confirmar.</p>
                            </div>
                            <button type="button" onclick="closeDraftModal()"
                                    class="flex-shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-white">
                                <span class="material-symbols-outlined" style="font-size:18px">close</span>
                            </button>
                        </div>
                        <div id="draft-modal-fields" class="space-y-4"></div>
                        <div class="mt-6 flex justify-end gap-2 border-t border-slate-700/60 pt-4">
                            <button type="button" onclick="closeDraftModal()"
                                    class="rounded-xl border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-400 hover:text-white">
                                Cancelar
                            </button>
                            <button type="button" onclick="saveDraftModal()"
                                    class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-indigo-500 active:scale-95">
                                Guardar cambios
                            </button>
                        </div>
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
                    <span class="text-xs font-semibold" style="color:#6b7280">{{ __('app.ai.history_title') }}</span>
                    <span id="history-badge"
                          class="hidden text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-1.5 py-0.5 rounded-full"></span>
                </div>
                <div class="flex items-center gap-3" onclick="event.stopPropagation()">
                    <button id="btn-clear-project"
                            class="text-[10px] hidden hover:text-amber-400 transition-colors"
                            style="color:#4b5563" onclick="clearHistory('project')">
                        {{ __('app.ai.btn_clear_project') }}
                    </button>
                    <button class="text-[10px] hover:text-red-400 transition-colors"
                            style="color:#4b5563" onclick="clearHistory('all')">
                        {{ __('app.ai.btn_clear_all') }}
                    </button>
                </div>
            </div>

            {{-- History content (collapsible) --}}
            <div id="history-content" style="display:none">

                {{-- Filter + search row --}}
                <div class="flex items-center gap-2 px-4 py-2 border-t border-white/5">
                    <button class="hist-filter-btn active" data-f="all" onclick="setHistoryFilter('all')">{{ __('app.ai.history_filter_all') }}</button>
                    <button class="hist-filter-btn" data-f="project" onclick="setHistoryFilter('project')" id="filter-project-btn">
                        {{ __('app.ai.history_filter_proj') }}
                    </button>
                    <div class="flex-1 relative">
                        <input type="text" id="history-search"
                               placeholder="{{ __('app.ai.history_search_ph') }}"
                               class="w-full rounded-lg px-3 py-1 text-[11px] transition-colors focus:outline-none"
                               style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);color:#f1f5f9;"
                               oninput="filterHistoryItems()" />
                    </div>
                </div>

                {{-- List --}}
                <div id="history-list"
                     style="max-height:200px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.06) transparent">
                    <div class="text-center py-4">
                        <span class="text-[11px]" style="color:#374151">{{ __('app.ai.history_click_hint') }}</span>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- end outer --}}

    @push('styles')
    <style>
        /* ── Asistente IA — responsive ─────────────────────────────────────── */
        @media (max-width: 767px) {
            .ai-assistant-wrapper {
                height: auto !important;
                min-height: calc(100vh - 64px);
                overflow: visible !important;
                overflow-y: auto !important;
            }
            .ai-panel-row {
                overflow: visible !important;
                min-height: 0;
            }
            .ai-left-panel {
                max-height: none !important;
                overflow-y: visible !important;
            }
        }
    </style>
    @endpush

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
        .draft-mini-btn { display:inline-flex; align-items:center; gap:4px; border:1px solid rgba(148,163,184,.20); border-radius:8px; padding:4px 8px; font-size:10px; font-weight:700; color:#cbd5e1; background:rgba(15,23,42,.55); transition:all .15s; }
        .draft-mini-btn:hover { border-color:rgba(129,140,248,.55); color:#c7d2fe; background:rgba(79,70,229,.12); }
        .draft-icon-btn { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; color:#94a3b8; border:1px solid rgba(148,163,184,.14); background:rgba(15,23,42,.50); transition:all .15s; }
        .draft-icon-btn:hover { color:#fff; border-color:rgba(129,140,248,.50); background:rgba(79,70,229,.12); }
        .draft-icon-btn.danger:hover { color:#fecaca; border-color:rgba(248,113,113,.50); background:rgba(127,29,29,.20); }
        .draft-icon-btn .material-symbols-outlined { font-size:15px; }

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

        /* ── Dark form selects (modal + sidebar) ────────────────────────────── */
        .dark-form-select { background:#0f172a !important; color:#f1f5f9; }
        .dark-form-select option { background:#0f172a; color:#f1f5f9; }

        /* ── Modal form fields ──────────────────────────────────────────────── */
        #draft-modal input, #draft-modal textarea, #draft-modal select {
            background:#0f172a;
            border-color:#334155;
            color:#f1f5f9;
        }
        #draft-modal input::placeholder, #draft-modal textarea::placeholder { color:#475569; }
        #draft-modal input:focus, #draft-modal textarea:focus, #draft-modal select:focus {
            outline:none;
            border-color:#6366f1;
            box-shadow:0 0 0 1px #6366f1;
        }
        #draft-modal label > span { color:#94a3b8; }
    </style>

    {{-- Pass only routes and UI translations to JavaScript — no provider/model names --}}
    @php
    $aiConfig = array_merge($ai ?? [], [
        'btn_gen_create'      => __('app.ai.btn_gen_create'),
        'btn_gen_improve'     => __('app.ai.btn_gen_improve'),
        'btn_generating'      => __('app.ai.btn_generating'),
        'btn_confirm'         => __('app.ai.btn_confirm'),
        'btn_saving'          => __('app.ai.btn_saving'),
        'btn_cancel'          => __('app.ai.btn_cancel'),
        'btn_edit_instr'      => __('app.ai.btn_edit_instr'),
        'btn_clear_project'   => __('app.ai.btn_clear_project'),
        'btn_clear_all'       => __('app.ai.btn_clear_all'),
        'btn_view'            => __('app.ai.btn_view'),
        'btn_reuse_short'     => __('app.ai.btn_reuse_short'),
        'btn_apply_short'     => __('app.ai.btn_apply_short'),
        'project_desc_label'  => __('app.ai.project_desc_label'),
        'improve_instr_label' => __('app.ai.improve_instr_label'),
        'ph_create'           => __('app.ai.ph_create'),
        'ph_improve'          => __('app.ai.ph_improve'),
        'history_empty'       => __('app.ai.history_empty'),
        'history_loading'     => __('app.ai.history_loading'),
        'history_error'       => __('app.ai.history_error'),
        'new_project_badge'   => __('app.ai.new_project_badge'),
        'improve_badge'       => __('app.ai.improve_badge'),
        'no_concrete'         => __('app.ai.no_concrete'),
        'no_concrete_improve' => __('app.ai.no_concrete_improve'),
        'changes_saved'       => __('app.ai.changes_saved'),
        'view_project'        => __('app.ai.view_project'),
        'error_connection'    => __('app.ai.error_connection'),
        'error_prompt_min'    => __('app.ai.error_prompt_min'),
        'error_no_model'      => __('app.ai.error_no_model'),
        'error_no_project'    => __('app.ai.error_no_project'),
        'error_processing'    => __('app.ai.error_processing'),
        'error_load'          => __('app.ai.error_load'),
        'error_delete'        => __('app.ai.error_delete'),
        'confirm_apply'       => __('app.ai.confirm_apply'),
        'confirm_delete'      => __('app.ai.confirm_delete'),
        'confirm_clear_proj'  => __('app.ai.confirm_clear_proj'),
        'confirm_clear_all'   => __('app.ai.confirm_clear_all'),
        'applied_ok'          => __('app.ai.applied_ok'),
        'applied_err'         => __('app.ai.applied_err'),
        'char_min_hint'       => __('app.ai.char_min_hint'),
        'ctx_no_project'      => __('app.ai.ctx_no_project'),
        'count_reqs'          => __('app.ai.count_reqs'),
        'count_tasks'         => __('app.ai.count_tasks'),
        'count_reqs_new'      => __('app.ai.count_reqs_new'),
        'count_tasks_new'     => __('app.ai.count_tasks_new'),
        'count_tasks_upd'     => __('app.ai.count_tasks_upd'),
        'count_sprints'       => __('app.ai.count_sprints'),
        'count_inputs'        => __('app.ai.count_inputs'),
    ]);
    @endphp
    <script>
    const _ai = @json($aiConfig);
    </script>

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
            generatedProposal: null,
            generatedType: null,
            modal: { section: null, index: null, mode: 'edit' },
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
            promptHint.textContent  = len > 0 && len < 10 ? _ai.char_min_hint : '';
        });

        // ── Mode selector ──────────────────────────────────────────────────────
        window.setMode = function (mode, keepContext) {
            S.mode = mode;
            document.getElementById('mode-crear').classList.toggle('active', mode === 'crear');
            document.getElementById('mode-mejorar').classList.toggle('active', mode === 'mejorar');

            const pw = document.getElementById('project-selector-wrap');
            if (pw) pw.classList.toggle('hidden', mode === 'crear');

            document.getElementById('btn-label').textContent = mode === 'crear'
                ? _ai.btn_gen_create
                : _ai.btn_gen_improve;

            document.getElementById('textarea-label').textContent = mode === 'crear'
                ? _ai.project_desc_label
                : _ai.improve_instr_label;

            promptInput.placeholder = mode === 'crear'
                ? _ai.ph_create
                : _ai.ph_improve;

            const cpBtn = document.getElementById('btn-clear-project');
            if (cpBtn) cpBtn.classList.toggle('hidden', mode === 'crear' || !S.proyectoId);

            if (!keepContext) hideError();
            updateActionCost(mode);
        };

        // Project selector
        const proyectoSelect = document.getElementById('proyecto-select');
        if (proyectoSelect) {
            proyectoSelect.addEventListener('change', function () {
                S.proyectoId = this.value ? parseInt(this.value) : null;
                document.getElementById('project-hint')?.classList.add('hidden');
                const cpBtn = document.getElementById('btn-clear-project');
                if (cpBtn) cpBtn.classList.toggle('hidden', !S.proyectoId);
                const lsBtn = document.getElementById('btn-load-structure');
                if (lsBtn) lsBtn.classList.toggle('hidden', !S.proyectoId);
                // Hide stale loaded info if project changes
                document.getElementById('project-loaded-info')?.classList.add('hidden');
                // Auto-load structure when project is selected in improve mode
                if (S.proyectoId && S.mode === 'mejorar') {
                    loadProjectStructure(S.proyectoId);
                }
            });
        }

        // Load structure button
        document.getElementById('btn-load-structure')?.addEventListener('click', function () {
            if (S.proyectoId) loadProjectStructure(S.proyectoId);
        });

        // ── Load project structure into editable draft ─────────────────────────
        async function loadProjectStructure(proyectoId) {
            if (!proyectoId) return;
            showPanel('loading');
            hideError();
            try {
                const r = await fetch(`${_ai.routes.estructuraBase}/${proyectoId}/estructura`, {
                    headers: { 'X-CSRF-TOKEN': csrf },
                });
                const data = await r.json();
                if (!data.ok) {
                    showPanel('empty');
                    showError(data.error || 'No se pudo cargar la estructura del proyecto.');
                    return;
                }
                S.generatedProposal = normalizeDraft(data.data, 'project_edit');
                S.generatedType     = 'project_edit';
                S.pendingId         = data.chat_id;

                // Show loaded counts in sidebar
                const info = document.getElementById('project-loaded-info');
                const cnt  = data.counts || {};
                if (info) {
                    document.getElementById('project-loaded-counts').textContent =
                        `${cnt.requerimientos || 0} reqs · ${cnt.tareas || 0} tareas · ${cnt.sprints || 0} sprints · ${cnt.insumos || 0} insumos`;
                    info.classList.remove('hidden');
                }

                renderEditableProposal({ proyecto_nombre: data.proyecto_nombre, counts: data.counts });
            } catch (e) {
                showPanel('empty');
                showError('No se pudo cargar la estructura del proyecto. Intenta nuevamente.');
            }
        }

        // ── Submit ────────────────────────────────────────────────────────────
        submitBtn.addEventListener('click', function () {
            const prompt  = promptInput.value.trim();
            const modelo  = document.getElementById('model-select').value.trim();

            if (prompt.length < 10) { showError(_ai.error_prompt_min); return; }
            if (!modelo)             { showError(_ai.error_no_model); return; }
            if (S.mode === 'mejorar' && !S.proyectoId) {
                document.getElementById('project-hint')?.classList.remove('hidden');
                showError(_ai.error_no_project);
                return;
            }
            if (S.generatedProposal && !confirm('Si generas una propuesta nueva, se reemplazara el borrador actual.')) {
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
                    showError(data.error || _ai.error_processing, data.raw || null);
                    return;
                }
                // Clear parent context after successful submission (context was used)
                S.parentChatId = null;
                updateContextBadge();

                S.pendingId = data.chat_id;
                if (data.type === 'create_proposal')  renderCreateProposal(data);
                else                                   renderImproveProposal(data);

                if (S.historyOpen) loadHistory();
                loadCreditBalance();
            })
            .catch(() => { setLoading(false); showError(_ai.error_connection); });
        });

        // ── Confirm ───────────────────────────────────────────────────────────
        document.getElementById('btn-confirm').addEventListener('click', function () {
            if (!S.pendingId) return;
            const draftError = validateDraftBeforeSave();
            if (draftError) { showError(draftError); return; }
            this.disabled = true;
            this.textContent = 'Guardando proyecto...';

            fetch('{{ route("asistente-ia.apply") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ chat_id: S.pendingId, action: 'confirm', data: S.generatedProposal }),
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('btn-confirm').disabled = false;
                document.getElementById('btn-confirm').innerHTML = '<span class="material-symbols-outlined" style="font-size:17px">check</span> ' + _ai.btn_confirm;
                if (!data.ok) { showError(data.error || _ai.error_processing); return; }
                showSuccess(data);
                S.pendingId = null;
                if (S.historyOpen) loadHistory();
            })
            .catch(() => {
                document.getElementById('btn-confirm').disabled = false;
                document.getElementById('btn-confirm').innerHTML = '<span class="material-symbols-outlined" style="font-size:17px">check</span> ' + _ai.btn_confirm;
                showError(_ai.error_connection);
            });
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
            S.generatedProposal = null;
            S.generatedType = null;
            document.getElementById('draft-tools')?.classList.add('hidden');
            document.getElementById('project-loaded-info')?.classList.add('hidden');
            if (S.parentChatId) showPanel('context');
            else showPanel('empty');
            if (S.historyOpen) loadHistory();
        });

        // ── Edit instruction ──────────────────────────────────────────────────
        document.getElementById('btn-edit').addEventListener('click', function () {
            showPanel('proposal');
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
            document.getElementById('btn-label').textContent = on ? _ai.btn_generating
                : (S.mode === 'crear' ? _ai.btn_gen_create : _ai.btn_gen_improve);
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

        // ── Créditos IA ───────────────────────────────────────────────────────
        const CREDIT_COSTS_MODE = { crear: 10, mejorar: 8 };

        async function loadCreditBalance() {
            if (!_ai.routes.balance) return;
            try {
                const r = await fetch(_ai.routes.balance, { headers: { 'X-CSRF-TOKEN': csrf } });
                if (!r.ok) return;
                const data = await r.json();
                if (data.ok) updateCreditWidget(data);
            } catch (e) {}
        }

        function updateCreditWidget(d) {
            const avail = d.credits_available ?? 0;
            const used  = d.credits_used      ?? 0;
            const total = d.credits_total      ?? 0;
            const pct   = total > 0 ? Math.min(100, Math.round((used / total) * 100)) : 0;
            const today = d.used_today         ?? 0;
            const ends  = d.period_ends_at     ?? '—';

            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('credit-available', avail);
            set('credit-ratio',     used + ' / ' + total);
            set('credit-plan',      d.plan_name ?? '—');
            set('credit-today',     today);
            set('credit-ends',      ends);

            const bar = document.getElementById('credit-bar');
            if (bar) {
                bar.style.width      = pct + '%';
                bar.style.background = avail < 10
                    ? 'linear-gradient(90deg,#f59e0b,#ef4444)'
                    : 'linear-gradient(90deg,#6366f1,#8b5cf6)';
            }

            // Color: warn if available credits < current action cost
            const availEl = document.getElementById('credit-available');
            const cost    = CREDIT_COSTS_MODE[S.mode] ?? 10;
            if (availEl) {
                availEl.style.color = avail < cost ? '#fbbf24' : '#ffffff';
            }
            updateActionCostStyle(avail);
        }

        function updateActionCost(mode) {
            const cost = CREDIT_COSTS_MODE[mode] ?? 10;
            const el   = document.getElementById('action-cost');
            if (el) el.textContent = cost;
        }

        function updateActionCostStyle(avail) {
            const el   = document.getElementById('action-cost');
            const cost = CREDIT_COSTS_MODE[S.mode] ?? 10;
            if (el) el.style.color = avail < cost ? '#f87171' : '#818cf8';
        }

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
            document.getElementById('prop-type-badge').textContent = _ai.new_project_badge;
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
            document.getElementById('prop-type-badge').textContent = _ai.improve_badge + ' «' + esc(res.proyecto_nombre) + '»';
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
            return html || `<p style="font-size:12px;color:#475569;padding:8px 0">${_ai.no_concrete}</p>`;
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
            return html || `<p style="font-size:12px;color:#475569;padding:8px 0">${_ai.no_concrete_improve}</p>`;
        }

        function normalizeDraft(data, type) {
            const d = JSON.parse(JSON.stringify(data || {}));
            d._tipo = d._tipo || type || 'create';

            if (d.project && typeof d.project === 'object') {
                d.nombre = d.nombre || d.project.nombre || d.project.name || '';
                d.descripcion = d.descripcion || d.project.descripcion || d.project.description || '';
            }

            if (d._tipo === 'improve') {
                ['requerimientos_nuevos', 'tareas_nuevas', 'actualizaciones_tareas', 'sprints_nuevos', 'insumos_nuevos']
                    .forEach(k => d[k] = Array.isArray(d[k]) ? d[k] : []);
                return d;
            }

            d.nombre = d.nombre || '';
            d.descripcion = d.descripcion || '';
            ['requerimientos', 'tareas', 'sprints', 'insumos']
                .forEach(k => d[k] = Array.isArray(d[k]) ? d[k] : []);

            return d;
        }

        function renderCreateProposal(res) {
            S.generatedType = 'create';
            S.generatedProposal = normalizeDraft(res.data, 'create');
            renderEditableProposal(res);
        }

        function renderImproveProposal(res) {
            S.generatedType = 'improve';
            S.generatedProposal = normalizeDraft(res.data, 'improve');
            renderEditableProposal(res);
        }

        const sectionMeta = {
            requerimientos: { icon: 'assignment', color: '#a5b4fc', title: 'Requerimientos', add: 'Agregar requerimiento', empty: 'Sin requerimientos.' },
            tareas: { icon: 'check_box', color: '#6ee7b7', title: 'Tareas', add: 'Agregar tarea', empty: 'Sin tareas.' },
            sprints: { icon: 'sprint', color: '#fbbf24', title: 'Sprints', add: 'Agregar sprint', empty: 'Sin sprints.' },
            insumos: { icon: 'inventory_2', color: '#fb923c', title: 'Insumos', add: 'Agregar insumo', empty: 'Sin insumos.' },
            requerimientos_nuevos: { icon: 'assignment', color: '#a5b4fc', title: 'Requerimientos a agregar', add: 'Agregar requerimiento', empty: 'Sin requerimientos.' },
            tareas_nuevas: { icon: 'check_box', color: '#6ee7b7', title: 'Tareas a agregar', add: 'Agregar tarea', empty: 'Sin tareas.' },
            actualizaciones_tareas: { icon: 'edit_note', color: '#fcd34d', title: 'Tareas a actualizar', add: 'Agregar actualizacion', empty: 'Sin actualizaciones.' },
            sprints_nuevos: { icon: 'sprint', color: '#fbbf24', title: 'Sprints a agregar', add: 'Agregar sprint', empty: 'Sin sprints.' },
            insumos_nuevos: { icon: 'inventory_2', color: '#fb923c', title: 'Insumos a agregar', add: 'Agregar insumo', empty: 'Sin insumos.' },
        };

        function attr(s) {
            return esc(s).replace(/"/g, '&quot;');
        }

        function sectionsForCurrentDraft() {
            return S.generatedProposal?._tipo === 'improve'
                ? ['requerimientos_nuevos', 'tareas_nuevas', 'actualizaciones_tareas', 'sprints_nuevos', 'insumos_nuevos']
                : ['requerimientos', 'tareas', 'sprints', 'insumos'];
        }

        function renderEditableProposal(res = {}) {
            const d = S.generatedProposal || {};

            showPanel('proposal');
            document.getElementById('draft-tools')?.classList.remove('hidden');
            document.getElementById('prop-warnings').classList.add('hidden');
            document.getElementById('success-box').classList.add('hidden');
            document.getElementById('btn-confirm').style.display = '';
            document.getElementById('btn-cancel').style.display  = '';

            if (d._tipo === 'improve') {
                const projectName = res.proyecto_nombre ? ' — ' + res.proyecto_nombre : '';
                document.getElementById('prop-type-badge').textContent = _ai.improve_badge + projectName;
                document.getElementById('prop-nombre').textContent = 'Borrador de mejora';
                document.getElementById('prop-descripcion').textContent = 'Edita las secciones propuestas antes de aplicar los cambios.';
                document.getElementById('prop-resumen-wrap').classList.remove('hidden');
                document.getElementById('prop-resumen').textContent = d.resumen || 'Resumen de cambios propuestos.';

            } else if (d._tipo === 'project_edit') {
                const projectName = res.proyecto_nombre || d.nombre || 'Proyecto';
                const cnt = {
                    requerimientos: (d.requerimientos || []).length,
                    tareas:         (d.tareas || []).length,
                    sprints:        (d.sprints || []).length,
                    insumos:        (d.insumos || []).length,
                };
                document.getElementById('prop-type-badge').textContent = '✏️ Editando: ' + projectName;
                document.getElementById('prop-resumen-wrap').classList.remove('hidden');
                document.getElementById('prop-resumen').textContent =
                    `${cnt.requerimientos} requerimientos · ${cnt.tareas} tareas · ${cnt.sprints} sprints · ${cnt.insumos} insumos — Edita o pide ajustes con IA antes de guardar.`;
                document.getElementById('prop-nombre').innerHTML =
                    `<span contenteditable="true" class="block rounded-lg border border-transparent px-2 py-1 outline-none hover:border-indigo-500/30 focus:border-indigo-400/60" oninput="updateProjectField('nombre', this.textContent)">${esc(d.nombre || projectName)}</span>`;
                document.getElementById('prop-descripcion').innerHTML =
                    `<span contenteditable="true" class="block min-h-[44px] rounded-lg border border-transparent px-2 py-1 outline-none hover:border-indigo-500/30 focus:border-indigo-400/60" oninput="updateProjectField('descripcion', this.textContent)">${esc(d.descripcion || '')}</span>`;

            } else {
                document.getElementById('prop-type-badge').textContent = _ai.new_project_badge;
                document.getElementById('prop-resumen-wrap').classList.add('hidden');
                document.getElementById('prop-nombre').innerHTML =
                    `<span contenteditable="true" class="block rounded-lg border border-transparent px-2 py-1 outline-none hover:border-emerald-500/30 focus:border-emerald-400/60" oninput="updateProjectField('nombre', this.textContent)">${esc(d.nombre || 'Proyecto sin nombre')}</span>`;
                document.getElementById('prop-descripcion').innerHTML =
                    `<span contenteditable="true" class="block min-h-[44px] rounded-lg border border-transparent px-2 py-1 outline-none hover:border-emerald-500/30 focus:border-emerald-400/60" oninput="updateProjectField('descripcion', this.textContent)">${esc(d.descripcion || 'Describe el proyecto...')}</span>`;
            }

            document.getElementById('prop-sections').innerHTML = sectionsForCurrentDraft()
                .map(renderEditableSection)
                .join('');
        }

        window.updateProjectField = function (field, value) {
            if (!S.generatedProposal) return;
            S.generatedProposal[field] = String(value || '').trim();
        };

        function renderEditableSection(section) {
            const meta = sectionMeta[section];
            const items = Array.isArray(S.generatedProposal?.[section]) ? S.generatedProposal[section] : [];
            const body = items.length
                ? items.map((item, index) => renderEditableItem(section, item, index)).join('')
                : `<div class="px-3 py-4 text-xs text-slate-500">${meta.empty}</div>`;

            return `<div class="prop-section">
                <div class="prop-section-hdr justify-between">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="material-symbols-outlined" style="font-size:15px;color:${meta.color}">${meta.icon}</span>
                        <span style="font-size:11px;font-weight:700;color:${meta.color}">${meta.title}</span>
                        <span style="font-size:10px;color:#64748b">(${items.length})</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="draft-mini-btn" onclick="openDraftItemModal('${section}', null, 'add')">
                            <span class="material-symbols-outlined" style="font-size:13px">add</span>${meta.add}
                        </button>
                        <button type="button" class="draft-mini-btn" onclick="regenerateDraftSection('${section}')">
                            <span class="material-symbols-outlined" style="font-size:13px">autorenew</span>Regenerar
                        </button>
                    </div>
                </div>
                <div class="prop-section-body space-y-2">${body}</div>
            </div>`;
        }

        function renderEditableItem(section, item, index) {
            return `<div class="rounded-xl border border-white/5 bg-slate-950/40 p-3 hover:border-indigo-500/20 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        ${itemSummaryHtml(section, item)}
                        <p class="mt-1 text-sm font-semibold text-slate-100">${esc(itemTitle(section, item))}</p>
                        ${itemDescription(section, item) ? `<p class="mt-1 text-xs leading-relaxed text-slate-500">${esc(itemDescription(section, item))}</p>` : ''}
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" class="draft-icon-btn" title="Editar" onclick="openDraftItemModal('${section}', ${index}, 'edit')">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button type="button" class="draft-icon-btn" title="Duplicar" onclick="duplicateDraftItem('${section}', ${index})">
                            <span class="material-symbols-outlined">content_copy</span>
                        </button>
                        <button type="button" class="draft-icon-btn" title="Regenerar con IA" onclick="regenerateDraftItem('${section}', ${index})">
                            <span class="material-symbols-outlined">auto_awesome</span>
                        </button>
                        <button type="button" class="draft-icon-btn danger" title="Eliminar" onclick="deleteDraftItem('${section}', ${index})">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </div>
            </div>`;
        }

        function itemSummaryHtml(section, item) {
            if (section.includes('requerimientos')) {
                return `<div class="flex items-center gap-1.5 flex-wrap">
                    <span class="bx ${item.tipo === 'no_funcional' ? 'b-nofunc' : 'b-func'}">${esc(item.tipo || 'funcional')}</span>
                    <span class="${prioClass(item.prioridad)}">${esc(item.prioridad || 'media')}</span>
                </div>`;
            }
            if (section === 'tareas' || section === 'tareas_nuevas') {
                return `<div class="flex items-center gap-1.5 flex-wrap">
                    <span class="${prioClass(item.prioridad)}">${esc(item.prioridad || 'media')}</span>
                    ${item.estado ? `<span class="bx b-func">${esc(item.estado)}</span>` : ''}
                </div>`;
            }
            if (section.includes('sprints')) {
                const weeks = item.semanas || item.duracion_semanas;
                return weeks ? `<span class="text-[10px] font-semibold text-indigo-300">${esc(weeks)} semanas</span>` : '';
            }
            if (section.includes('insumos')) {
                return item.tipo ? `<span class="text-[10px] font-semibold text-slate-400">[${esc(item.tipo)}]</span>` : '';
            }
            if (section === 'actualizaciones_tareas') {
                return '<span class="bx b-upd">actualizar</span>';
            }
            return '';
        }

        function itemTitle(section, item) {
            if (section === 'actualizaciones_tareas') return item.titulo_nuevo || item.titulo_actual || 'Actualizacion de tarea';
            if (section.includes('sprints')) return item.nombre || item.titulo || 'Sprint sin nombre';
            return item.titulo || item.nombre || 'Elemento sin titulo';
        }

        function itemDescription(section, item) {
            if (section === 'actualizaciones_tareas') return item.descripcion_nueva || item.descripcion || '';
            if (section.includes('sprints')) return item.objetivo || item.descripcion || '';
            if (section.includes('insumos')) return item.contenido || item.descripcion || '';
            return item.descripcion || '';
        }

        function validateDraftBeforeSave() {
            if (!S.generatedProposal) return 'No hay un borrador editable para guardar.';

            if (S.generatedProposal._tipo === 'improve') {
                const hasChanges = sectionsForCurrentDraft().some(section => (S.generatedProposal[section] || []).length > 0);
                return hasChanges ? null : 'Agrega al menos un cambio antes de aplicar la mejora.';
            }

            if (S.generatedProposal._tipo === 'project_edit') {
                if (!S.generatedProposal._proyecto_id) return 'No se ha cargado un proyecto para editar.';
                if (!S.pendingId) return 'El borrador no tiene un registro de sesion valido. Recarga la estructura.';
                return null;
            }

            if (!String(S.generatedProposal.nombre || '').trim()) return 'El borrador necesita nombre del proyecto.';
            if (!String(S.generatedProposal.descripcion || '').trim()) return 'El borrador necesita descripcion del proyecto.';
            if (!(S.generatedProposal.requerimientos || []).length) return 'El borrador necesita al menos 1 requerimiento.';
            if (!(S.generatedProposal.tareas || []).length) return 'El borrador necesita al menos 1 tarea.';

            return null;
        }

        function setDraftStatus(message) {
            const el = document.getElementById('draft-status');
            if (!el) return;
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        }

        async function postDraftAction(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({ ok: false, error: _ai.error_connection }));
            if (!response.ok || !data.ok) throw data;
            return data;
        }

        async function refineCurrentDraft(instruction, statusText) {
            if (!S.generatedProposal) return;
            setDraftStatus(statusText || 'Ajustando borrador...');
            hideError();
            try {
                const data = await postDraftAction(_ai.routes.refineProposal, {
                    proposal: S.generatedProposal,
                    instruction: instruction,
                    model: document.getElementById('model-select').value.trim(),
                });
                S.generatedProposal = normalizeDraft(data.data, S.generatedProposal._tipo);
                renderEditableProposal(data);
                loadCreditBalance();
            } catch (e) {
                showError(
                    (e.error || 'No se pudo ajustar el borrador.') + ' La propuesta actual se conserva.',
                    e.raw || null
                );
            } finally {
                setDraftStatus('');
            }
        }

        document.getElementById('btn-refine-draft')?.addEventListener('click', async function () {
            const btn = this;
            const input = document.getElementById('draft-adjust-input');
            const instruction = input.value.trim();
            if (instruction.length < 5) {
                showError('Escribe una instruccion de ajuste mas concreta (minimo 5 caracteres).');
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin" style="font-size:15px">refresh</span> Ajustando borrador...';
            await refineCurrentDraft(instruction, 'Ajustando borrador...');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:15px">auto_fix_high</span> Pedir ajuste a la IA';
        });

        document.getElementById('btn-regenerate-draft')?.addEventListener('click', function () {
            if (!S.generatedProposal) return;
            if (!confirm('Se reemplazara el borrador actual con una nueva version.')) return;
            const prompt = promptInput.value.trim();
            const instruction = prompt
                ? 'Regenera la propuesta completa usando esta instruccion como referencia: ' + prompt
                : 'Regenera la propuesta completa manteniendo el mismo alcance general.';
            refineCurrentDraft(instruction, 'Regenerando propuesta...');
        });

        window.regenerateDraftSection = async function (section) {
            if (!S.generatedProposal) return;
            setDraftStatus('Regenerando ' + (sectionMeta[section]?.title || section) + '...');
            hideError();
            try {
                const data = await postDraftAction(_ai.routes.regenerateSection, {
                    proposal: S.generatedProposal,
                    section: section,
                    model: document.getElementById('model-select').value.trim(),
                });
                S.generatedProposal[section] = Array.isArray(data.items) ? data.items : [];
                renderEditableProposal(data);
                loadCreditBalance();
            } catch (e) {
                showError(e.error || 'No se pudo regenerar la seccion.', e.raw || null);
            } finally {
                setDraftStatus('');
            }
        };

        window.regenerateDraftItem = async function (section, index) {
            if (!S.generatedProposal?.[section]?.[index]) return;
            setDraftStatus('Regenerando elemento...');
            hideError();
            try {
                const data = await postDraftAction(_ai.routes.regenerateItem, {
                    proposal: S.generatedProposal,
                    section: section,
                    item: S.generatedProposal[section][index],
                    model: document.getElementById('model-select').value.trim(),
                });
                S.generatedProposal[section][index] = data.item || S.generatedProposal[section][index];
                renderEditableProposal(data);
                loadCreditBalance();
            } catch (e) {
                showError(e.error || 'No se pudo regenerar el elemento.', e.raw || null);
            } finally {
                setDraftStatus('');
            }
        };

        window.deleteDraftItem = function (section, index) {
            if (!S.generatedProposal?.[section]?.[index]) return;
            if (!confirm('Eliminar este elemento del borrador?')) return;
            S.generatedProposal[section].splice(index, 1);
            renderEditableProposal();
        };

        window.duplicateDraftItem = function (section, index) {
            if (!S.generatedProposal?.[section]?.[index]) return;
            const copy = JSON.parse(JSON.stringify(S.generatedProposal[section][index]));
            if (copy.titulo) copy.titulo += ' copia';
            else if (copy.nombre) copy.nombre += ' copia';
            S.generatedProposal[section].splice(index + 1, 0, copy);
            renderEditableProposal();
        };

        window.openDraftItemModal = function (section, index = null, mode = 'edit') {
            const item = index === null ? defaultItem(section) : (S.generatedProposal?.[section]?.[index] || defaultItem(section));
            S.modal = { section, index, mode };
            document.getElementById('draft-modal-title').textContent = mode === 'add'
                ? (sectionMeta[section]?.add || 'Agregar elemento')
                : 'Editar ' + (sectionMeta[section]?.title || 'elemento');
            document.getElementById('draft-modal-fields').innerHTML = modalFields(section, item);
            const modal = document.getElementById('draft-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeDraftModal = function () {
            const modal = document.getElementById('draft-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            S.modal = { section: null, index: null, mode: 'edit' };
        };

        window.saveDraftModal = function () {
            const { section, index, mode } = S.modal;
            if (!section || !S.generatedProposal) return;
            const item = readDraftItem(section, mode === 'edit' && index !== null ? S.generatedProposal[section][index] : {});
            if (!itemTitle(section, item).trim()) {
                showError('El elemento necesita un titulo o nombre.');
                return;
            }
            S.generatedProposal[section] = Array.isArray(S.generatedProposal[section]) ? S.generatedProposal[section] : [];
            if (mode === 'add' || index === null) S.generatedProposal[section].push(item);
            else S.generatedProposal[section][index] = item;
            closeDraftModal();
            renderEditableProposal();
        };

        function defaultItem(section) {
            if (section.includes('requerimientos')) return { titulo: '', descripcion: '', tipo: 'funcional', prioridad: 'media' };
            if (section === 'tareas' || section === 'tareas_nuevas') return { titulo: '', descripcion: '', prioridad: 'media', estado: 'pendiente' };
            if (section.includes('sprints')) return { nombre: '', objetivo: '', semanas: 1 };
            if (section.includes('insumos')) return { titulo: '', tipo: 'software', contenido: '' };
            if (section === 'actualizaciones_tareas') return { titulo_actual: '', titulo_nuevo: '', descripcion_nueva: '' };
            return { titulo: '', descripcion: '' };
        }

        function modalFields(section, item) {
            if (section.includes('requerimientos')) {
                return [
                    modalInput('titulo', 'Titulo', item.titulo || item.nombre || ''),
                    modalTextarea('descripcion', 'Descripcion', item.descripcion || ''),
                    modalSelect('tipo', 'Tipo', item.tipo || 'funcional', [['funcional', 'Funcional'], ['no_funcional', 'No funcional']]),
                    modalSelect('prioridad', 'Prioridad', item.prioridad || 'media', [['baja', 'Baja'], ['media', 'Media'], ['alta', 'Alta']]),
                ].join('');
            }
            if (section === 'tareas' || section === 'tareas_nuevas') {
                return [
                    modalInput('titulo', 'Titulo', item.titulo || item.nombre || ''),
                    modalTextarea('descripcion', 'Descripcion', item.descripcion || ''),
                    modalSelect('prioridad', 'Prioridad', item.prioridad || 'media', [['baja', 'Baja'], ['media', 'Media'], ['alta', 'Alta']]),
                    modalSelect('estado', 'Estado', item.estado || 'pendiente', [['pendiente', 'Pendiente'], ['en_progreso', 'En progreso'], ['en_revision', 'En revision'], ['completado', 'Completado']]),
                ].join('');
            }
            if (section.includes('sprints')) {
                return [
                    modalInput('nombre', 'Nombre', item.nombre || item.titulo || ''),
                    modalTextarea('objetivo', 'Objetivo', item.objetivo || item.descripcion || ''),
                    modalInput('semanas', 'Duracion en semanas', item.semanas || item.duracion_semanas || 1, 'number', 'min="1"'),
                ].join('');
            }
            if (section.includes('insumos')) {
                return [
                    modalInput('titulo', 'Titulo', item.titulo || item.nombre || ''),
                    modalInput('tipo', 'Tipo', item.tipo || 'software'),
                    modalTextarea('contenido', 'Contenido o descripcion', item.contenido || item.descripcion || ''),
                ].join('');
            }
            if (section === 'actualizaciones_tareas') {
                return [
                    modalInput('titulo_actual', 'Titulo actual', item.titulo_actual || ''),
                    modalInput('titulo_nuevo', 'Titulo nuevo', item.titulo_nuevo || item.titulo || ''),
                    modalTextarea('descripcion_nueva', 'Descripcion nueva', item.descripcion_nueva || item.descripcion || ''),
                ].join('');
            }
            return [
                modalInput('titulo', 'Titulo', item.titulo || item.nombre || ''),
                modalTextarea('descripcion', 'Descripcion', item.descripcion || ''),
            ].join('');
        }

        function modalInput(name, label, value, type = 'text', extra = '') {
            return `<label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-300">${label}</span>
                <input id="draft-field-${name}" type="${type}" value="${attr(value)}" ${extra}
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">
            </label>`;
        }

        function modalTextarea(name, label, value) {
            return `<label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-300">${label}</span>
                <textarea id="draft-field-${name}" rows="4"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500">${esc(value)}</textarea>
            </label>`;
        }

        function modalSelect(name, label, value, options) {
            return `<label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-300">${label}</span>
                <select id="draft-field-${name}"
                        class="dark-form-select w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                    ${options.map(([v, t]) => `<option value="${attr(v)}" ${String(value) === String(v) ? 'selected' : ''}>${esc(t)}</option>`).join('')}
                </select>
            </label>`;
        }

        function draftField(name) {
            return document.getElementById('draft-field-' + name)?.value.trim() || '';
        }

        function readDraftItem(section, current = {}) {
            const base = { ...current };
            if (section.includes('requerimientos')) {
                return { ...base, titulo: draftField('titulo'), descripcion: draftField('descripcion'), tipo: draftField('tipo') || 'funcional', prioridad: draftField('prioridad') || 'media' };
            }
            if (section === 'tareas' || section === 'tareas_nuevas') {
                return { ...base, titulo: draftField('titulo'), descripcion: draftField('descripcion'), prioridad: draftField('prioridad') || 'media', estado: draftField('estado') || 'pendiente' };
            }
            if (section.includes('sprints')) {
                return { ...base, nombre: draftField('nombre'), objetivo: draftField('objetivo'), semanas: Math.max(1, parseInt(draftField('semanas') || '1', 10)) };
            }
            if (section.includes('insumos')) {
                return { ...base, titulo: draftField('titulo'), tipo: draftField('tipo') || 'software', contenido: draftField('contenido') };
            }
            if (section === 'actualizaciones_tareas') {
                return { ...base, titulo_actual: draftField('titulo_actual'), titulo_nuevo: draftField('titulo_nuevo'), descripcion_nueva: draftField('descripcion_nueva') };
            }
            return { ...base, titulo: draftField('titulo'), descripcion: draftField('descripcion') };
        }

        function showSuccess(data) {
            document.getElementById('btn-confirm').style.display = 'none';
            document.getElementById('btn-cancel').style.display  = 'none';
            document.getElementById('draft-tools')?.classList.add('hidden');
            document.querySelectorAll('#prop-sections button').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-40', 'pointer-events-none');
            });
            S.generatedProposal = null;
            S.generatedType = null;
            const sb = document.getElementById('success-box');
            sb.classList.remove('hidden');
            document.getElementById('success-msg').textContent = data.message || _ai.changes_saved;
            const c = data.counts || {};
            let html = '';
            if (c.requerimientos)    html += `<p>📋 ${c.requerimientos} ${_ai.count_reqs}</p>`;
            if (c.tareas)            html += `<p>✅ ${c.tareas} ${_ai.count_tasks}</p>`;
            if (c.reqs_new)          html += `<p>📋 ${c.reqs_new} ${_ai.count_reqs_new}</p>`;
            if (c.reqs_updated)      html += `<p>📋 ${c.reqs_updated} requerimientos actualizados</p>`;
            if (c.tasks_new)         html += `<p>✅ ${c.tasks_new} ${_ai.count_tasks_new}</p>`;
            if (c.tasks_updated)     html += `<p>🔄 ${c.tasks_updated} ${_ai.count_tasks_upd}</p>`;
            if (c.sprints_new)       html += `<p>🏃 ${c.sprints_new} sprints nuevos</p>`;
            if (c.sprints_updated)   html += `<p>🏃 ${c.sprints_updated} sprints actualizados</p>`;
            if (c.sprints)           html += `<p>🏃 ${c.sprints} ${_ai.count_sprints}</p>`;
            if (c.insumos_new)       html += `<p>📦 ${c.insumos_new} insumos nuevos</p>`;
            if (c.insumos_updated)   html += `<p>📦 ${c.insumos_updated} insumos actualizados</p>`;
            if (c.insumos)           html += `<p>📦 ${c.insumos} ${_ai.count_inputs}</p>`;
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
            list.innerHTML = `<div class="text-center py-4"><span style="font-size:11px;color:#374151">${_ai.history_loading}</span></div>`;

            fetch('{{ route("asistente-ia.historial") }}', { headers: { 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json())
            .then(data => {
                S.allItems = data.history || [];
                filterHistoryItems();
            })
            .catch(() => {
                document.getElementById('history-list').innerHTML = `<p style="font-size:11px;color:#ef4444;text-align:center;padding:12px">${_ai.history_error}</p>`;
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
                list.innerHTML = `<p style="font-size:11px;color:#374151;text-align:center;padding:16px">${_ai.history_empty}</p>`;
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
                            ${h.credits_cost ? `<span>·</span><span style="color:#6366f1;font-weight:600">${h.credits_cost} créd.</span>` : ''}
                            ${h.proyecto_nombre ? `<span>·</span><a href="${esc(h.proyecto_url)}" style="color:#818cf8" onclick="event.stopPropagation()">${esc(h.proyecto_nombre)}</a>` : ''}
                        </div>
                        <p style="font-size:11px;color:#cbd5e1;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px"
                           title="${esc(h.prompt_completo)}">${esc(h.prompt_usuario)}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 mt-0.5">
                        <button onclick="viewHistoryItem(${h.id})"
                                style="font-size:10px;color:#6366f1" class="hover:underline hover:text-indigo-400">
                            ${_ai.btn_view}
                        </button>
                        <button onclick="reuseHistoryItem(${h.id})"
                                style="font-size:10px;color:#a855f7" class="hover:underline hover:text-violet-400">
                            ${_ai.btn_reuse_short}
                        </button>
                        ${h.estado === 'borrador' ? `
                        <button onclick="applyHistoryItem(${h.id})"
                                style="font-size:10px;color:#10b981" class="hover:underline hover:text-emerald-400">
                            ${_ai.btn_apply_short}
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
            if (!data.ok) { alert(_ai.error_load); return; }

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
            if (!data.ok) { alert(_ai.error_load); return; }

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
                : _ai.ctx_no_project;
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

            // 6. If the record has a full structured proposal, load it as editable draft
            if (dd && dd._tipo && (dd.requerimientos || dd.tareas || dd.requerimientos_nuevos || dd.tareas_nuevas)) {
                S.generatedProposal = normalizeDraft(dd, dd._tipo);
                S.generatedType     = dd._tipo;
                S.pendingId         = rec.estado === 'borrador' ? rec.id : null;
                renderEditableProposal({ proyecto_nombre: rec.proyecto_nombre });
            } else {
                showPanel('context');
            }
            promptInput.value = '';
            promptInput.dispatchEvent(new Event('input'));

            // 7. Refresh history list to highlight active item
            if (S.historyOpen) filterHistoryItems();
        };

        // ── APPLY a borrador record from history ──────────────────────────────
        window.applyHistoryItem = async function (id) {
            if (!confirm(_ai.confirm_apply)) return;
            const r = await fetch('{{ route("asistente-ia.apply") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ chat_id: id, action: 'confirm' }),
            });
            const data = await r.json();
            if (data.ok) {
                alert(data.message || _ai.applied_ok);
                loadHistory();
            } else {
                alert(data.error || _ai.applied_err);
            }
        };

        // ── DELETE a history record ────────────────────────────────────────────
        window.deleteHistoryItem = async function (id) {
            if (!confirm(_ai.confirm_delete)) return;
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
                alert(_ai.error_delete);
            }
        };

        window.clearHistory = async function (scope) {
            const confirmMsg = scope === 'project' && S.proyectoId ? _ai.confirm_clear_proj : _ai.confirm_clear_all;
            if (!confirm(confirmMsg)) return;
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

        // ── Init: load credit balance on page ready ───────────────────────────
        loadCreditBalance();
        updateActionCost(S.mode);

    })();
    </script>
    @endpush

</x-app-layout>
