<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use App\Models\WhatsAppGrupo;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index()
    {
        $tecnicos = Tecnico::with('whatsappGrupo')
            ->orderBy('nome')
            ->paginate(20);

        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        $whatsappGrupos = WhatsAppGrupo::where('ativo', true)->orderBy('nome')->get();

        return view('tecnicos.create', compact('whatsappGrupos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'whatsapp_grupo_id' => 'required|exists:whatsapp_grupos,id',
        ]);

        Tecnico::create($validated);

        return redirect()->route('tecnicos.index')
            ->with('success', 'Técnico cadastrado com sucesso!');
    }

    public function edit(Tecnico $tecnico)
    {
        $whatsappGrupos = WhatsAppGrupo::where('ativo', true)->orderBy('nome')->get();

        return view('tecnicos.edit', compact('tecnico', 'whatsappGrupos'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'whatsapp_grupo_id' => 'required|exists:whatsapp_grupos,id',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->has('ativo');

        $tecnico->update($validated);

        return redirect()->route('tecnicos.index')
            ->with('success', 'Técnico atualizado com sucesso!');
    }

    public function destroy(Tecnico $tecnico)
    {
        if ($tecnico->ordensServico()->exists()) {
            return back()->with('error', 'Não é possível excluir um técnico com ordens de serviço vinculadas. Desative-o em vez disso.');
        }

        $tecnico->delete();

        return redirect()->route('tecnicos.index')
            ->with('success', 'Técnico removido.');
    }
}
