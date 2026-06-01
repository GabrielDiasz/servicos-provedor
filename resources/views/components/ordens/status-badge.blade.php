@props(['ordem'])

<span {{ $attributes->merge(['class' => $ordem->status_classes]) }}>
    {{ $ordem->status_label }}
</span>
