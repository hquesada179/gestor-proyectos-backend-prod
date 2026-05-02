<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary-container" style="font-size: 24px;">smart_toy</span>
            <span class="text-sm font-medium text-gray-400">Crear proyecto con IA</span>
        </div>
    </x-slot>

    <div class="p-4 sm:p-8 max-w-[1000px] mx-auto h-[calc(100vh-100px)] flex flex-col">
        {{-- Chat Container --}}
        <div class="glass-panel flex-1 rounded-2xl flex flex-col overflow-hidden relative shadow-2xl border border-white/5">
            
            {{-- Header Area --}}
            <div class="p-4 border-b border-white/10 bg-surface/50 backdrop-blur-md flex justify-between items-center z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center border border-secondary-container/30">
                        <span class="material-symbols-outlined text-secondary-container text-xl">auto_awesome</span>
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-sm sm:text-base">Asistente de Proyectos</h2>
                        <p class="text-xs text-on-surface-variant">Siempre activo</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="p-2 rounded-xl hover:bg-white/5 text-gray-400 hover:text-white transition-colors" title="Borrar chat">
                        <span class="material-symbols-outlined" style="font-size: 20px;">delete_sweep</span>
                    </button>
                    <button type="button" class="p-2 rounded-xl hover:bg-white/5 text-gray-400 hover:text-white transition-colors" title="Opciones">
                        <span class="material-symbols-outlined" style="font-size: 20px;">more_vert</span>
                    </button>
                </div>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 flex flex-col" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                
                {{-- Date Separator --}}
                <div class="flex justify-center">
                    <span class="text-xs font-medium text-gray-500 bg-white/5 px-3 py-1 rounded-full backdrop-blur-sm">
                        Hoy
                    </span>
                </div>

                {{-- AI Message --}}
                <div class="flex items-end gap-3 group">
                    <div class="w-8 h-8 rounded-full bg-secondary-container/20 flex items-center justify-center flex-shrink-0 border border-secondary-container/30 mb-1">
                        <span class="material-symbols-outlined text-secondary-container text-[16px]">smart_toy</span>
                    </div>
                    <div class="bg-surface/80 border border-white/10 rounded-2xl rounded-bl-sm p-4 max-w-[85%] sm:max-w-[75%] shadow-sm relative">
                        <p class="text-sm text-gray-200 leading-relaxed">
                            ¡Hola! 👋 Soy tu asistente de IA. Cuéntame un poco sobre el proyecto que tienes en mente. ¿Qué problema buscas resolver o qué tipo de aplicación quieres construir?
                        </p>
                    </div>
                </div>

                {{-- User Message --}}
                <div class="flex items-end gap-3 flex-row-reverse group">
                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/20 mb-1">
                        <span class="material-symbols-outlined text-white text-[16px]">person</span>
                    </div>
                    <div class="bg-secondary-container rounded-2xl rounded-br-sm p-4 max-w-[85%] sm:max-w-[75%] shadow-md relative">
                        <p class="text-sm text-white leading-relaxed">
                            Quiero crear una aplicación web para gestionar las tareas de mi equipo de marketing. Necesito algo con tableros Kanban, roles y notificaciones.
                        </p>
                    </div>
                </div>

                {{-- AI Typing Indicator (Animated) --}}
                <div class="flex items-end gap-3">
                    <div class="w-8 h-8 rounded-full bg-secondary-container/20 flex items-center justify-center flex-shrink-0 border border-secondary-container/30 mb-1">
                        <span class="material-symbols-outlined text-secondary-container text-[16px]">smart_toy</span>
                    </div>
                    <div class="bg-surface/80 border border-white/10 rounded-2xl rounded-bl-sm p-4 py-5 shadow-sm flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>

            {{-- Input Area --}}
            <div class="p-3 sm:p-4 bg-surface/50 border-t border-white/10 backdrop-blur-md z-10">
                <form class="relative flex items-end gap-2 max-w-4xl mx-auto">
                    <button type="button" class="p-2 sm:p-3 text-gray-400 hover:text-white transition-colors flex-shrink-0" title="Adjuntar archivo">
                        <span class="material-symbols-outlined">attach_file</span>
                    </button>
                    
                    <div class="flex-1 bg-black/20 border border-white/10 rounded-xl overflow-hidden focus-within:border-secondary-container/50 focus-within:bg-black/30 transition-all duration-300">
                        <textarea 
                            rows="1" 
                            class="w-full bg-transparent text-white text-sm p-3 sm:p-4 focus:outline-none focus:ring-0 border-none resize-none max-h-32 placeholder-gray-500 block"
                            placeholder="Describe tu proyecto aquí..."
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="p-3 sm:p-4 bg-secondary-container text-white rounded-xl hover:opacity-90 active:scale-95 transition-all flex-shrink-0 shadow-lg shadow-secondary-container/20 flex items-center justify-center group" title="Enviar mensaje">
                        <span class="material-symbols-outlined text-lg group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">send</span>
                    </button>
                </form>
                
                <div class="text-center mt-2 pb-1">
                    <p class="text-[10px] text-gray-500">La IA puede cometer errores. Considera verificar la información importante antes de generar el proyecto.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
