@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border border-slate-600 bg-slate-800 text-white placeholder:text-slate-500 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-900/70 disabled:text-slate-500']) }}>
