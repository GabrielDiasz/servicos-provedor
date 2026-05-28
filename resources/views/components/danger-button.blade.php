<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-red-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-800']) }}>
    {{ $slot }}
</button>
