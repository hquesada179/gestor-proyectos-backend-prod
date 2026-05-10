<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-transparent bg-secondary-container px-4 py-2 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-secondary-container/20 transition ease-in-out duration-150 hover:opacity-90 active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-950']) }}>
    {{ $slot }}
</button>
