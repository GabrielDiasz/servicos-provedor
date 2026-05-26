<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index()
    {
        $tecnicos = Tecnico::orderBy('nome')->paginate(20);
        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        return view('tecnicos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'     => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
        ]);

        Tecnico::create($validated);

        return redirect()->route('tecnicos.index')
            ->with('success', 'Técnico cadastrado com sucesso!');
    }

    public function edit(Tecnico $tecnico)
    {
        return view('tecnicos.edit', compact('tecnico'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        $validated = $request->validate([
            'nome'     => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'ativo'    => 'boolean',
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
