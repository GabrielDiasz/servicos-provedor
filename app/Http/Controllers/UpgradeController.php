<?php

namespace App\Http\Controllers;

use App\Jobs\SendUpgradeCampaignJob;
use App\Models\UpgradeCampaign;
use App\Models\UpgradeContact;
use App\Services\UpgradeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class UpgradeController extends Controller
{
    public function index(Request $request)
    {
        $campaign = null;

        if ($request->filled('campaign')) {
            $campaign = UpgradeCampaign::query()
                ->with(['contatos' => fn ($query) => $query->orderBy('linha_planilha')])
                ->findOrFail($request->integer('campaign'));
        } else {
            $campaign = UpgradeCampaign::query()
                ->with(['contatos' => fn ($query) => $query->orderBy('linha_planilha')])
                ->latest('id')
                ->first();
        }

        return view('upgrade.index', [
            'campaign' => $campaign,
            'contatos' => $campaign?->contatos ?? collect(),
        ]);
    }

    public function importar(Request $request, UpgradeImportService $importService)
    {
        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:15360'],
        ]);

        try {
            $dados = $importService->importar($validated['arquivo']);

            $campaign = DB::transaction(function () use ($validated, $dados) {
                $campaign = UpgradeCampaign::create([
                    'user_id' => Auth::id(),
                    'nome_arquivo' => $validated['arquivo']->getClientOriginalName(),
                    'total_clientes' => $dados['total'],
                    'selecionados' => 0,
                    'enviados' => 0,
                    'falhas' => 0,
                    'status_envio' => 'importado',
                    'erro_ultimo' => null,
                    'enviado_em' => null,
                    'finalizado_em' => null,
                ]);

                $campaign->contatos()->createMany($dados['contacts']);

                return $campaign;
            });

            return redirect()
                ->route('upgrade.index', ['campaign' => $campaign->id])
                ->with('success', 'Planilha importada com sucesso. Agora voce pode selecionar os clientes e iniciar o envio.');
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['arquivo' => $exception->getMessage()])
                ->withInput();
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['arquivo' => 'Nao foi possivel importar a planilha. Verifique o arquivo e tente novamente.'])
                ->withInput();
        }
    }

    public function enviar(Request $request, UpgradeCampaign $campaign)
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer'],
            'phone_preferences' => ['nullable', 'array'],
        ]);

        if ($campaign->status_envio === 'enviando') {
            return back()->with('error', 'Esta planilha ja esta em envio. Aguarde a conclusao antes de iniciar um novo disparo.');
        }

        $selectedIds = $validated['scope'] === 'all'
            ? $campaign->contatos()->pluck('id')->all()
            : collect($request->input('selected_ids', []))
                ->map(fn ($value) => (int) $value)
                ->filter()
                ->unique()
                ->values()
                ->all();

        if ($selectedIds === []) {
            return back()->with('error', 'Selecione ao menos um cliente para iniciar o envio.');
        }

        $phonePreferences = collect($request->input('phone_preferences', []))
            ->mapWithKeys(function ($value, $contactId) {
                $preference = in_array($value, ['auto', 'primeiro', 'segundo'], true) ? $value : 'auto';

                return [(int) $contactId => $preference];
            })
            ->all();

        $contatosParaEnviar = $campaign->contatos()
            ->whereIn('id', $selectedIds)
            ->whereIn('status_envio', ['aguardando', 'erro'])
            ->orderBy('linha_planilha')
            ->get();

        if ($contatosParaEnviar->isEmpty()) {
            return back()->with('error', 'Nenhum cliente novo foi encontrado para envio. Os registros selecionados ja foram enviados ou estao em processamento.');
        }

        DB::transaction(function () use ($campaign, $contatosParaEnviar, $phonePreferences) {
            $campaign->forceFill([
                'selecionados' => $contatosParaEnviar->count(),
                'enviados' => 0,
                'falhas' => 0,
                'status_envio' => 'na_fila',
                'erro_ultimo' => null,
                'enviado_em' => null,
                'finalizado_em' => null,
            ])->save();

            foreach ($contatosParaEnviar as $contato) {
                $contato->forceFill([
                    'status_envio' => 'na_fila',
                    'erro_envio' => null,
                    'enviado_em' => null,
                    'contato_preferido' => $phonePreferences[$contato->id] ?? $contato->contato_preferido ?? 'auto',
                ])->save();
            }
        });

        SendUpgradeCampaignJob::dispatch(
            $campaign->id,
            $contatosParaEnviar->pluck('id')->all(),
            $phonePreferences,
            0,
            Auth::id(),
            Auth::user()?->name,
            Auth::user()?->email
        )->afterCommit();

        $ignorados = count($selectedIds) - $contatosParaEnviar->count();
        $mensagem = 'Envio enfileirado com sucesso. '.$contatosParaEnviar->count().' cliente(s) serao processados.';

        if ($ignorados > 0) {
            $mensagem .= ' '.$ignorados.' registro(s) selecionado(s) ja haviam sido enviados e foram ignorados.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'campaign_id' => $campaign->id,
                'selected' => $contatosParaEnviar->count(),
                'ignored' => $ignorados,
            ]);
        }

        return back()->with('success', $mensagem);
    }

    public function status(UpgradeCampaign $campaign)
    {
        $campaign->load(['contatos' => fn ($query) => $query->orderBy('linha_planilha')]);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'status_envio' => $campaign->status_envio,
                'status_label' => $campaign->status_label,
                'status_badge_class' => $campaign->status_badge_class,
                'total_clientes' => $campaign->total_clientes,
                'selecionados' => $campaign->selecionados,
                'enviados' => $campaign->enviados,
                'falhas' => $campaign->falhas,
                'progresso_percentual' => $campaign->progresso_percentual,
                'erro_ultimo' => $campaign->erro_ultimo,
                'enviado_em' => $campaign->enviado_em?->toDateTimeString(),
                'finalizado_em' => $campaign->finalizado_em?->toDateTimeString(),
            ],
            'contatos' => $campaign->contatos->map(function (UpgradeContact $contato) {
                return [
                    'id' => $contato->id,
                    'status_envio' => $contato->status_envio,
                    'status_label' => $contato->status_label,
                    'status_badge_class' => $contato->status_badge_class,
                    'erro_envio' => $contato->erro_envio,
                    'enviado_em' => $contato->enviado_em?->toDateTimeString(),
                    'telefone_para_envio' => $contato->telefone_para_envio,
                    'contato_preferido' => $contato->contato_preferido,
                ];
            })->values(),
        ]);
    }

    public function destroy(UpgradeCampaign $campaign)
    {
        if (in_array($campaign->status_envio, ['na_fila', 'enviando'], true)) {
            return back()->with('error', 'Nao e possivel remover uma planilha enquanto o envio estiver em andamento.');
        }

        $campaign->delete();

        return redirect()
            ->route('upgrade.index')
            ->with('success', 'Planilha removida com sucesso.');
    }
}
