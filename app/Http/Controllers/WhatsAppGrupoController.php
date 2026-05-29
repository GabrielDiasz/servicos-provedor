<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppGrupo;
use Illuminate\Http\Request;

class WhatsAppGrupoController extends Controller
{
    public function index()
    {
        $grupos = WhatsAppGrupo::query()
            ->withCount('tecnicos')
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->paginate(20);

        return view('whatsapp-grupos.index', compact('grupos'));
    }

    public function create()
    {
        return view('whatsapp-grupos.create');
    }

    public function store(Request $request)
    {
        WhatsAppGrupo::create($this->validateRequest($request));

        return redirect()->route('whatsapp-grupos.index')
            ->with('success', 'Grupo de WhatsApp cadastrado com sucesso!');
    }

    public function edit(WhatsAppGrupo $whatsappGrupo)
    {
        return view('whatsapp-grupos.edit', compact('whatsappGrupo'));
    }

    public function update(Request $request, WhatsAppGrupo $whatsappGrupo)
    {
        $validated = $this->validateRequest($request, $whatsappGrupo);
        $validated['ativo'] = $request->has('ativo');

        $whatsappGrupo->update($validated);

        return redirect()->route('whatsapp-grupos.index')
            ->with('success', 'Grupo de WhatsApp atualizado com sucesso!');
    }

    public function destroy(WhatsAppGrupo $whatsappGrupo)
    {
        if ($whatsappGrupo->tecnicos()->exists()) {
            return back()->with('error', 'Não é possível excluir um grupo vinculado a técnicos.');
        }

        $whatsappGrupo->delete();

        return redirect()->route('whatsapp-grupos.index')
            ->with('success', 'Grupo de WhatsApp removido.');
    }

    private function validateRequest(Request $request, ?WhatsAppGrupo $whatsappGrupo = null): array
    {
        $id = $whatsappGrupo?->id;

        return $request->validate([
            'nome' => 'required|string|max:255',
            'grupo_id' => 'required|string|max:255|ends_with:@g.us|unique:whatsapp_grupos,grupo_id,'.$id,
        ]);
    }
}
