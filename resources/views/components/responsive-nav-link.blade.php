@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md border-l-4 border-[#ff5a00] px-4 py-2.5 text-start text-base font-semibold text-white bg-white/10 focus:outline-none focus:bg-white/10 transition duration-150 ease-in-out'
            : 'block w-full rounded-md border-l-4 border-transparent px-4 py-2.5 text-start text-base font-medium text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
