@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-sm border-b-2 border-[#ff5a00] px-2.5 py-1.5 text-sm font-semibold leading-5 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-sm border-b-2 border-transparent px-2.5 py-1.5 text-sm font-medium leading-5 text-slate-300 transition duration-150 ease-in-out hover:border-[#ff5a00]/60 hover:text-white focus:outline-none focus:border-[#ff5a00]/60 focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
