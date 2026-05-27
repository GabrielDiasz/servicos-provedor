<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Tecnico;
use App\Services\SgpService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdemServicoController extends Controller
{
    public function index(Request $request)
    {
        $query = OrdemServico::with(['tecnico', 'atendente']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tecnico_id')) {
            $query->where('tecnico_id', $request->tecnico_id);
        }

        if ($request->filled('tipo_servico')) {
            $query->where('tipo_servico', $request->tipo_servico);
        }

        if ($request->filled('data_marcacao')) {
            $query->whereDate('data_marcacao', $request->data_marcacao);
        }

        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->prioridade);
        }

        $ordens = $query->orderByRaw("FIELD(prioridade, 'urgente', 'alta', 'normal')")
            ->orderBy('data_marcacao', 'asc')
            ->paginate(20)
            ->withQueryString();

        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();

        return view('ordens.index', compact('ordens', 'tecnicos'));
    }

    public function create()
    {
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        return view('ordens.create', compact('tecnicos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_nome'     => 'required|string|max:255',
            'cliente_telefone' => 'required|string|max:20',
            'sgp_cliente_link' => 'nullable|string|max:255',
            'bairro'           => 'required|string|max:255',
            'tipo_servico'     => 'required|in:' . implode(',', array_keys(OrdemServico::TIPOS)),
            'turno'            => 'required|in:manha,tarde',
            'prioridade'       => 'required|in:normal,alta,urgente',
            'data_marcacao'    => 'required|date',
            'tecnico_id'       => 'required|exists:tecnicos,id',
            'observacao'       => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status']  = 'passada';
        $validated = $this->preencherDadosSgp($validated);

        $ordem = OrdemServico::create($validated);

        return redirect()->route('ordens.show', ['ordem' => $ordem->id])
            ->with('success', 'Ordem de serviço criada com sucesso!');
    }

    public function show(OrdemServico $ordem)
    {
        $ordem->load(['tecnico', 'atendente']);
        return view('ordens.show', compact('ordem'));
    }

    public function edit(OrdemServico $ordem)
    {
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        return view('ordens.edit', compact('ordem', 'tecnicos'));
    }

    public function update(Request $request, OrdemServico $ordem)
    {
        $validated = $request->validate([
            'cliente_nome'     => 'required|string|max:255',
            'cliente_telefone' => 'required|string|max:20',
            'sgp_cliente_link' => 'nullable|string|max:255',
            'bairro'           => 'required|string|max:255',
            'tipo_servico'     => 'required|in:' . implode(',', array_keys(OrdemServico::TIPOS)),
            'turno'            => 'required|in:manha,tarde',
            'prioridade'       => 'required|in:normal,alta,urgente',
            'status'           => 'required|in:' . implode(',', array_keys(OrdemServico::STATUS)),
            'data_marcacao'    => 'required|date',
            'tecnico_id'       => 'required|exists:tecnicos,id',
            'observacao'       => 'nullable|string|max:1000',
        ]);

        $ordem->update($this->preencherDadosSgp($validated));

        return redirect()->route('ordens.show', $ordem)
            ->with('success', 'Ordem de serviço atualizada com sucesso!');
    }

    public function enviarWhatsApp(OrdemServico $ordem, WhatsAppService $whatsApp)
    {
        if ($whatsApp->enviarOrdemServico($ordem)) {
            return back()->with('success', 'Ordem de serviço enviada para o técnico pelo WhatsApp.');
        }

        return back()->with('error', 'Não foi possível enviar a ordem pelo WhatsApp. Confira se o serviço está conectado e se o técnico tem um grupo de envio válido.');
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

    private function preencherDadosSgp(array $validated): array
    {
        if (empty($validated['sgp_cliente_link'])) {
            return $validated;
        }

        $dadosSgp = app(SgpService::class)->consultarClientePorLink($validated['sgp_cliente_link']);

        if (! $dadosSgp) {
            return $validated;
        }

        return array_merge($validated, $dadosSgp, [
            'sgp_cliente_link' => $validated['sgp_cliente_link'],
        ]);
    }

    public function destroy(OrdemServico $ordem)
    {
        $ordem->delete();

        return redirect()->route('ordens.index')
            ->with('success', 'Ordem de serviço removida.');
    }
}
