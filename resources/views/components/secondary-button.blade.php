<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-200 shadow-sm transition ease-in-out duration-150 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
