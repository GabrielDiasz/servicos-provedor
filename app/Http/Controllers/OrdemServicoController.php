<?php

namespace App\Http\Controllers;

use App\Jobs\CreateSgpOccurrenceJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Services\SgpService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrdemServicoController extends Controller
{
    public function index(Request $request)
    {
        $dataMarcacao = $request->filled('data_marcacao')
            ? $request->data_marcacao
            : now()->toDateString();

        $resumoDia = OrdemServico::query()
            ->where('data_marcacao', $dataMarcacao)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'passada' THEN 1 ELSE 0 END) as total_passadas")
            ->selectRaw("SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as total_concluidas")
            ->first();

        $query = OrdemServico::query()
            ->select([
                'id',
                'cliente_nome',
                'cliente_telefone',
                'sgp_cliente_link',
                'tipo_servico',
                'bairro',
                'tecnico_id',
                'data_marcacao',
                'turno',
                'prioridade',
                'status',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tecnico_id')) {
            $query->where('tecnico_id', $request->tecnico_id);
        }

        if ($request->filled('tipo_servico')) {
            $query->where('tipo_servico', $request->tipo_servico);
        }

        $query->where('data_marcacao', $dataMarcacao);

        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->prioridade);
        }

        $ordens = $query->orderByRaw("FIELD(prioridade, 'urgente', 'alta', 'normal')")
            ->orderBy('data_marcacao', 'asc')
            ->orderByRaw("FIELD(turno, 'manha', 'tarde')")
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $tecnicos = $this->tecnicosAtivosOrdenados();
        $tecnicosDisponiveis = $this->tecnicosDisponiveisOrdenados();
        $tecnicoFilterOptions = $tecnicos->pluck('nome', 'id')->all();
        $tecnicoOptions = $tecnicosDisponiveis
            ->mapWithKeys(fn ($tecnico) => [
                $tecnico->id => $tecnico->nome.($tecnico->ativo ? '' : ' (Inativo)'),
            ])
            ->all();

        return view('ordens.index', [
            'ordens' => $ordens,
            'dataMarcacao' => $dataMarcacao,
            'resumoCards' => $this->resumoCards($resumoDia),
            'tecnicoFilterOptions' => $tecnicoFilterOptions,
            'tecnicoOptions' => $tecnicoOptions,
            'tipoOptions' => OrdemServico::TIPOS,
            'prioridadeOptions' => OrdemServico::PRIORIDADES,
            'statusFilterOptions' => OrdemServico::STATUS,
        ]);
    }

    public function create()
    {
        $tecnicoOptions = $this->tecnicosAtivosOrdenados()->pluck('nome', 'id')->all();

        return view('ordens.create', [
            'tecnicoOptions' => $tecnicoOptions,
            'tipoOptions' => OrdemServico::TIPOS,
            'turnoOptions' => OrdemServico::TURNOS,
            'prioridadeOptions' => OrdemServico::PRIORIDADES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_nome' => 'required|string|max:255',
            'cliente_telefone' => 'required|string|max:20',
            'sgp_cliente_link' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:255',
            'tipo_servico' => 'required|in:'.implode(',', array_keys(OrdemServico::TIPOS)),
            'turno' => 'required|in:manha,tarde',
            'prioridade' => 'required|in:normal,alta,urgente',
            'data_marcacao' => 'required|date',
            'tecnico_id' => 'nullable|exists:tecnicos,id',
            'observacao' => 'required_if:tipo_servico,upgrade|nullable|string|max:1000',
        ], [
            'observacao.required_if' => 'A observação é obrigatória para o serviço Upgrade.',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pendente';
        $validated['whatsapp_send_status'] = null;
        $validated['whatsapp_send_error'] = null;
        $validated['whatsapp_sent_at'] = null;
        $validated['whatsapp_sent_for_sgp_ocorrencia_numero'] = null;
        $validated['sgp_sync_status'] = null;
        $validated['sgp_sync_error'] = null;
        $validated['sgp_ocorrencia_sgp_id'] = null;

        $ordem = OrdemServico::create($validated);

        $payload = [
            'success' => true,
            'message' => 'Ocorrência criada com sucesso.',
            'ordem_id' => $ordem->id,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('ordens.index')
            ->with('success', $payload['message']);
    }

    public function show(OrdemServico $ordem)
    {
        $ordem->load(['tecnico', 'atendente']);
        $ctoInfo = $this->ctoInfo($ordem);

        return view('ordens.show', compact('ordem', 'ctoInfo'));
    }

    public function edit(OrdemServico $ordem)
    {
        $tecnicoOptions = $this->tecnicosAtivosOrdenados()->pluck('nome', 'id')->all();

        return view('ordens.edit', [
            'ordem' => $ordem,
            'tecnicoOptions' => $tecnicoOptions,
            'tipoOptions' => OrdemServico::TIPOS,
            'turnoOptions' => OrdemServico::TURNOS,
            'prioridadeOptions' => OrdemServico::PRIORIDADES,
        ]);
    }

    public function update(Request $request, OrdemServico $ordem, SgpService $sgp)
    {
        $validated = $request->validate([
            'cliente_nome' => 'required|string|max:255',
            'cliente_telefone' => 'required|string|max:20',
            'sgp_cliente_link' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:255',
            'tipo_servico' => 'required|in:'.implode(',', array_keys(OrdemServico::TIPOS)),
            'turno' => 'required|in:manha,tarde',
            'prioridade' => 'required|in:normal,alta,urgente',
            'status' => 'required|in:'.implode(',', array_keys($ordem->editable_status_options)),
            'data_marcacao' => 'required|date',
            'tecnico_id' => 'nullable|exists:tecnicos,id',
            'observacao' => 'required_if:tipo_servico,upgrade|nullable|string|max:1000',
        ], [
            'observacao.required_if' => 'A observação é obrigatória para o serviço Upgrade.',
        ]);

        $ordem->update($this->preencherDadosSgp($validated, $sgp, $ordem));

        return redirect()->route('ordens.index')
            ->with('success', 'Ordem de serviço atualizada com sucesso!');
    }

    public function enviarWhatsApp(Request $request, OrdemServico $ordem)
    {
        $abrirOcorrenciaSgp = $request->boolean('abrir_ocorrencia_sgp');

        if ($abrirOcorrenciaSgp) {
            $ordem->forceFill([
                'sgp_sync_status' => $ordem->sgp_sync_status === 'sincronizado' ? $ordem->sgp_sync_status : 'queued',
                'sgp_sync_error' => null,
                'whatsapp_send_status' => 'queued',
                'whatsapp_send_error' => null,
                'whatsapp_sent_at' => null,
                'whatsapp_sent_for_sgp_ocorrencia_numero' => null,
            ])->save();

            CreateSgpOccurrenceJob::dispatch(
                $ordem->id,
                Auth::user()?->name,
                Auth::user()?->email,
                true,
                $ordem->tecnico_id
            )->afterCommit();

            $message = 'Ocorrência e envio do WhatsApp foram enfileirados.';
        } else {
            $ordem->forceFill([
                'whatsapp_send_status' => 'queued',
                'whatsapp_send_error' => null,
                'whatsapp_sent_at' => null,
                'whatsapp_sent_for_sgp_ocorrencia_numero' => null,
            ])->save();

            SendWhatsappMessageJob::dispatch(
                $ordem->id,
                Auth::user()?->name,
                Auth::user()?->email,
                $ordem->tecnico_id
            )->afterCommit();

            $message = 'Envio do WhatsApp enfileirado com sucesso.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'ordem_id' => $ordem->id,
            ]);
        }

        return back()->with('success', $message);
    }

    public function atualizarStatus(Request $request, OrdemServico $ordem)
    {
        if ($request->input('status') === 'passada' && $ordem->status !== 'passada') {
            return back()->with('error', 'O status Passada só é definido após envio pelo WhatsApp.');
        }

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys($ordem->editable_status_options)),
        ]);

        $ordem->update($validated);

        return back()->with('success', 'Status da OS #'.$ordem->id.' atualizado.');
    }

    public function atualizarTecnico(Request $request, OrdemServico $ordem)
    {
        $validated = $request->validate([
            'tecnico_id' => 'nullable|exists:tecnicos,id',
        ]);

        $ordem->update($validated);

        return back()->with('success', 'Técnico da OS #'.$ordem->id.' atualizado.');
    }

    public function buscarSgp(Request $request, SgpService $sgp)
    {
        $validated = $request->validate([
            'sgp_cliente_link' => 'required|string|max:255',
        ]);

        $dados = $sgp->consultarClientePorLink($validated['sgp_cliente_link']);

        if (! $dados) {
            return response()->json([
                'message' => 'Cliente não encontrado no SGP para o link informado.',
            ], 404);
        }

        return response()->json($dados);
    }

    private function preencherDadosSgp(array $validated, SgpService $sgp, ?OrdemServico $ordem = null): array
    {
        if (empty($validated['sgp_cliente_link'])) {
            return $ordem ? array_merge($validated, $this->camposSgpVazios()) : $validated;
        }

        if (
            $ordem
            && $validated['sgp_cliente_link'] === $ordem->sgp_cliente_link
            && filled($ordem->sgp_cliente_id)
        ) {
            return $validated;
        }

        $dadosSgp = $sgp->consultarClientePorLink($validated['sgp_cliente_link']);

        if (! $dadosSgp) {
            return $ordem ? array_merge($validated, $this->camposSgpVazios()) : $validated;
        }

        return array_merge($this->camposSgpVazios(), $dadosSgp, $validated, [
            'sgp_cliente_link' => $validated['sgp_cliente_link'],
        ]);
    }

    private function camposSgpVazios(): array
    {
        return [
            'sgp_cliente_id' => null,
            'sgp_contrato_id' => null,
            'sgp_cpf_cnpj' => null,
            'sgp_data_nascimento' => null,
            'sgp_plano' => null,
            'sgp_vencimento' => null,
            'sgp_pppoe_login' => null,
            'sgp_pppoe_senha' => null,
            'sgp_endereco' => null,
            'sgp_dados' => null,
            'sgp_ocorrencia_numero' => null,
            'sgp_ocorrencia_sgp_id' => null,
            'sgp_os_numero' => null,
            'sgp_sync_status' => null,
            'sgp_sync_error' => null,
        ];
    }

    private function garantirSincronizacaoSgp(OrdemServico $ordem, SgpService $sgp, ?string $usuarioResponsavel = null, ?string $usuarioEmail = null): array
    {
        if (
            $ordem->sgp_sync_status === 'sincronizado'
            && filled($ordem->sgp_ocorrencia_numero)
            && filled($ordem->sgp_os_numero)
        ) {
            return [
                'status' => 'synced',
                'message' => 'Ocorrência e OS já estavam sincronizadas no SGP.',
                'ocorrencia_numero' => $ordem->sgp_ocorrencia_numero,
                'os_numero' => $ordem->sgp_os_numero,
            ];
        }

        $sincronizacao = $sgp->sincronizarOcorrenciaEOrdemServico($ordem, $usuarioResponsavel, $usuarioEmail);

        if ($sincronizacao['status'] === 'synced') {
            $ordem->update([
                'sgp_ocorrencia_numero' => $sincronizacao['ocorrencia_numero'] ?? null,
                'sgp_ocorrencia_sgp_id' => $ordem->sgp_ocorrencia_sgp_id,
                'sgp_os_numero' => $sincronizacao['os_numero'] ?? null,
                'sgp_sync_status' => 'sincronizado',
                'sgp_sync_error' => null,
            ]);

            return $sincronizacao;
        }

        $ordem->update([
            'sgp_sync_status' => $sincronizacao['status'] === 'skipped' ? 'ignorado' : 'erro',
            'sgp_sync_error' => $sincronizacao['message'] ?? null,
        ]);

        return $sincronizacao;
    }

    private function ctoInfo(OrdemServico $ordem): array
    {
        $onu = data_get($ordem->sgp_dados ?? [], 'contratos.0.servicos.0.onu');
        $cto = data_get($onu, 'splitter.nome');
        $porta = data_get($onu, 'splitter.porta');

        return [
            'cto' => $cto,
            'porta' => $porta,
            'label' => $cto
                ? 'CTO: '.$cto.' Porta: '.($porta ?: 'sem porta')
                : 'Sem CTO',
            'has_cto' => (bool) $cto,
            'has_porta' => filled($porta),
        ];
    }

    private function tecnicosAtivosOrdenados(): Collection
    {
        return Tecnico::query()
            ->select(['id', 'nome', 'ativo', 'whatsapp_grupo_id'])
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }

    private function tecnicosDisponiveisOrdenados(): Collection
    {
        return Tecnico::query()
            ->select(['id', 'nome', 'ativo'])
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();
    }

    private function resumoCards(object $resumoDia): array
    {
        return [
            [
                'title' => 'Serviços no dia',
                'value' => (int) ($resumoDia->total ?? 0),
                'tone' => 'blue',
            ],
            [
                'title' => 'Serviços passados',
                'value' => (int) ($resumoDia->total_passadas ?? 0),
                'tone' => 'amber',
            ],
            [
                'title' => 'Serviços concluídos',
                'value' => (int) ($resumoDia->total_concluidas ?? 0),
                'tone' => 'emerald',
            ],
        ];
    }

    public function destroy(OrdemServico $ordem)
    {
        $ordem->delete();

        return redirect()->route('ordens.index')
            ->with('success', 'Ordem de serviço removida.');
    }
}
