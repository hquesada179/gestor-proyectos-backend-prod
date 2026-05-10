<div class="relative flex-shrink-0"
     x-data="chatPanel()"
     data-projects-url="{{ route('mensajes.projects') }}"
     data-project-base-url="{{ url('/mensajes/proyectos') }}"
     data-current-user-id="{{ Auth::id() }}"
     @click.outside="if (open) { open = false; stopPoll(); }"
     @keydown.escape.window="if (open) { open = false; stopPoll(); }">

    <style>
        .project-chat-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
        }
        .project-chat-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .project-chat-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .project-chat-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }
        .project-chat-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.55);
        }
    </style>

    <button type="button"
            @click="toggleOpen()"
            :aria-expanded="open.toString()"
            class="relative flex items-center justify-center w-9 h-9 rounded-xl hover:bg-white/5 transition-all duration-150 outline-none focus:ring-2 focus:ring-blue-500/40"
            :class="open ? 'bg-white/10 text-white' : ''"
            title="Chat del proyecto">
        <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">forum</span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false; stopPoll()"
         class="fixed inset-0 z-[60] bg-black/20"
         style="display:none;"></div>

    <div id="projectChatPanel"
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         class="absolute right-0 top-full mt-3 z-[70] w-[390px] max-w-[calc(100vw-1rem)] h-[620px] max-h-[80vh]
                rounded-2xl overflow-hidden bg-slate-900 border border-slate-700/80 shadow-2xl shadow-black/80 flex flex-col backdrop-blur-xl"
         style="display:none; height:min(620px,85vh); background:rgba(15,23,42,0.98); box-shadow:0 0 0 1px rgba(148,163,184,0.08), 0 24px 70px rgba(0,0,0,0.78);">

        <div class="px-4 py-3 border-b border-slate-700/80 flex items-center justify-between gap-3 flex-shrink-0 bg-slate-900">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-indigo-600/25 border border-indigo-500/40 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-indigo-400" style="font-size:18px;">forum</span>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white truncate"
                       x-text="view === 'chat' && selProject ? selProject.nombre : 'Chat interno'"></p>
                    <p class="text-[11px] text-gray-500 truncate" x-text="chatSubtitle()"></p>
                </div>
            </div>

            <button type="button"
                    @click="open = false; stopPoll()"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            </button>
        </div>

        <div x-show="view === 'projects'" class="flex-1 min-h-0 flex flex-col bg-slate-950/90 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex-shrink-0 bg-slate-900">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none" style="font-size:16px;">search</span>
                    <input type="text"
                           x-model="search"
                           placeholder="Buscar proyecto"
                           autocomplete="off"
                           spellcheck="false"
                           class="w-full appearance-none rounded-xl border border-slate-700 !bg-slate-900 pl-9 pr-3 py-2.5 text-sm !text-slate-100 placeholder:text-slate-400 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50"
                           style="background:#0f172a;color:#f8fafc;caret-color:#a5b4fc;">
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-hidden bg-slate-950/90">
                <template x-if="loading">
                    <div class="h-full flex items-center justify-center">
                        <div class="w-6 h-6 rounded-full border-2 border-indigo-400/30 border-t-indigo-400" style="animation:spin 800ms linear infinite;"></div>
                    </div>
                </template>

                <template x-if="!loading && projects.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-center px-8">
                        <span class="material-symbols-outlined text-gray-700 mb-3" style="font-size:40px;">forum</span>
                        <p class="text-sm font-semibold text-gray-300">Sin proyectos disponibles</p>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            Cuando seas propietario o miembro activo de un proyecto, su chat aparecera aqui.
                        </p>
                    </div>
                </template>

                <template x-if="!loading && projects.length > 0 && filteredProjects.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-center px-6">
                        <span class="material-symbols-outlined text-gray-700 mb-2" style="font-size:34px;">search_off</span>
                        <p class="text-sm text-gray-400">No hay proyectos para esa busqueda.</p>
                    </div>
                </template>

                <template x-if="!loading && filteredProjects.length > 0">
                    <div class="h-full min-h-0 flex flex-col">
                        <p class="px-5 pt-4 pb-2 text-[10px] uppercase tracking-widest text-gray-600 font-bold flex-shrink-0">
                            Selecciona un proyecto
                        </p>

                        <div class="project-list project-chat-scrollbar flex-1 min-h-0 overflow-y-scroll overscroll-contain px-3 pb-3 pr-2 space-y-1"
                             style="max-height:calc(min(620px,85vh) - 178px);">
                            <template x-for="project in filteredProjects" :key="project.id">
                                <button type="button"
                                        @click="openChat(project)"
                                        class="w-full cursor-pointer text-left px-3 py-2.5 rounded-xl hover:bg-slate-800/70 transition-all flex items-center gap-3 border border-transparent hover:border-slate-700/70">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-black flex-shrink-0"
                                         :style="'background:' + projBg(project.id)"
                                         x-text="project.nombre ? project.nombre.substring(0, 2).toUpperCase() : 'PR'"></div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-semibold text-white truncate leading-5" x-text="project.nombre"></p>
                                            <span class="text-[10px] text-slate-500 flex-shrink-0 pt-0.5"
                                                  x-show="project.last_message"
                                                  x-text="project.last_message ? project.last_message.time : ''"></span>
                                        </div>

                                        <p class="text-xs text-slate-500 truncate mt-0.5"
                                           x-text="project.last_message ? ((project.last_message.is_mine ? 'Tu: ' : project.last_message.user_name + ': ') + project.last_message.text) : 'Sin mensajes todavia'"></p>

                                        <div class="flex items-center mt-1.5" x-show="project.members && project.members.length">
                                            <template x-for="member in (project.members || []).slice(0, 4)" :key="member.id">
                                                <div class="w-5 h-5 -ml-1 first:ml-0 rounded-full border border-slate-950 overflow-hidden bg-slate-700 flex items-center justify-center text-[9px] font-bold text-white"
                                                     :title="member.name">
                                                    <img x-show="member.photo" :src="member.photo" alt="" class="w-full h-full object-cover">
                                                    <span x-show="!member.photo" x-text="member.initial"></span>
                                                </div>
                                            </template>
                                            <span class="ml-2 text-[10px] text-slate-500"
                                                  x-show="project.members.length > 4"
                                                  x-text="'+' + (project.members.length - 4)"></span>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div x-show="view === 'chat'" class="flex-1 min-h-0 flex flex-col bg-slate-950/95">
            <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between gap-3 flex-shrink-0 bg-slate-900">
                <button type="button"
                        @click="backToList()"
                        class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
                    Proyectos
                </button>
                <span class="text-[11px] text-slate-500 truncate" x-show="selProject" x-text="selProject ? selProject.nombre : ''"></span>
            </div>

            <div class="px-4 py-3 border-b border-slate-800 bg-slate-900 flex-shrink-0 space-y-3">
                <div class="inline-flex rounded-full border border-slate-700 bg-slate-950 p-1">
                    <button type="button"
                            @click="switchConversation('general')"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                            :class="conversationMode === 'general'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/40'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                        General
                    </button>
                    <button type="button"
                            @click="switchConversation('private')"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                            :class="conversationMode === 'private'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/40'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                        Privado
                    </button>
                </div>

                <div x-show="conversationMode === 'private'" class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold">
                        Integrante
                    </label>
                    <select x-model.number="selectedReceiverId"
                            @change="selectPrivateMember()"
                            class="dark-form-select w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none focus:border-indigo-500/70">
                        <option value="">Selecciona un integrante</option>
                        <template x-for="member in privateMembers" :key="member.id">
                            <option :value="member.id" x-text="member.name"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-slate-500" x-show="privateMembers.length === 0">
                        No hay otros integrantes activos para chat privado.
                    </p>
                </div>
            </div>

            <div x-ref="msgBody"
                 class="flex-1 min-h-0 overflow-y-auto px-4 py-4 space-y-4 bg-slate-950/90">
                <template x-if="loading">
                    <div class="h-full flex items-center justify-center">
                        <div class="w-6 h-6 rounded-full border-2 border-indigo-400/30 border-t-indigo-400" style="animation:spin 800ms linear infinite;"></div>
                    </div>
                </template>

                <template x-if="!loading && messages.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-center px-8">
                        <span class="material-symbols-outlined text-gray-700 mb-3" style="font-size:40px;">chat_bubble</span>
                        <p class="text-sm font-semibold text-gray-300" x-text="emptyTitle()"></p>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            <span x-text="emptyText()"></span>
                        </p>
                    </div>
                </template>

                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex gap-2" :class="msg.is_mine ? 'justify-end' : 'justify-start'">
                        <template x-if="!msg.is_mine">
                            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-[11px] font-black text-white flex-shrink-0 mt-5"
                                 :style="'background:' + avBg(msg.user_id)">
                                <img x-show="msg.user_photo" :src="msg.user_photo" alt="" class="w-full h-full object-cover">
                                <span x-show="!msg.user_photo" x-text="msg.user_initial"></span>
                            </div>
                        </template>

                        <div class="max-w-[76%] flex flex-col" :class="msg.is_mine ? 'items-end' : 'items-start'">
                            <div class="flex items-center gap-2 mb-1"
                                 :class="msg.is_mine ? 'flex-row-reverse' : ''">
                                <span class="text-[11px] font-semibold text-gray-300 truncate max-w-[150px]" x-text="msg.user_name"></span>
                                <span class="text-[10px] text-gray-600" x-text="msg.time"></span>
                            </div>
                            <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap break-words border"
                                 :class="msg.is_mine
                                    ? 'rounded-br-md bg-indigo-600 border-indigo-500/60 text-white shadow-lg shadow-indigo-950/30'
                                    : 'rounded-bl-md bg-slate-800 border-slate-700/80 text-slate-100 shadow-lg shadow-black/20'"
                                 x-text="msg.message"></div>
                        </div>

                        <template x-if="msg.is_mine">
                            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-[11px] font-black text-white flex-shrink-0 mt-5"
                                 :style="'background:' + avBg(msg.user_id)">
                                <img x-show="msg.user_photo" :src="msg.user_photo" alt="" class="w-full h-full object-cover">
                                <span x-show="!msg.user_photo" x-text="msg.user_initial"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-4 py-3 border-t border-slate-700/80 flex-shrink-0 bg-slate-900">
                <p class="mb-2 text-xs text-red-400" x-show="errMsg" x-text="errMsg"></p>

                <form class="flex items-end gap-2" @submit.prevent="sendMsg()">
                    <textarea x-model="newMsg"
                              rows="1"
                              :placeholder="inputPlaceholder()"
                              @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMsg(); }"
                              class="flex-1 min-h-[42px] max-h-28 resize-none rounded-xl border border-slate-700 bg-slate-800 px-3 py-2.5 text-sm !text-white placeholder:text-slate-400 outline-none focus:border-indigo-500/70"
                              style="background:#1e293b;color:#f8fafc;caret-color:#a5b4fc;"></textarea>

                    <button type="submit"
                            :disabled="sending || !newMsg.trim() || (conversationMode === 'private' && !selectedReceiverId)"
                            class="w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center transition-all flex-shrink-0 shadow-lg shadow-indigo-950/40">
                        <span class="material-symbols-outlined" style="font-size:18px;" x-show="!sending">send</span>
                        <span class="material-symbols-outlined" style="font-size:18px; animation:spin 800ms linear infinite;" x-show="sending">progress_activity</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
