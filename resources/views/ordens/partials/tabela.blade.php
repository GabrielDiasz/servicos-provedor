<div class="overflow-x-auto rounded-2xl border border-[#3a3a40] bg-[#232326] shadow-[0_24px_60px_rgba(0,0,0,0.18)]">
    <table class="min-w-full divide-y divide-[#35353a] text-sm text-[#e4e4e7]">
        <thead class="bg-[#1f1f22]">
            <tr>
                <th class="w-16 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    #
                </th>

                <th class="w-[26rem] px-4 py-3 text-left text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Cliente
                </th>

                <th class="px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Serviço
                </th>

                <th class="w-44 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Bairro
                </th>

                <th class="w-52 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Técnico
                </th>

                <th class="w-32 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Data
                </th>

                <th class="w-36 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Prioridade
                </th>

                <th class="w-40 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Status
                </th>

                <th class="w-32 px-4 py-3 text-center text-[11px] font-medium tracking-[0.02em] text-[#a1a1aa]">
                    Ações
                </th>
            </tr>
        </thead>

        <tbody class="divide-y divide-[#303036]">
            @forelse($ordens as $ordem)
                <x-ordens.ordem-row :ordem="$ordem" :tecnico-options="$tecnicoOptions" />
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-sm text-[#a1a1aa]">
                        Nenhuma ordem de serviço encontrada.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
