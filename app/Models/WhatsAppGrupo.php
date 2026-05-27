<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppGrupo extends Model
{
    protected $table = 'whatsapp_grupos';

    protected $fillable = [
        'nome',
        'grupo_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function tecnicos(): HasMany
    {
        return $this->hasMany(Tecnico::class, 'whatsapp_grupo_id');
    }
}
