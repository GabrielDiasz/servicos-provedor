@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md border-b-2 border-[#ff7a00] px-2.5 py-1.5 text-sm font-semibold leading-5 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-md border-b-2 border-transparent px-2.5 py-1.5 text-sm font-medium leading-5 text-green-50/80 transition duration-150 ease-in-out hover:border-white/20 hover:text-white focus:outline-none focus:border-white/20 focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
