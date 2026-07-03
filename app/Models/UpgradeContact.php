<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UpgradeContact extends Model
{
    protected $fillable = [
        'upgrade_campaign_id',
        'linha_planilha',
        'nome_cliente',
        'primeiro_contato',
        'segundo_contato',
        'contato_preferido',
        'status_envio',
        'erro_envio',
        'enviado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(UpgradeCampaign::class, 'upgrade_campaign_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_envio) {
            'aguardando' => 'Aguardando',
            'na_fila' => 'Na fila',
            'enviando' => 'Enviando',
            'enviado' => 'Enviado',
            'erro' => 'Erro',
            'ignorado' => 'Ignorado',
            default => $this->status_envio ?: '-',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status_envio) {
            'aguardando' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
            'na_fila' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            'enviando' => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
            'enviado' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'erro' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
            'ignorado' => 'bg-zinc-500/15 text-zinc-300 border-zinc-500/30',
            default => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    }

    public function getContatoPreferidoLabelAttribute(): string
    {
        return match ($this->contato_preferido) {
            'primeiro' => 'Primeiro contato',
            'segundo' => 'Segundo contato',
            default => 'Automático',
        };
    }

    public function getPrimeiroContatoFormatadoAttribute(): ?string
    {
        return $this->formatarTelefone($this->primeiro_contato);
    }

    public function getSegundoContatoFormatadoAttribute(): ?string
    {
        return $this->formatarTelefone($this->segundo_contato);
    }

    public function getTelefoneParaEnvioAttribute(): ?string
    {
        return $this->resolverTelefoneParaEnvio();
    }

    public function resolverTelefoneParaEnvio(): ?string
    {
        $primeiro = $this->normalizarTelefone($this->primeiro_contato);
        $segundo = $this->normalizarTelefone($this->segundo_contato);

        return match ($this->contato_preferido) {
            'primeiro' => $primeiro ?: $segundo,
            'segundo' => $segundo ?: $primeiro,
            default => $primeiro ?: $segundo,
        };
    }

    public function telefonesParaEnvio(): array
    {
        $primeiro = $this->normalizarTelefone($this->primeiro_contato);
        $segundo = $this->normalizarTelefone($this->segundo_contato);

        $telefonesOrdenados = match ($this->contato_preferido) {
            'segundo' => [$segundo, $primeiro],
            default => [$primeiro, $segundo],
        };

        return collect($telefonesOrdenados)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarTelefone(?string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $telefone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return str_starts_with($digits, '55') ? $digits : '55'.$digits;
        }

        if (strlen($digits) > 11 && str_starts_with($digits, '55')) {
            return $digits;
        }

        return strlen($digits) >= 10 ? (str_starts_with($digits, '55') ? $digits : '55'.$digits) : null;
    }

    private function formatarTelefone(?string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $telefone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        }

        if (strlen($digits) === 10) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 4).'-'.substr($digits, 6);
        }

        return $telefone ?: null;
    }
}
