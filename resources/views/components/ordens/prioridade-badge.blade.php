@props(['ordem'])

<span {{ $attributes->merge(['class' => $ordem->prioridade_classes]) }}>
    {{ $ordem->prioridade_label }}
</span>
