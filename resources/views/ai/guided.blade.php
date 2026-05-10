<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-violet-400" style="font-size: 18px; font-variation-settings: 'FILL' 1;">auto_awesome</span>
                <span class="text-white font-semibold">Crear Proyecto con IA</span>
                <span class="text-gray-600">·</span>
                <span class="text-xs {{ $aiOnline ? 'text-emerald-400' : 'text-red-400' }} flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full {{ $aiOnline ? 'bg-emerald-400' : 'bg-red-400' }} animate-pulse inline-block"></span>
                    {{ $aiOnline ? 'Asistente disponible' : 'Servicio no disponible' }}
                </span>
            </div>
            <a href="{{ route('asistente-ia.index') }}"
               class="text-xs text-gray-500 hover:text-gray-300 transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined" style="font-size: 14px;">chevron_left</span>
                Modo clásico
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

        {{-- ─── SERVICE ALERT ───────────────────────────────────────────── --}}
        @unless($aiOnline)
        <div class="rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 flex gap-3 items-start">
            <span class="material-symbols-outlined text-amber-400 flex-shrink-0 mt-0.5" style="font-size: 18px;">warning</span>
            <div>
                <p class="text-xs font-semibold text-amber-300">Asistente no disponible</p>
                <p class="text-[11px] text-amber-200/70 mt-0.5">
                    El servicio de generacion IA no esta activo. Contacta al administrador.
                </p>
            </div>
        </div>
        @endunless

        {{-- ─── STEP INDICATOR ─────────────────────────────────────────────── --}}
        <div id="step-indicator" class="flex items-center gap-0">

            {{-- Step 1: Idea --}}
            <div class="step-node" data-step="1">
                <div class="step-circle active" id="step-circle-1">
                    <span class="material-symbols-outlined" style="font-size: 14px;">lightbulb</span>
                </div>
                <span class="step-label">Idea</span>
            </div>

            <div class="step-line" id="step-line-1"></div>

            {{-- Step 2: Interpretación --}}
            <div class="step-node" data-step="2">
                <div class="step-circle" id="step-circle-2">
                    <span class="material-symbols-outlined" style="font-size: 14px;">psychology</span>
                </div>
                <span class="step-label">Interpretar</span>
            </div>

            <div class="step-line" id="step-line-2"></div>

            {{-- Step 3–7: Coming in next phases --}}
            <div class="step-node" data-step="3">
                <div class="step-circle locked" id="step-circle-3">
                    <span class="material-symbols-outlined" style="font-size: 14px;">folder</span>
                </div>
                <span class="step-label text-gray-600">Proyecto</span>
            </div>

            <div class="step-line locked"></div>

            <div class="step-node" data-step="4">
                <div class="step-circle locked" id="step-circle-4">
                    <span class="material-symbols-outlined" style="font-size: 14px;">list_alt</span>
                </div>
                <span class="step-label text-gray-600">Req.</span>
            </div>

            <div class="step-line locked"></div>

            <div class="step-node" data-step="5">
                <div class="step-circle locked" id="step-circle-5">
                    <span class="material-symbols-outlined" style="font-size: 14px;">task_alt</span>
                </div>
                <span class="step-label text-gray-600">Tareas</span>
            </div>

            <div class="step-line locked"></div>

            <div class="step-node" data-step="6">
                <div class="step-circle locked" id="step-circle-6">
                    <span class="material-symbols-outlined" style="font-size: 14px;">sprint</span>
                </div>
                <span class="step-label text-gray-600">Sprints</span>
            </div>

            <div class="step-line locked"></div>

            <div class="step-node" data-step="7">
                <div class="step-circle locked" id="step-circle-7">
                    <span class="material-symbols-outlined" style="font-size: 14px;">inventory_2</span>
                </div>
                <span class="step-label text-gray-600">Insumos</span>
            </div>

        </div>

        {{-- ─── PHASE 1: IDEA INPUT ─────────────────────────────────────────── --}}
        <div id="phase-idea">
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 space-y-5">

                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-violet-500/20 border border-violet-500/30
                                     flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-violet-400" style="font-size: 14px;">lightbulb</span>
                        </span>
                        Describe tu idea de proyecto
                    </h2>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed ml-8">
                        Escribe con tus propias palabras. No necesitas ser técnico. La IA interpretará tu idea,
                        sugerirá un nombre y te propondrá una estructura completa.
                    </p>
                </div>

                {{-- Model is resolved internally; not exposed to users --}}
                <input type="hidden" id="model-select" name="modelo" value="{{ $defaultModel }}">

                {{-- Idea textarea --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1.5">
                        Tu idea
                        <span class="text-gray-600 font-normal ml-1">(mín. 10 caracteres)</span>
                    </label>
                    <textarea id="idea-input" rows="6"
                              placeholder="Ej: Quiero una aplicación web para gestionar proyectos multimedia. Los usuarios podrán subir imágenes, videos y audios, organizar el trabajo en sprints y ver el progreso en un tablero Kanban..."
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                                     placeholder-gray-600 resize-none leading-relaxed
                                     focus:outline-none focus:border-violet-500/50 transition-colors"></textarea>
                    <div class="flex justify-between mt-1">
                        <span id="idea-hint" class="text-[10px] text-red-400/80"></span>
                        <span id="idea-count" class="text-[10px] text-gray-600">0 / 2000</span>
                    </div>
                </div>

                {{-- Error box --}}
                <div id="error-box" class="hidden rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 space-y-1.5">
                    <p class="text-xs font-semibold text-red-300 flex items-center gap-1.5">
                        <span class="material-symbols-outlined" style="font-size: 15px;">error</span>
                        Error al interpretar
                    </p>
                    <p id="error-message" class="text-xs text-red-200/80 leading-relaxed"></p>
                    <details id="raw-details" class="hidden mt-1">
                        <summary class="text-[10px] text-red-300/50 cursor-pointer hover:text-red-300 select-none">
                            Ver respuesta cruda del modelo
                        </summary>
                        <pre id="raw-content" class="mt-2 text-[10px] text-gray-400 bg-black/30 rounded-lg p-2
                                                      overflow-x-auto whitespace-pre-wrap max-h-40 overflow-y-auto font-mono"></pre>
                        <button onclick="copyRaw()" class="mt-1 text-[10px] text-indigo-400 hover:text-indigo-300 transition-colors">
                            Copiar respuesta cruda
                        </button>
                    </details>
                </div>

                {{-- Submit --}}
                <button id="interpret-btn" type="button"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4
                               bg-gradient-to-r from-violet-600 to-indigo-600
                               hover:from-violet-500 hover:to-indigo-500
                               active:scale-95 transition-all
                               text-white text-sm font-bold rounded-xl
                               disabled:opacity-40 disabled:cursor-not-allowed disabled:scale-100">
                    <span class="material-symbols-outlined btn-icon" style="font-size: 18px;">auto_awesome</span>
                    <span class="btn-label">Analizar con IA</span>
                </button>

            </div>
        </div>

        {{-- ─── PHASE 2: INTERPRETATION RESULT ─────────────────────────────── --}}
        <div id="phase-interpretation" class="hidden space-y-4">

            {{-- Interpretation card --}}
            <div class="bg-white/3 border border-violet-500/20 rounded-2xl overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3 border-b border-white/5
                            bg-gradient-to-r from-violet-600/10 to-indigo-600/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-violet-400" style="font-size: 16px; font-variation-settings: 'FILL' 1;">psychology</span>
                        <span class="text-xs font-semibold text-violet-300">Interpretación de tu idea</span>
                    </div>
                    <button id="btn-reinterpret"
                            class="text-[10px] text-gray-500 hover:text-gray-300 transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size: 13px;">refresh</span>
                        Reinterpretar
                    </button>
                </div>

                <div class="p-5 space-y-4">

                    {{-- Project name --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold mb-1">Nombre sugerido</p>
                        <p id="interp-name" class="text-xl font-black text-white leading-tight"></p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold mb-1">Descripción mejorada</p>
                        <p id="interp-desc" class="text-sm text-gray-300 leading-relaxed"></p>
                    </div>

                    {{-- Modules --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold mb-2">Módulos detectados</p>
                        <div id="interp-modules" class="flex flex-wrap gap-1.5"></div>
                    </div>

                    {{-- Metadata row --}}
                    <div class="flex flex-wrap gap-3 pt-1">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-gray-600" style="font-size: 14px;">bar_chart</span>
                            <span class="text-[11px] text-gray-500">Complejidad:</span>
                            <span id="interp-complexity" class="text-[11px] font-semibold"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-gray-600" style="font-size: 14px;">schedule</span>
                            <span class="text-[11px] text-gray-500">Duración estimada:</span>
                            <span id="interp-duration" class="text-[11px] font-semibold text-gray-300"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-gray-600" style="font-size: 14px;">devices</span>
                            <span class="text-[11px] text-gray-500">Tipo:</span>
                            <span id="interp-type" class="text-[11px] font-semibold text-indigo-300 capitalize"></span>
                        </div>
                    </div>

                    {{-- Technologies --}}
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-600 font-semibold mb-2">Tecnologías sugeridas</p>
                        <div id="interp-techs" class="flex flex-wrap gap-1.5"></div>
                    </div>

                </div>
            </div>

            {{-- Mode selection --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-5 space-y-3">
                <div>
                    <h3 class="text-sm font-bold text-white">¿Cómo quieres continuar?</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Elige el modo de creación del proyecto.</p>
                </div>

                <div class="grid grid-cols-1 gap-3">

                    {{-- Guided mode --}}
                    <button onclick="selectMode('guided')"
                            class="mode-btn group text-left rounded-xl border border-violet-500/30 bg-violet-500/8
                                   hover:border-violet-500/60 hover:bg-violet-500/15 transition-all p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-500/20 border border-violet-500/30
                                        flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="material-symbols-outlined text-violet-400" style="font-size: 16px; font-variation-settings: 'FILL' 1;">route</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white group-hover:text-violet-200 transition-colors">
                                    Modo Guiado
                                    <span class="ml-2 text-[10px] font-semibold bg-violet-500/20 text-violet-300 px-2 py-0.5 rounded-full border border-violet-500/30">Recomendado</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                    Revisas y apruebas cada fase antes de continuar.
                                    Proyecto → Requerimientos → Tareas → Sprints → Insumos.
                                </p>
                            </div>
                        </div>
                    </button>

                    {{-- Automatic mode --}}
                    <button onclick="selectMode('automatic')"
                            class="mode-btn group text-left rounded-xl border border-indigo-500/25 bg-indigo-500/6
                                   hover:border-indigo-500/50 hover:bg-indigo-500/12 transition-all p-4
                                   opacity-60 cursor-not-allowed">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/15 border border-indigo-500/25
                                        flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="material-symbols-outlined text-indigo-400" style="font-size: 16px; font-variation-settings: 'FILL' 1;">bolt</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white flex items-center gap-2">
                                    Modo Automático
                                    <span class="text-[10px] font-semibold bg-gray-700/60 text-gray-400 px-2 py-0.5 rounded-full border border-white/10">Próximamente</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                    La IA genera todo el proyecto completo de una vez y tú solo revisas al final.
                                </p>
                            </div>
                        </div>
                    </button>

                    {{-- Base project only --}}
                    <button onclick="selectMode('base_only')"
                            class="mode-btn group text-left rounded-xl border border-white/8 bg-white/3
                                   hover:border-white/15 hover:bg-white/6 transition-all p-4
                                   opacity-60 cursor-not-allowed">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10
                                        flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="material-symbols-outlined text-gray-400" style="font-size: 16px; font-variation-settings: 'FILL' 1;">folder</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white flex items-center gap-2">
                                    Solo Proyecto Base
                                    <span class="text-[10px] font-semibold bg-gray-700/60 text-gray-400 px-2 py-0.5 rounded-full border border-white/10">Próximamente</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                    Crea solo el proyecto con nombre y descripción, sin módulos adicionales.
                                </p>
                            </div>
                        </div>
                    </button>

                </div>
            </div>

            {{-- Mode selected confirmation (shown after selection) --}}
            <div id="mode-confirmation" class="hidden bg-white/3 border border-emerald-500/20 rounded-2xl p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-emerald-400" style="font-size: 20px; font-variation-settings: 'FILL' 1;">check_circle</span>
                    <div>
                        <p class="text-sm font-semibold text-white">Modo seleccionado: <span id="mode-name-display" class="text-emerald-300"></span></p>
                        <p class="text-xs text-gray-500 mt-0.5">Las siguientes fases estarán disponibles en la próxima actualización.</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ─── LOADING OVERLAY ─────────────────────────────────────────────── --}}
        <div id="loading-overlay" class="hidden bg-white/3 border border-white/8 rounded-2xl p-8 text-center">
            <div class="flex justify-center gap-1.5 mb-4">
                <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 120ms"></span>
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-bounce" style="animation-delay: 240ms"></span>
            </div>
            <p class="text-sm font-semibold text-white mb-1">Analizando tu idea…</p>
            <p class="text-xs text-gray-500">La IA está interpretando tu proyecto. Puede tardar hasta 2 minutos.</p>
        </div>

    </div>

    @push('scripts')
    <style>
        /* ── Step indicator ─────────────────────────────────────────────────── */
        .step-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: rgba(107,114,128,1);
            transition: all 0.3s;
        }
        .step-circle.active {
            border-color: rgba(139,92,246,0.7);
            background: rgba(139,92,246,0.15);
            color: rgb(167,139,250);
        }
        .step-circle.done {
            border-color: rgba(16,185,129,0.6);
            background: rgba(16,185,129,0.12);
            color: rgb(52,211,153);
        }
        .step-circle.locked {
            opacity: 0.35;
        }
        .step-label {
            font-size: 10px;
            color: rgba(107,114,128,1);
            white-space: nowrap;
            font-weight: 500;
        }
        .step-label.active-label { color: rgb(167,139,250); }
        .step-line {
            flex: 1;
            height: 1.5px;
            background: rgba(255,255,255,0.07);
            margin: 0 3px;
            margin-bottom: 14px;
            transition: background 0.3s;
            min-width: 16px;
        }
        .step-line.done { background: rgba(16,185,129,0.4); }
        .step-line.locked { opacity: 0.3; }

        /* ── Module / tech chips ────────────────────────────────────────────── */
        .chip-module {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(139,92,246,0.12);
            color: rgb(196,181,253);
            border: 1px solid rgba(139,92,246,0.25);
        }
        .chip-tech {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(59,130,246,0.1);
            color: rgb(147,197,253);
            border: 1px solid rgba(59,130,246,0.2);
        }
        .complexity-baja  { color: rgb(52,211,153); }
        .complexity-media { color: rgb(251,191,36); }
        .complexity-alta  { color: rgb(248,113,113); }
    </style>
    <script>
    (function () {
        'use strict';

        var csrf         = document.querySelector('meta[name="csrf-token"]').content;
        var interpretBtn = document.getElementById('interpret-btn');
        var ideaInput    = document.getElementById('idea-input');
        var ideaCount    = document.getElementById('idea-count');
        var ideaHint     = document.getElementById('idea-hint');
        var errorBox     = document.getElementById('error-box');
        var errorMsg     = document.getElementById('error-message');
        var rawDetails   = document.getElementById('raw-details');
        var rawContent   = document.getElementById('raw-content');

        var phaseIdea   = document.getElementById('phase-idea');
        var phaseInterp = document.getElementById('phase-interpretation');
        var loadingOverlay = document.getElementById('loading-overlay');

        var currentStep = 1;
        var interpreting = null; // holds the interpretation data

        // ── Character counter ─────────────────────────────────────────────────
        ideaInput.addEventListener('input', function () {
            var len = this.value.length;
            ideaCount.textContent = len + ' / 2000';
            ideaHint.textContent  = len > 0 && len < 10 ? 'Mínimo 10 caracteres' : '';
        });

        // ── Interpret button ──────────────────────────────────────────────────
        interpretBtn.addEventListener('click', function () {
            var idea  = ideaInput.value.trim();
            var model = document.getElementById('model-select').value.trim();

            if (idea.length < 10) {
                showError('La idea debe tener al menos 10 caracteres.');
                return;
            }
            if (!model) {
                showError('El asistente no esta configurado correctamente. Contacta al administrador.');
                return;
            }

            startLoading();

            var fd = new FormData();
            fd.append('_token', csrf);
            fd.append('idea', idea);
            fd.append('modelo', model);

            fetch('{{ route("asistente-ia.interpretar") }}', {
                method: 'POST',
                body: fd,
            })
            .then(function (r) {
                if (r.status === 422) {
                    return r.json().then(function (d) {
                        var msgs = d.error || Object.values(d.errors || {}).flat().join(' ');
                        throw new Error(msgs || 'Datos inválidos.');
                    });
                }
                if (!r.ok) {
                    throw new Error('No se pudo generar la propuesta. Intenta nuevamente en unos segundos.');
                }
                return r.json();
            })
            .then(function (result) {
                stopLoading();
                if (!result.ok) {
                    showError(result.error || 'Error desconocido.', result.raw || null);
                    return;
                }
                interpreting = result.data;
                renderInterpretation(result.data);
                goToStep(2);
            })
            .catch(function (err) {
                stopLoading();
                showError(err.message || 'Error de red o conexión.');
            });
        });

        // ── Reinterpret button ────────────────────────────────────────────────
        document.getElementById('btn-reinterpret').addEventListener('click', function () {
            goToStep(1);
            phaseInterp.classList.add('hidden');
            phaseIdea.classList.remove('hidden');
            hideError();
        });

        // ── Mode selection ────────────────────────────────────────────────────
        window.selectMode = function (mode) {
            if (mode !== 'guided') {
                // Future modes — not yet implemented
                return;
            }
            var labels = { guided: 'Modo Guiado', automatic: 'Modo Automático', base_only: 'Solo Proyecto Base' };
            document.getElementById('mode-name-display').textContent = labels[mode] || mode;
            document.getElementById('mode-confirmation').classList.remove('hidden');

            // In a future phase, this would POST to save the draft and move to step 3
        };

        // ── Copy raw response ─────────────────────────────────────────────────
        window.copyRaw = function () {
            var text = rawContent.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    alert('Respuesta copiada al portapapeles.');
                });
            }
        };

        // ── Render interpretation ─────────────────────────────────────────────
        function renderInterpretation(data) {
            document.getElementById('interp-name').textContent = data.nombre_sugerido || '—';
            document.getElementById('interp-desc').textContent = data.descripcion_mejorada || '—';

            // Modules
            var modulesEl = document.getElementById('interp-modules');
            modulesEl.innerHTML = '';
            var mods = Array.isArray(data.modulos_detectados) ? data.modulos_detectados : [];
            mods.forEach(function (m) {
                var chip = document.createElement('span');
                chip.className = 'chip-module';
                chip.textContent = m;
                modulesEl.appendChild(chip);
            });

            // Complexity
            var comp = data.complejidad || 'media';
            var compEl = document.getElementById('interp-complexity');
            compEl.textContent = comp.charAt(0).toUpperCase() + comp.slice(1);
            compEl.className = 'text-[11px] font-semibold complexity-' + comp;

            // Duration
            document.getElementById('interp-duration').textContent =
                (data.duracion_semanas_estimada || 8) + ' semanas aprox.';

            // Type
            document.getElementById('interp-type').textContent = data.tipo_proyecto || 'web';

            // Technologies
            var techsEl = document.getElementById('interp-techs');
            techsEl.innerHTML = '';
            var techs = Array.isArray(data.tecnologias_sugeridas) ? data.tecnologias_sugeridas : [];
            if (techs.length === 0) {
                techsEl.innerHTML = '<span class="text-[11px] text-gray-600 italic">No detectadas</span>';
            } else {
                techs.forEach(function (t) {
                    var chip = document.createElement('span');
                    chip.className = 'chip-tech';
                    chip.textContent = t;
                    techsEl.appendChild(chip);
                });
            }
        }

        // ── Step navigation ───────────────────────────────────────────────────
        function goToStep(step) {
            currentStep = step;

            // Update all step circles and lines
            for (var i = 1; i <= 7; i++) {
                var circle = document.getElementById('step-circle-' + i);
                var line   = document.getElementById('step-line-' + i);

                if (!circle) continue;

                circle.classList.remove('active', 'done');

                if (i < step) {
                    circle.classList.add('done');
                    if (line) { line.classList.remove('locked'); line.classList.add('done'); }
                } else if (i === step) {
                    circle.classList.add('active');
                    if (line) { line.classList.remove('locked', 'done'); }
                } else {
                    circle.classList.add('locked');
                    if (line) { line.classList.remove('done'); line.classList.add('locked'); }
                }
            }
        }

        // ── Loading state ─────────────────────────────────────────────────────
        function startLoading() {
            interpretBtn.disabled = true;
            interpretBtn.querySelector('.btn-icon').textContent = 'hourglass_top';
            interpretBtn.querySelector('.btn-label').textContent = 'Analizando…';
            phaseIdea.classList.add('hidden');
            loadingOverlay.classList.remove('hidden');
            hideError();
        }

        function stopLoading() {
            interpretBtn.disabled = false;
            interpretBtn.querySelector('.btn-icon').textContent = 'auto_awesome';
            interpretBtn.querySelector('.btn-label').textContent = 'Analizar con IA';
            loadingOverlay.classList.add('hidden');
        }

        // ── Error helpers ─────────────────────────────────────────────────────
        function showError(msg, raw) {
            phaseIdea.classList.remove('hidden');
            loadingOverlay.classList.add('hidden');
            errorBox.classList.remove('hidden');
            errorMsg.textContent = msg;
            if (raw) {
                rawDetails.classList.remove('hidden');
                rawContent.textContent = typeof raw === 'string' ? raw : JSON.stringify(raw, null, 2);
            } else {
                rawDetails.classList.add('hidden');
            }
        }

        function hideError() {
            errorBox.classList.add('hidden');
        }

    })();
    </script>
    @endpush

</x-app-layout>
