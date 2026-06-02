<x-app-layout>
    <div x-data="ordensPage()" class="mx-auto max-w-[92rem] px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-4">
            @include('ordens.partials.filtros')
            @include('ordens.partials.resumo')
            @include('ordens.partials.tabela')
        </div>

        <div class="mt-5 flex justify-end">
            {{ $ordens->links() }}
        </div>

        @include('ordens.modals.whatsapp')
        @include('ordens.modals.delete')
    </div>
</x-app-layout>
