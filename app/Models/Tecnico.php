<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tecnico extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'whatsapp_grupo_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    public function whatsappGrupo(): BelongsTo
    {
        return $this->belongsTo(WhatsAppGrupo::class, 'whatsapp_grupo_id');
    }
}
