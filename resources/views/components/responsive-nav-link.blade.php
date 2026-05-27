@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#ff7a00] text-start text-base font-semibold text-white bg-[#0c5f3a] focus:outline-none focus:text-white focus:bg-[#0c5f3a] focus:border-[#ff7a00] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-green-50/80 hover:text-white hover:bg-[#0c5f3a] hover:border-[#ff7a00]/70 focus:outline-none focus:text-white focus:bg-[#0c5f3a] focus:border-[#ff7a00] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
