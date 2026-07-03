<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class UpgradeCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'nome_arquivo',
        'total_clientes',
        'selecionados',
        'enviados',
        'falhas',
        'status_envio',
        'erro_ultimo',
        'enviado_em',
        'finalizado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    public function contatos(): HasMany
    {
        return $this->hasMany(UpgradeContact::class, 'upgrade_campaign_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_envio) {
            'importado' => 'Importado',
            'na_fila' => 'Na fila',
            'enviando' => 'Enviando',
            'concluido' => 'Concluido',
            'concluido_com_erro' => 'Concluido com erro',
            'erro' => 'Erro',
            default => $this->status_envio ?: '-',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status_envio) {
            'importado' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
            'na_fila' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            'enviando' => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
            'concluido' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'concluido_com_erro' => 'bg-orange-500/15 text-orange-300 border-orange-500/30',
            'erro' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
            default => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        };
    }

    public function getProgressoPercentualAttribute(): int
    {
        if ($this->selecionados <= 0) {
            return 0;
        }

        return (int) min(100, round((($this->enviados + $this->falhas) / $this->selecionados) * 100));
    }
}
