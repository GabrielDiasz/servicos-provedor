<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-[#ff7a00] px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-[#e96f00] focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 active:bg-[#c95f00] dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>
