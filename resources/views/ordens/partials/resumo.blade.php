<div class="mb-0 flex flex-nowrap items-stretch gap-2.5 overflow-x-auto pb-0">
    @foreach ($resumoCards as $card)
        <x-ordens.resumo-card :title="$card['title']" :value="$card['value']" :tone="$card['tone']" />
    @endforeach
</div>
