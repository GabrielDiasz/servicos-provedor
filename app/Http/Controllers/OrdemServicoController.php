<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Tecnico;
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

        $ordem = OrdemServico::create($validated);

        return redirect()->route('ordens.show', $ordem)
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
            'bairro'           => 'required|string|max:255',
            'tipo_servico'     => 'required|in:' . implode(',', array_keys(OrdemServico::TIPOS)),
            'turno'            => 'required|in:manha,tarde',
            'prioridade'       => 'required|in:normal,alta,urgente',
            'status'           => 'required|in:' . implode(',', array_keys(OrdemServico::STATUS)),
            'data_marcacao'    => 'required|date',
            'tecnico_id'       => 'required|exists:tecnicos,id',
            'observacao'       => 'nullable|string|max:1000',
        ]);

        $ordem->update($validated);

        return redirect()->route('ordens.show', $ordem)
            ->with('success', 'Ordem de serviço atualizada com sucesso!');
    }

    public function destroy(OrdemServico $ordem)
    {
        $ordem->delete();

        return redirect()->route('ordens.index')
            ->with('success', 'Ordem de serviço removida.');
    }
}
