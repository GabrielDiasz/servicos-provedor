@props(['ordem'])

<span {{ $attributes->merge(['class' => $ordem->turno_classes]) }}>
    {{ $ordem->turno_label }}
</span>
