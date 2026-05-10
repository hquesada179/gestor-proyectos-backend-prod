<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-red-500/40 bg-red-600 px-4 py-2 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-red-950/30 transition ease-in-out duration-150 hover:bg-red-500 active:scale-95 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-slate-950']) }}>
    {{ $slot }}
</button>
