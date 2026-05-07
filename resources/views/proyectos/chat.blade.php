<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary-container" style="font-size: 22px;">smart_toy</span>
            <span class="text-sm font-semibold text-white">Asistente IA</span>
            <span class="text-gray-600 text-sm">·</span>
            <span class="text-xs text-gray-500">Crear y mejorar proyectos con inteligencia artificial</span>
        </div>
    </x-slot>

    {{-- ═══════════════════════════════════════════════════════════
         HISTORY SIDE PANEL
         ═══════════════════════════════════════════════════════════ --}}
    <div id="history-panel"
         class="fixed top-0 right-0 h-full w-[340px] z-50 flex flex-col
                bg-[#0b0d14] border-l border-white/8 shadow-2xl
                transform translate-x-full transition-transform duration-300 ease-in-out">

        {{-- Panel header --}}
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-white/8 flex-shrink-0 bg-white/2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-violet-400" style="font-size:18px;font-variation-settings:'FILL' 1">history</span>
                <span class="text-sm font-bold text-white">Historial IA</span>
                <span id="history-count-badge"
                      class="hidden text-[10px] font-bold bg-violet-500/20 text-violet-300 border border-violet-500/30 px-1.5 py-0.5 rounded-full"></span>
            </div>
            <div class="flex items-center gap-1">
                <button id="btn-clear-history"
                        class="text-[10px] text-red-400/70 hover:text-red-400 px-2 py-1 rounded-lg
                               hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition-all">
                    Limpiar
                </button>
                <button id="btn-close-history"
                        class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5 transition-colors ml-1">
                    <span class="material-symbols-outlined" style="font-size:18px">close</span>
                </button>
            </div>
        </div>

        {{-- Filter tabs --}}
        <div id="history-filters" class="flex items-center gap-0.5 px-3 py-2 border-b border-white/5 flex-shrink-0">
            <button class="history-filter-btn active" data-filter="all">Todos</button>
            <button class="history-filter-btn" id="filter-project-btn" data-filter="project">
                Este proyecto
            </button>
        </div>

        {{-- History list --}}
        <div id="history-list"
             class="flex-1 overflow-y-auto"
             style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.07) transparent;">
            <div class="flex items-center justify-center h-32">
                <div class="flex gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:120ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:240ms"></span>
                </div>
            </div>
        </div>

    </div>
    <div id="history-backdrop"
         class="hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
         onclick="closeHistory()"></div>

    {{-- ═══════════════════════════════════════════════════════════
         MAIN LAYOUT
         ═══════════════════════════════════════════════════════════ --}}
    <div class="p-3 sm:p-6 max-w-[960px] mx-auto h-[calc(100vh-96px)] flex flex-col">
        <div class="flex-1 flex flex-col min-h-0 rounded-2xl border border-white/6 overflow-hidden shadow-2xl"
             style="background: rgba(11,13,20,0.95);">

            {{-- ── TOP BAR: title + history button ── --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-white/6 flex-shrink-0"
                 style="background: rgba(255,255,255,0.02);">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/15 border border-indigo-500/25
                                flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-indigo-400" style="font-size:16px;font-variation-settings:'FILL' 1">auto_awesome</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white leading-none">Asistente de Proyectos</p>
                        <p class="text-[10px] text-gray-600 mt-0.5">Powered by Ollama</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button id="btn-open-history"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                   text-gray-400 hover:text-violet-300 hover:bg-violet-500/10
                                   border border-transparent hover:border-violet-500/20 transition-all">
                        <span class="material-symbols-outlined" style="font-size:15px">history</span>
                        Historial
                    </button>
                    <button onclick="clearChat()"
                            class="p-1.5 rounded-lg text-gray-600 hover:text-gray-300 hover:bg-white/5 transition-colors"
                            title="Limpiar conversación visible">
                        <span class="material-symbols-outlined" style="font-size:16px">delete_sweep</span>
                    </button>
                </div>
            </div>

            {{-- ── PROJECT SELECTOR BAR ── --}}
            <div class="px-5 py-3 border-b border-white/5 flex-shrink-0 flex items-center gap-3"
                 style="background: rgba(255,255,255,0.015);">

                <span class="material-symbols-outlined text-gray-600 flex-shrink-0" style="font-size:16px">folder_open</span>

                {{-- Custom dropdown --}}
                <div class="relative flex-1" id="project-selector-wrapper">
                    <button id="project-selector-btn"
                            class="w-full flex items-center gap-2 text-left text-sm rounded-lg px-3 py-1.5
                                   border border-white/8 hover:border-white/15
                                   text-gray-400 hover:text-white transition-all"
                            style="background: rgba(255,255,255,0.04);">
                        <span id="selector-label" class="flex-1 truncate">Sin proyecto seleccionado</span>
                        <span class="material-symbols-outlined text-gray-600 flex-shrink-0" style="font-size:16px">unfold_more</span>
                    </button>

                    <div id="project-dropdown"
                         class="hidden absolute top-full left-0 right-0 mt-1.5 rounded-xl border border-white/10 shadow-2xl z-30 overflow-hidden"
                         style="background: #0d0f1a; max-height: 280px; overflow-y: auto;">

                        {{-- No project option --}}
                        <button class="proj-opt w-full text-left px-4 py-2.5 text-sm text-gray-400
                                       hover:bg-white/5 hover:text-white transition-colors flex items-center gap-2.5"
                                data-id="" data-name="Sin proyecto seleccionado">
                            <span class="material-symbols-outlined text-gray-600" style="font-size:15px">remove_circle_outline</span>
                            <span>Sin proyecto seleccionado</span>
                        </button>

                        @if($proyectos->isNotEmpty())
                        <div class="px-4 py-1.5 text-[9px] uppercase tracking-widest text-gray-600 font-bold border-t border-white/5">
                            Mis proyectos ({{ $proyectos->count() }})
                        </div>
                        @foreach($proyectos as $p)
                        <button class="proj-opt w-full text-left px-4 py-2.5 text-sm text-white
                                       hover:bg-white/5 transition-colors flex items-center gap-2.5"
                                data-id="{{ $p->id }}"
                                data-name="{{ $p->nombre }}"
                                data-desc="{{ Str::limit($p->descripcion ?? '', 80) }}">
                            <span class="material-symbols-outlined text-indigo-400 flex-shrink-0" style="font-size:15px">folder</span>
                            <span class="flex-1 truncate">{{ $p->nombre }}</span>
                            <span class="text-[10px] text-gray-600 flex-shrink-0 capitalize">{{ $p->estado }}</span>
                        </button>
                        @endforeach
                        @else
                        <p class="px-4 py-3 text-xs text-gray-600 italic">Sin proyectos creados aún.</p>
                        @endif

                        <div class="border-t border-white/5 mt-1"></div>
                        <button class="proj-opt w-full text-left px-4 py-2.5 text-sm text-violet-300
                                       hover:bg-violet-500/10 transition-colors flex items-center gap-2.5"
                                data-id="new" data-name="Crear nuevo proyecto con IA">
                            <span class="material-symbols-outlined text-violet-400" style="font-size:15px">add_circle</span>
                            Crear nuevo proyecto con IA
                        </button>
                    </div>
                </div>

                {{-- Active project badge --}}
                <div id="active-project-badge" class="hidden flex items-center gap-1.5 flex-shrink-0">
                    <div class="flex items-center gap-1 bg-indigo-500/15 border border-indigo-500/25
                                px-2.5 py-1 rounded-lg max-w-[180px]">
                        <span class="material-symbols-outlined text-indigo-400" style="font-size:12px;font-variation-settings:'FILL' 1">radio_button_checked</span>
                        <span id="active-badge-name" class="text-[11px] font-semibold text-indigo-300 truncate"></span>
                    </div>
                    <button id="btn-clear-project"
                            class="p-1 rounded text-gray-600 hover:text-red-400 transition-colors"
                            title="Quitar proyecto activo">
                        <span class="material-symbols-outlined" style="font-size:14px">close</span>
                    </button>
                </div>
            </div>

            {{-- ── CHAT MESSAGES ── --}}
            <div id="chat-messages"
                 class="flex-1 overflow-y-auto px-5 py-4 space-y-5 flex flex-col min-h-0"
                 style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.07) transparent;">

                {{-- Welcome bubble --}}
                <div id="welcome-bubble" class="flex items-end gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30
                                flex items-center justify-center flex-shrink-0 mb-0.5">
                        <span class="material-symbols-outlined text-indigo-400" style="font-size:14px;font-variation-settings:'FILL' 1">smart_toy</span>
                    </div>
                    <div class="rounded-2xl rounded-bl-sm px-4 py-3 max-w-[82%] border border-white/6"
                         style="background: rgba(255,255,255,0.04);">
                        <p id="welcome-text" class="text-sm text-gray-300 leading-relaxed">
                            ¡Hola! Selecciona un proyecto activo para editar, o escribe una idea para crear uno nuevo.
                        </p>
                    </div>
                </div>

                {{-- Typing indicator --}}
                <div id="typing-indicator" class="hidden items-end gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30
                                flex items-center justify-center flex-shrink-0 mb-0.5">
                        <span class="material-symbols-outlined text-indigo-400" style="font-size:14px;font-variation-settings:'FILL' 1">smart_toy</span>
                    </div>
                    <div class="rounded-2xl rounded-bl-sm px-4 py-3 border border-white/6 flex items-center gap-1.5"
                         style="background: rgba(255,255,255,0.04);">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400/60 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400/60 animate-bounce" style="animation-delay:130ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400/60 animate-bounce" style="animation-delay:260ms"></span>
                    </div>
                </div>
            </div>

            {{-- ── INPUT AREA ── --}}
            <div class="flex-shrink-0 px-4 py-3 border-t border-white/6"
                 style="background: rgba(255,255,255,0.02);">
                <form id="chat-form" class="flex items-end gap-2">

                    <div class="flex-1 rounded-xl border border-white/10 overflow-hidden
                                focus-within:border-indigo-500/50 transition-colors"
                         style="background: #111827;">
                        <textarea
                            id="chat-input"
                            name="message"
                            rows="1"
                            placeholder="Describe tu proyecto o escribe qué quieres mejorar..."
                            style="
                                background: transparent;
                                color: #f1f5f9;
                                caret-color: #818cf8;
                                display: block;
                                width: 100%;
                                padding: 0.75rem 1rem;
                                font-size: 0.875rem;
                                line-height: 1.5;
                                border: none;
                                outline: none;
                                resize: none;
                                max-height: 7rem;
                                font-family: inherit;
                            "
                            oninput="this.style.height=''; this.style.height=this.scrollHeight+'px'"
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitChat();}"
                        ></textarea>
                        {{-- Context hint --}}
                        <div id="input-context-hint"
                             class="hidden px-3 pb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-indigo-400" style="font-size:12px;font-variation-settings:'FILL' 1">radio_button_checked</span>
                            <span class="text-[10px] text-indigo-400/80">
                                Editando: <span id="input-hint-name" class="font-semibold"></span>
                            </span>
                        </div>
                    </div>

                    <button type="submit" id="send-btn"
                            class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl
                                   bg-indigo-600 hover:bg-indigo-500 active:scale-95
                                   text-white transition-all shadow-lg shadow-indigo-900/30
                                   disabled:opacity-40 disabled:cursor-not-allowed disabled:scale-100">
                        <span class="material-symbols-outlined" style="font-size:18px">send</span>
                    </button>
                </form>

                <p class="text-center text-[10px] text-gray-700 mt-2">
                    El asistente detecta automáticamente si quieres crear o mejorar un proyecto.
                    <span class="text-gray-600">Shift+Enter para nueva línea.</span>
                </p>
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STYLES
         ═══════════════════════════════════════════════════════════ --}}
    @push('scripts')
    <style>
        /* ── Textarea placeholder ─────────────────────────────── */
        #chat-input::placeholder { color: #475569; }
        #chat-input:focus        { outline: none; }

        /* ── History filter tabs ──────────────────────────────── */
        .history-filter-btn {
            padding: 0.3rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 11px;
            font-weight: 500;
            color: rgba(156,163,175,1);
            transition: all 0.15s;
            background: none;
            border: 1px solid transparent;
        }
        .history-filter-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }
        .history-filter-btn.active {
            color: rgb(167,139,250);
            background: rgba(139,92,246,0.12);
            border-color: rgba(139,92,246,0.3);
        }

        /* ── History record cards ─────────────────────────────── */
        .history-card {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s;
        }
        .history-card:hover { background: rgba(255,255,255,0.025); }

        /* ── Intent / state badges ────────────────────────────── */
        .badge-sm {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid;
        }
        .intent-crear     { background:rgba(99,102,241,.12);  color:rgb(165,180,252); border-color:rgba(99,102,241,.25); }
        .intent-editar    { background:rgba(139,92,246,.12);  color:rgb(196,181,253); border-color:rgba(139,92,246,.25); }
        .intent-req       { background:rgba(59,130,246,.12);  color:rgb(147,197,253); border-color:rgba(59,130,246,.25); }
        .intent-tareas    { background:rgba(16,185,129,.12);  color:rgb(110,231,183); border-color:rgba(16,185,129,.25); }
        .intent-sprints   { background:rgba(245,158,11,.12);  color:rgb(253,230,138); border-color:rgba(245,158,11,.25); }
        .intent-insumos   { background:rgba(249,115,22,.12);  color:rgb(253,186,116); border-color:rgba(249,115,22,.25); }
        .intent-consulta  { background:rgba(100,116,139,.1);  color:rgb(148,163,184); border-color:rgba(100,116,139,.2); }
        .estado-aplicado  { color: rgb(52,211,153); }
        .estado-borrador  { color: rgb(251,191,36);  }
        .estado-descartado{ color: rgb(100,116,139); }
        .estado-error     { color: rgb(248,113,113); }

        /* ── Chip (modules/technologies) ─────────────────────── */
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>

    {{-- ═══════════════════════════════════════════════════════════
         JAVASCRIPT
         ═══════════════════════════════════════════════════════════ --}}
    <script>
    (function () {
        'use strict';

        // ── State ────────────────────────────────────────────────────────────
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        let activeProjectId   = null;
        let activeProjectName = null;
        let historyFilter     = 'all'; // 'all' | 'project'

        // ── DOM refs ─────────────────────────────────────────────────────────
        const form          = document.getElementById('chat-form');
        const input         = document.getElementById('chat-input');
        const sendBtn       = document.getElementById('send-btn');
        const messages      = document.getElementById('chat-messages');
        const typing        = document.getElementById('typing-indicator');
        const selectorBtn   = document.getElementById('project-selector-btn');
        const selectorLabel = document.getElementById('selector-label');
        const dropdown      = document.getElementById('project-dropdown');
        const activeBadge   = document.getElementById('active-project-badge');
        const activeBadgeName = document.getElementById('active-badge-name');
        const inputHint     = document.getElementById('input-context-hint');
        const inputHintName = document.getElementById('input-hint-name');

        // ── Helpers ───────────────────────────────────────────────────────────
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function scrollBottom() { messages.scrollTop = messages.scrollHeight; }

        // ── Project selector ─────────────────────────────────────────────────
        selectorBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function () { dropdown.classList.add('hidden'); });
        dropdown.addEventListener('click', function (e) { e.stopPropagation(); });

        document.querySelectorAll('.proj-opt').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id   = this.dataset.id   || null;
                const name = this.dataset.name || '';
                dropdown.classList.add('hidden');
                selectProject(id, name);
            });
        });

        document.getElementById('btn-clear-project').addEventListener('click', function () {
            selectProject(null, '');
        });

        function selectProject(id, name) {
            if (id === 'new') {
                // Create new mode: clear active project
                activeProjectId   = null;
                activeProjectName = null;
                updateProjectUI(null, null);
                updateWelcomeText(null);
                appendSystemMessage('🆕 Modo creación de proyecto nuevo. Describe tu idea y la IA generará la estructura completa.');
                return;
            }

            activeProjectId   = id ? parseInt(id) : null;
            activeProjectName = id ? name : null;
            updateProjectUI(activeProjectId, activeProjectName);
            updateWelcomeText(activeProjectId ? activeProjectName : null);

            if (activeProjectId) {
                appendSystemMessage(
                    '📁 Proyecto activo: <strong>' + esc(activeProjectName) + '</strong>. ' +
                    'Todo lo que escribas se aplicará como mejora o análisis de este proyecto.'
                );
                // Reload history for this project if panel is open
                if (!document.getElementById('history-panel').classList.contains('translate-x-full')) {
                    loadHistory();
                }
            }
        }

        function updateProjectUI(id, name) {
            if (id) {
                selectorLabel.textContent = name;
                selectorLabel.style.color = '#f8fafc';
                activeBadge.classList.remove('hidden');
                activeBadgeName.textContent = name;
                inputHint.classList.remove('hidden');
                inputHintName.textContent = name;
            } else {
                selectorLabel.textContent = 'Sin proyecto seleccionado';
                selectorLabel.style.color = '';
                activeBadge.classList.add('hidden');
                inputHint.classList.add('hidden');
            }
        }

        function updateWelcomeText(projectName) {
            const el = document.getElementById('welcome-text');
            if (projectName) {
                el.innerHTML = '📁 Proyecto activo: <strong>' + esc(projectName) + '</strong>. ' +
                    'Escribe qué quieres mejorar, qué módulos agregar, o qué analizar.';
            } else {
                el.textContent = '¡Hola! Selecciona un proyecto activo para editar, o escribe una idea para crear uno nuevo.';
            }
        }

        // ── Chat submission ───────────────────────────────────────────────────
        form.addEventListener('submit', function (e) { e.preventDefault(); submitChat(); });

        window.submitChat = function () {
            const msg = input.value.trim();
            if (!msg || sendBtn.disabled) return;
            appendUserMessage(msg);
            input.value = '';
            input.style.height = '';
            setLoading(true);
            postToServer({ message: msg, proyecto_id: activeProjectId || null });
        };

        async function postToServer(payload) {
            try {
                const r    = await fetch('{{ route("chat.send") }}', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body:    JSON.stringify(payload),
                });
                const data = await r.json();
                setLoading(false);
                handleResponse(data);
            } catch (err) {
                setLoading(false);
                appendAIMessage('Error de conexión con el servidor.');
            }
        }

        function handleResponse(data) {
            if (data.type === 'similar_found') {
                appendSimilarFound(data);
            } else if (data.type === 'project_proposal') {
                appendProjectProposal(data);
            } else if (data.type === 'task_update_proposal') {
                appendTaskUpdateProposal(data);
            } else if (data.type === 'task_delete_proposal') {
                appendTaskDeleteProposal(data);
            } else if (data.type === 'task_status_proposal') {
                appendTaskStatusProposal(data);
            } else if (data.response) {
                appendAIMessage(data.response);
            } else {
                appendAIMessage('Sin respuesta del servidor.');
            }
        }

        // ── Message renderers ─────────────────────────────────────────────────
        function appendUserMessage(text) {
            const el = document.createElement('div');
            el.className = 'flex items-end gap-2.5 flex-row-reverse';
            el.innerHTML = `
                <div class="w-7 h-7 rounded-full bg-white/10 border border-white/15
                            flex items-center justify-center flex-shrink-0 mb-0.5">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:13px">person</span>
                </div>
                <div class="max-w-[78%] rounded-2xl rounded-br-sm px-4 py-3"
                     style="background: rgba(99,102,241,0.25); border: 1px solid rgba(99,102,241,0.3);">
                    <p class="text-sm leading-relaxed" style="color:#e2e8f0; white-space:pre-wrap">${esc(text)}</p>
                </div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        function appendAIMessage(html) {
            const el = document.createElement('div');
            el.className = 'flex items-end gap-2.5';
            el.innerHTML = `
                <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30
                            flex items-center justify-center flex-shrink-0 mb-0.5">
                    <span class="material-symbols-outlined text-indigo-400" style="font-size:13px;font-variation-settings:'FILL' 1">smart_toy</span>
                </div>
                <div class="max-w-[82%] rounded-2xl rounded-bl-sm px-4 py-3 border border-white/6"
                     style="background: rgba(255,255,255,0.04);">
                    <div class="text-sm leading-relaxed" style="color:#cbd5e1">${html}</div>
                </div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        function appendSystemMessage(html) {
            const el = document.createElement('div');
            el.className = 'flex justify-center';
            el.innerHTML = `
                <div class="text-[11px] text-gray-500 bg-white/4 border border-white/6
                            px-3 py-1.5 rounded-full max-w-[85%] text-center leading-relaxed">${html}</div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        function appendSimilarFound(data) {
            const isEdit = data.intent === 'editar_proyecto';
            const projCards = data.projects.map(p => `
                <div class="flex items-start justify-between gap-3 p-3 rounded-xl
                            border border-white/7 hover:border-white/12 transition-colors group/card"
                     style="background: rgba(255,255,255,0.04);">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold" style="color:#f1f5f9">${esc(p.nombre)}</p>
                        ${p.descripcion ? `<p class="text-xs mt-0.5 line-clamp-1" style="color:#64748b">${esc(p.descripcion)}</p>` : ''}
                        <span class="text-[10px] font-medium capitalize" style="color:#34d399">${esc(p.estado)}</span>
                    </div>
                    <div class="flex flex-col gap-1.5 items-end flex-shrink-0">
                        <button onclick="useExistingProject(${p.id}, ${data.chat_id})"
                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg transition-colors"
                                style="background:rgba(139,92,246,0.2);color:rgb(196,181,253);border:1px solid rgba(139,92,246,0.35)">
                            Editar →
                        </button>
                        <a href="${esc(p.url_show)}" target="_blank"
                           class="text-[10px] hover:underline" style="color:#475569">Ver</a>
                    </div>
                </div>`).join('');

            const el = document.createElement('div');
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mb-0.5"
                     style="background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3)">
                    <span class="material-symbols-outlined" style="font-size:13px;color:#fbbf24;font-variation-settings:'FILL' 1">warning</span>
                </div>
                <div class="max-w-[88%] w-full rounded-2xl rounded-bl-sm px-4 py-4 space-y-3"
                     style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);">
                    <div>
                        <p class="text-sm font-bold" style="color:#fef3c7">
                            ${esc(isEdit ? 'Proyectos relacionados encontrados' : 'Proyectos similares encontrados')}
                        </p>
                        <p class="text-xs mt-0.5" style="color:#a1a1aa">
                            ${esc(isEdit ? 'Selecciona cuál quieres mejorar, o crea uno nuevo.' : 'Antes de crear, ¿quieres editar uno existente?')}
                        </p>
                    </div>
                    <div class="space-y-2">${projCards}</div>
                    <div class="flex flex-wrap gap-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.06)">
                        <button onclick="confirmCreate(${data.chat_id})"
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-colors"
                                style="background:rgba(99,102,241,0.8);color:#fff">
                            Crear proyecto nuevo de todos modos
                        </button>
                        <button onclick="discardAction(${data.chat_id})"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                                style="color:#94a3b8;border:1px solid rgba(255,255,255,0.1)">
                            Cancelar
                        </button>
                    </div>
                </div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        // ── Project proposal preview ──────────────────────────────────────────
        function prioStyle(p) {
            if (p === 'alta') return 'color:#fca5a5';
            if (p === 'baja') return 'color:#94a3b8';
            return 'color:#fcd34d';
        }

        function proposalSection(emoji, label, items, renderFn) {
            if (!items || items.length === 0) return '';
            return `
                <div>
                    <p style="font-size:11px;font-weight:700;color:#a5b4fc;margin-bottom:6px">
                        ${emoji} ${label} <span style="color:#334155">(${items.length})</span>
                    </p>
                    <div style="padding-left:8px;display:flex;flex-direction:column;gap:6px">
                        ${items.map(renderFn).join('')}
                    </div>
                </div>`;
        }

        function appendProjectProposal(data) {
            const d        = data.data || {};
            const resumen  = d.resumen || 'Propuesta de mejoras generada por IA';
            const reqs     = Array.isArray(d.requerimientos) ? d.requerimientos : [];
            const tasks    = Array.isArray(d.tareas)         ? d.tareas         : [];
            const sprints  = Array.isArray(d.sprints)        ? d.sprints        : [];
            const insumos  = Array.isArray(d.insumos)        ? d.insumos        : [];
            const total    = reqs.length + tasks.length + sprints.length + insumos.length;

            const reqsHtml = proposalSection('📋', 'Requerimientos', reqs, r => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:8px 10px">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <span style="font-size:12px;font-weight:600;color:#e2e8f0">${esc(r.titulo || '—')}</span>
                        ${r.tipo ? `<span style="font-size:9px;background:rgba(59,130,246,0.15);color:#93c5fd;border:1px solid rgba(59,130,246,0.2);padding:0 5px;border-radius:99px">${esc(r.tipo)}</span>` : ''}
                        ${r.prioridad ? `<span style="font-size:9px;font-weight:600;${prioStyle(r.prioridad)}">${esc(r.prioridad)}</span>` : ''}
                    </div>
                    ${r.descripcion ? `<p style="font-size:11px;color:#64748b;margin-top:3px">${esc(r.descripcion)}</p>` : ''}
                </div>`);

            const tasksHtml = proposalSection('✅', 'Tareas', tasks, t => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:8px 10px">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <span style="font-size:12px;font-weight:600;color:#e2e8f0">${esc(t.titulo || '—')}</span>
                        ${t.prioridad ? `<span style="font-size:9px;font-weight:600;${prioStyle(t.prioridad)}">[${esc(t.prioridad)}]</span>` : ''}
                    </div>
                    ${t.descripcion ? `<p style="font-size:11px;color:#64748b;margin-top:3px">${esc(t.descripcion)}</p>` : ''}
                </div>`);

            const sprintsHtml = proposalSection('🏃', 'Sprints', sprints, s => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:8px 10px">
                    <span style="font-size:12px;font-weight:600;color:#e2e8f0">${esc(s.nombre || '—')}</span>
                    ${(s.semanas || s.duracion_semanas) ? `<span style="font-size:10px;color:#818cf8;margin-left:6px">${s.semanas || s.duracion_semanas} sem.</span>` : ''}
                    ${s.objetivo ? `<p style="font-size:11px;color:#64748b;margin-top:3px">${esc(s.objetivo)}</p>` : ''}
                </div>`);

            const insumosHtml = proposalSection('📦', 'Insumos', insumos, i => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:8px 10px">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <span style="font-size:12px;font-weight:600;color:#e2e8f0">${esc(i.titulo || i.nombre || '—')}</span>
                        ${i.tipo ? `<span style="font-size:9px;color:#94a3b8">[${esc(i.tipo)}]</span>` : ''}
                    </div>
                    ${(i.contenido || i.descripcion) ? `<p style="font-size:11px;color:#64748b;margin-top:3px">${esc(i.contenido || i.descripcion)}</p>` : ''}
                </div>`);

            const el = document.createElement('div');
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:2px">
                    <span class="material-symbols-outlined" style="font-size:13px;color:#34d399;font-variation-settings:'FILL' 1">edit_note</span>
                </div>
                <div style="max-width:90%;width:100%;border-radius:16px 16px 16px 4px;padding:16px;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.22)">

                    <div style="margin-bottom:12px">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <span style="font-size:9px;font-weight:700;letter-spacing:.08em;color:#6ee7b7;text-transform:uppercase">Propuesta de cambios</span>
                            <span style="font-size:10px;color:#334155">para «${esc(data.proyecto_nombre)}»</span>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#f1f5f9;line-height:1.5">${esc(resumen)}</p>
                    </div>

                    ${total === 0 ? `<p style="font-size:12px;color:#64748b">La IA no propuso cambios concretos. Intenta ser más específico.</p>` : `
                        <div style="display:flex;flex-direction:column;gap:12px">
                            ${reqsHtml}${tasksHtml}${sprintsHtml}${insumosHtml}
                        </div>`}

                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.06)">
                        ${total > 0 ? `
                            <button onclick="applyProposal(${data.chat_id})"
                                    style="background:rgba(16,185,129,0.85);color:#fff;font-size:12px;font-weight:700;padding:7px 16px;border-radius:8px;border:none;cursor:pointer">
                                ✓ Confirmar y guardar
                            </button>` : ''}
                        <button onclick="discardProposal(${data.chat_id})"
                                style="color:#94a3b8;font-size:12px;font-weight:500;padding:7px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:none;cursor:pointer">
                            ✕ Cancelar
                        </button>
                    </div>
                </div>`;

            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        // ── Apply / discard proposal (new items) ─────────────────────────────
        window.applyProposal = function (chatId) {
            setLoading(true);
            postToServer({ action: 'apply_proposal', chat_id: chatId });
        };

        window.discardProposal = function (chatId) {
            setLoading(true);
            postToServer({ action: 'discard_proposal', chat_id: chatId });
        };

        // ── Task update proposal renderer ─────────────────────────────────────
        function appendTaskUpdateProposal(data) {
            const d       = data.data || {};
            const resumen = d.resumen || 'Propuesta de cambios en tareas';
            const updates = Array.isArray(d.actualizaciones_tareas) ? d.actualizaciones_tareas : [];
            const invalid = d.invalid_count || 0;

            const updatesHtml = updates.map((u, i) => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:10px 12px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                        <span style="font-size:9px;font-weight:700;color:#64748b">#${i + 1}</span>
                        <span style="font-size:11px;background:rgba(239,68,68,0.12);color:#fca5a5;border:1px solid rgba(239,68,68,0.2);padding:2px 8px;border-radius:99px;text-decoration:line-through">
                            ${esc(u.titulo_actual || '—')}
                        </span>
                        <span style="font-size:14px;color:#475569">→</span>
                        <span style="font-size:11px;background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.2);padding:2px 8px;border-radius:99px;font-weight:600">
                            ${esc(u.titulo_nuevo || '—')}
                        </span>
                    </div>
                    ${u.descripcion_nueva ? `<p style="font-size:11px;color:#64748b;line-height:1.5;margin-top:4px">${esc(u.descripcion_nueva)}</p>` : ''}
                </div>`).join('');

            const el = document.createElement('div');
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:2px">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#fbbf24;font-variation-settings:'FILL' 1">sync_alt</span>
                </div>
                <div style="max-width:90%;width:100%;border-radius:16px 16px 16px 4px;padding:16px;background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.22)">

                    <div style="margin-bottom:12px">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                            <span style="font-size:9px;font-weight:700;letter-spacing:.08em;color:#fcd34d;text-transform:uppercase">Actualización de tareas existentes</span>
                            <span style="font-size:10px;color:#334155">en «${esc(data.proyecto_nombre)}»</span>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#f1f5f9;line-height:1.5">${esc(resumen)}</p>
                    </div>

                    ${updates.length > 0 ? `
                        <div>
                            <p style="font-size:11px;font-weight:700;color:#fbbf24;margin-bottom:8px">🔄 Tareas a modificar (${updates.length})</p>
                            <div style="display:flex;flex-direction:column;gap:8px">${updatesHtml}</div>
                        </div>` : `<p style="font-size:12px;color:#64748b">Sin cambios propuestos. Intenta ser más específico.</p>`}

                    ${invalid > 0 ? `
                        <p style="font-size:11px;color:#fca5a5;margin-top:10px">
                            ⚠ ${invalid} sugerencia(s) omitidas: ID de tarea inválido o no pertenece al proyecto.
                        </p>` : ''}

                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.06)">
                        ${updates.length > 0 ? `
                            <button onclick="applyTaskUpdates(${data.chat_id})"
                                    style="background:rgba(245,158,11,0.85);color:#fff;font-size:12px;font-weight:700;padding:7px 16px;border-radius:8px;border:none;cursor:pointer">
                                ✓ Confirmar cambios
                            </button>` : ''}
                        <button onclick="discardProposal(${data.chat_id})"
                                style="color:#94a3b8;font-size:12px;font-weight:500;padding:7px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:none;cursor:pointer">
                            ✕ Cancelar
                        </button>
                    </div>
                </div>`;

            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        // Apply task updates
        window.applyTaskUpdates = function (chatId) {
            setLoading(true);
            postToServer({ action: 'apply_task_updates', chat_id: chatId });
        };

        // ── Task delete proposal ──────────────────────────────────────────────
        function appendTaskDeleteProposal(data) {
            const d       = data.data || {};
            const resumen = d.resumen || 'Tareas propuestas para eliminar';
            const tasks   = Array.isArray(d.tareas_a_eliminar) ? d.tareas_a_eliminar : [];
            const invalid = d.invalid_count || 0;

            const tasksHtml = tasks.map(t => `
                <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:8px 12px;display:flex;align-items:start;gap:10px">
                    <span style="font-size:16px;flex-shrink:0;margin-top:1px">🗑</span>
                    <div>
                        <p style="font-size:12px;font-weight:700;color:#fca5a5">${esc(t.titulo || '—')}</p>
                        ${t.razon ? `<p style="font-size:11px;color:#64748b;margin-top:2px">${esc(t.razon)}</p>` : ''}
                    </div>
                </div>`).join('');

            const el = document.createElement('div');
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:2px">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#f87171;font-variation-settings:'FILL' 1">delete_forever</span>
                </div>
                <div style="max-width:90%;width:100%;border-radius:16px 16px 16px 4px;padding:16px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2)">
                    <div style="margin-bottom:10px">
                        <span style="font-size:9px;font-weight:700;letter-spacing:.08em;color:#f87171;text-transform:uppercase">⚠ Eliminación permanente</span>
                        <p style="font-size:13px;font-weight:600;color:#f1f5f9;margin-top:3px;line-height:1.5">${esc(resumen)}</p>
                    </div>
                    ${tasks.length > 0 ? `<div style="display:flex;flex-direction:column;gap:6px">${tasksHtml}</div>` : '<p style="font-size:12px;color:#64748b">Sin tareas identificadas.</p>'}
                    ${invalid > 0 ? `<p style="font-size:11px;color:#fcd34d;margin-top:8px">⚠ ${invalid} sugerencia(s) omitidas: tarea no válida.</p>` : ''}
                    <div style="display:flex;gap:8px;margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.06)">
                        ${tasks.length > 0 ? `
                            <button onclick="applyTaskDeletes(${data.chat_id})"
                                    style="background:rgba(239,68,68,0.8);color:#fff;font-size:12px;font-weight:700;padding:7px 14px;border-radius:8px;border:none;cursor:pointer">
                                ✓ Confirmar eliminación
                            </button>` : ''}
                        <button onclick="discardProposal(${data.chat_id})"
                                style="color:#94a3b8;font-size:12px;font-weight:500;padding:7px 10px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:none;cursor:pointer">
                            ✕ Cancelar
                        </button>
                    </div>
                </div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        window.applyTaskDeletes = function (chatId) {
            if (!confirm('¿Confirmas la eliminación permanente de las tareas? Esta acción no se puede deshacer.')) return;
            setLoading(true);
            postToServer({ action: 'apply_task_deletes', chat_id: chatId });
        };

        // ── Task status change proposal ───────────────────────────────────────
        function appendTaskStatusProposal(data) {
            const d       = data.data || {};
            const resumen = d.resumen || 'Cambios de estado propuestos';
            const changes = Array.isArray(d.cambios_estado) ? d.cambios_estado : [];
            const invalid = d.invalid_count || 0;

            const changesHtml = changes.map(c => `
                <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:8px 12px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span style="font-size:12px;font-weight:600;color:#e2e8f0">${esc(c.titulo_actual || '—')}</span>
                        <span style="font-size:10px;background:rgba(100,116,139,0.15);color:#94a3b8;border:1px solid rgba(100,116,139,0.2);padding:1px 6px;border-radius:99px">${esc(c.estado_actual || '—')}</span>
                        <span style="font-size:14px;color:#475569">→</span>
                        <span style="font-size:10px;background:rgba(99,102,241,0.15);color:#a5b4fc;border:1px solid rgba(99,102,241,0.25);padding:1px 6px;border-radius:99px;font-weight:600">${esc(c.estado_nuevo_nombre || c.estado_nuevo || '—')}</span>
                    </div>
                </div>`).join('');

            const el = document.createElement('div');
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:2px">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#818cf8;font-variation-settings:'FILL' 1">move_down</span>
                </div>
                <div style="max-width:90%;width:100%;border-radius:16px 16px 16px 4px;padding:16px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.2)">
                    <div style="margin-bottom:10px">
                        <span style="font-size:9px;font-weight:700;letter-spacing:.08em;color:#a5b4fc;text-transform:uppercase">Cambio de estado</span>
                        <p style="font-size:13px;font-weight:600;color:#f1f5f9;margin-top:3px;line-height:1.5">${esc(resumen)}</p>
                    </div>
                    ${changes.length > 0 ? `<div style="display:flex;flex-direction:column;gap:6px">${changesHtml}</div>` : '<p style="font-size:12px;color:#64748b">Sin cambios identificados.</p>'}
                    ${invalid > 0 ? `<p style="font-size:11px;color:#fcd34d;margin-top:8px">⚠ ${invalid} cambio(s) omitidos: estado o tarea inválida.</p>` : ''}
                    <div style="display:flex;gap:8px;margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.06)">
                        ${changes.length > 0 ? `
                            <button onclick="applyTaskStatusChanges(${data.chat_id})"
                                    style="background:rgba(99,102,241,0.85);color:#fff;font-size:12px;font-weight:700;padding:7px 14px;border-radius:8px;border:none;cursor:pointer">
                                ✓ Confirmar cambios
                            </button>` : ''}
                        <button onclick="discardProposal(${data.chat_id})"
                                style="color:#94a3b8;font-size:12px;font-weight:500;padding:7px 10px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:none;cursor:pointer">
                            ✕ Cancelar
                        </button>
                    </div>
                </div>`;
            typing.insertAdjacentElement('beforebegin', el);
            scrollBottom();
        }

        window.applyTaskStatusChanges = function (chatId) {
            setLoading(true);
            postToServer({ action: 'apply_task_status_changes', chat_id: chatId });
        };

        // ── Action confirmations ──────────────────────────────────────────────
        window.confirmCreate = function (chatId) {
            setLoading(true);
            postToServer({ action: 'confirm_create', chat_id: chatId });
        };

        window.useExistingProject = function (projectId, chatId) {
            setLoading(true);
            postToServer({ action: 'use_existing', chat_id: chatId, project_id: projectId });
        };

        window.discardAction = function (chatId) {
            setLoading(true);
            postToServer({ action: 'discard', chat_id: chatId });
        };

        // ── Loading state ─────────────────────────────────────────────────────
        function setLoading(on) {
            sendBtn.disabled = on;
            typing.classList.toggle('hidden', !on);
            typing.classList.toggle('flex', on);
            if (on) scrollBottom();
        }

        // ── Clear visible chat ────────────────────────────────────────────────
        window.clearChat = function () {
            if (!confirm('¿Limpiar la conversación visible? El historial IA se mantiene.')) return;
            const items = messages.querySelectorAll('.flex.items-end, .flex.items-start, .flex.justify-center');
            items.forEach(el => { if (el !== typing && !el.id) el.remove(); });
        };

        // ══════════════════════════════════════════════════════════════════════
        //  HISTORY PANEL
        // ══════════════════════════════════════════════════════════════════════
        document.getElementById('btn-open-history').addEventListener('click', openHistory);
        document.getElementById('btn-close-history').addEventListener('click', closeHistory);

        window.openHistory = function () {
            document.getElementById('history-panel').classList.remove('translate-x-full');
            document.getElementById('history-backdrop').classList.remove('hidden');
            loadHistory();
        };

        window.closeHistory = function () {
            document.getElementById('history-panel').classList.add('translate-x-full');
            document.getElementById('history-backdrop').classList.add('hidden');
        };

        // Filter tabs
        document.querySelectorAll('.history-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                historyFilter = this.dataset.filter;

                // Disable "Este proyecto" tab if no project active
                if (historyFilter === 'project' && !activeProjectId) {
                    appendSystemMessage('Selecciona un proyecto activo para filtrar el historial.');
                    historyFilter = 'all';
                    document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
                    document.querySelector('[data-filter="all"]').classList.add('active');
                }

                loadHistory();
            });
        });

        async function loadHistory() {
            const list = document.getElementById('history-list');
            list.innerHTML = `
                <div class="flex items-center justify-center h-24">
                    <div class="flex gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:120ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-600 animate-bounce" style="animation-delay:240ms"></span>
                    </div>
                </div>`;

            let url = '{{ route("chat.history") }}';
            if (historyFilter === 'project' && activeProjectId) {
                url += '?proyecto_id=' + activeProjectId;
            }

            try {
                const r    = await fetch(url, { headers: { 'X-CSRF-TOKEN': csrf } });
                const data = await r.json();
                renderHistory(data.history || []);
            } catch {
                list.innerHTML = `<p class="text-xs text-center p-4" style="color:#f87171">Error al cargar historial.</p>`;
            }
        }

        function intentBadgeClass(intent) {
            const map = {
                'crear_proyecto': 'badge-sm intent-crear',
                'editar_proyecto': 'badge-sm intent-editar',
                'generar_requerimientos': 'badge-sm intent-req',
                'generar_tareas': 'badge-sm intent-tareas',
                'generar_sprints': 'badge-sm intent-sprints',
                'generar_insumos': 'badge-sm intent-insumos',
            };
            return map[intent] || 'badge-sm intent-consulta';
        }

        function estadoClass(estado) {
            return 'estado-' + (estado || 'borrador');
        }

        function renderHistory(items) {
            const list  = document.getElementById('history-list');
            const badge = document.getElementById('history-count-badge');

            if (items.length === 0) {
                badge.classList.add('hidden');
                const msg = activeProjectId && historyFilter === 'project'
                    ? 'Aún no hay prompts registrados para este proyecto.'
                    : 'Aún no hay prompts registrados.';
                list.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-40 gap-3 px-4">
                        <span class="material-symbols-outlined" style="font-size:32px;color:#1e293b">history</span>
                        <p class="text-xs text-center" style="color:#475569">${msg}</p>
                    </div>`;
                return;
            }

            badge.textContent = items.length;
            badge.classList.remove('hidden');

            list.innerHTML = items.map(h => `
                <div class="history-card group">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="${intentBadgeClass(h.tipo_accion)}">${esc(h.tipo_label)}</span>
                        <span class="text-[10px] font-semibold ${estadoClass(h.estado)}">${esc(h.estado)}</span>
                        <span class="text-[10px] ml-auto flex-shrink-0" style="color:#334155">${esc(h.created_at_human)}</span>
                    </div>

                    <p class="text-xs leading-relaxed line-clamp-2 mb-1.5" style="color:#94a3b8">${esc(h.prompt_usuario)}</p>

                    ${h.respuesta_resumida ? `<p class="text-[10px] italic line-clamp-1 mb-1" style="color:#475569">"${esc(h.respuesta_resumida)}"</p>` : ''}

                    ${h.proyecto_nombre ? `
                        <a href="${esc(h.proyecto_url)}"
                           class="text-[10px] flex items-center gap-1 hover:underline" style="color:#818cf8">
                            <span class="material-symbols-outlined" style="font-size:11px">folder</span>
                            ${esc(h.proyecto_nombre)}
                        </a>` : ''}

                    <div class="flex items-center gap-2 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-[9px]" style="color:#1e293b">${esc(h.created_at_full)}</span>
                        ${h.respuesta_completa ? `
                            <button onclick="copyResponse(${JSON.stringify(esc(h.respuesta_completa))})"
                                    class="text-[10px] transition-colors hover:underline ml-auto" style="color:#475569">
                                Copiar respuesta
                            </button>` : ''}
                        <button onclick="deleteHistoryItem(${h.id})"
                                class="text-[10px] transition-colors hover:underline" style="color:#7f1d1d">
                            Borrar
                        </button>
                    </div>
                </div>`).join('');
        }

        window.copyResponse = function (text) {
            // Unescape HTML entities for copying
            const d = document.createElement('div');
            d.innerHTML = text;
            const plain = d.textContent || d.innerText || text;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(plain).then(
                    () => { alert('Respuesta copiada al portapapeles.'); },
                    () => { alert('No se pudo copiar.'); }
                );
            }
        };

        window.deleteHistoryItem = async function (id) {
            if (!confirm('¿Borrar este registro del historial? No se borran datos del proyecto.')) return;
            try {
                const r    = await fetch('{{ url("proyectos/chat/historial") }}/' + id, {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf },
                });
                const data = await r.json();
                if (data.ok) loadHistory();
                else alert('Error al borrar el registro.');
            } catch { alert('Error de conexión.'); }
        };

        // Clear history
        document.getElementById('btn-clear-history').addEventListener('click', async function () {
            const scope   = historyFilter === 'project' && activeProjectId
                ? 'del proyecto «' + activeProjectName + '»'
                : 'completo';
            if (!confirm(`¿Limpiar el historial ${scope}? Solo se borran registros de IA, no proyectos ni datos reales.`)) return;

            const payload = {};
            if (historyFilter === 'project' && activeProjectId) {
                payload.proyecto_id = activeProjectId;
            }

            try {
                const r    = await fetch('{{ route("chat.history.clear") }}', {
                    method:  'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body:    JSON.stringify(payload),
                });
                const data = await r.json();
                if (data.ok) {
                    loadHistory();
                    alert(`${data.deleted} registro(s) eliminados. Los proyectos y datos reales no fueron afectados.`);
                }
            } catch { alert('Error de conexión.'); }
        });

    })();
    </script>
    @endpush

</x-app-layout>
