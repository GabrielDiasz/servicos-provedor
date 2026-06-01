<div x-show="deleteModalOpen" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div x-show="deleteModalOpen" x-transition x-on:click.outside="closeDeleteModal()"
        class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:border dark:border-slate-700 dark:bg-slate-900 dark:shadow-2xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Excluir ordem de serviço?</h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">
            Esta ação vai remover definitivamente <span class="font-semibold" x-text="deleteLabel"></span>.
        </p>

        <form method="POST" x-bind:action="deleteAction" class="mt-6 flex justify-end gap-3"
            x-on:submit="$dispatch('busy-start', { label: 'Excluindo OS...' })">
            @csrf
            @method('DELETE')
            <button type="button" x-on:click="closeDeleteModal()"
                class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                Cancelar
            </button>
            <button type="submit"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                Excluir
            </button>
        </form>
    </div>
</div>
