<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0c5f3a] dark:text-emerald-400">Acesso</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">Usuários</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Controle perfis com uma visão objetiva de acesso e volume de OS.
                </p>
            </div>

            <a href="{{ route('usuarios.create') }}" class="app-btn-primary">
                + Novo Usuário
            </a>
        </div>

        <div class="app-surface overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-[#f5fbf4] dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-300">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-300">E-mail</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-300">Perfil</th>
                                                <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($usuarios as $usuario)
                        <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                            <td class="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">
                                {{ $usuario->name }}
                                @if($usuario->id === auth()->id())
                                    <span class="ml-1 text-xs text-slate-400 dark:text-slate-500">(você)</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $usuario->email }}</td>
                            <td class="px-4 py-3">
                                @if($usuario->perfil === 'admin')
                                    <span class="inline-flex rounded-full bg-violet-100 px-2 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">Admin</span>
                                @else
                                    <span class="inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">Atendente</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('usuarios.edit', $usuario) }}"
                                       class="inline-flex h-8 items-center rounded-full border border-[#b9d9c2] bg-white px-3 text-xs font-medium text-[#064b31] shadow-sm transition hover:border-[#ff7a00] hover:text-[#ff7a00] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[#ff7a00] dark:hover:text-[#ffb366]">
                                        Editar
                                    </a>

                                    @if($usuario->id !== auth()->id())
                                        <form method="POST"
                                              action="{{ route('usuarios.destroy', $usuario) }}"
                                              onsubmit="return confirm('Deseja realmente excluir este usuário?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex h-8 items-center rounded-full border border-red-200 bg-white px-3 text-xs font-medium text-red-600 shadow-sm transition hover:bg-red-50 dark:border-red-900/60 dark:bg-slate-900 dark:hover:bg-red-950/30">
                                                Excluir
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $usuarios->links() }}</div>
    </div>
</x-app-layout>




