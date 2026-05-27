<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-[#b9d9c2] rounded-md font-semibold text-xs text-[#064b31] uppercase tracking-widest shadow-sm hover:bg-[#f5fbf4] focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
