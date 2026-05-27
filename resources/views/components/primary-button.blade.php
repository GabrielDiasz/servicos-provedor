<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#ff7a00] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#e96f00] focus:bg-[#e96f00] active:bg-[#c95f00] focus:outline-none focus:ring-2 focus:ring-[#ff7a00] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
