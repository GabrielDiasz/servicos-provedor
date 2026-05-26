<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected $fillable = [
        'cliente_nome',
        'cliente_telefone',
        'bairro',
        'tipo_servico',
        'turno',
        'prioridade',
        'status',
        'data_marcacao',
        'observacao',
        'tecnico_id',
        'user_id',
    ];

    protected $casts = [
        'data_marcacao' => 'date',
    ];

    // Labels para exibição na tela
    public const TIPOS = [
        'instalacao'       => 'Instalação',
        'reparo'           => 'Reparo',
        'upgrade'          => 'Upgrade',
        'desconectado'     => 'Desconectado',
        'troca_senha'      => 'Troca de Senha',
        'mudanca_endereco' => 'Mudança de Endereço',
        'cancelamento'     => 'Cancelamento',
    ];

    public const STATUS = [
        'passada'      => 'Passada',
        'concluida'    => 'Concluída',
        'cancelada'    => 'Cancelada',
        'retornar'     => 'Retornar',
        'sem_contato'  => 'Sem Contato',
    ];

    public const PRIORIDADES = [
        'normal'  => 'Normal',
        'alta'    => 'Alta',
        'urgente' => 'Urgente',
    ];

    public const TURNOS = [
        'manha' => 'Manhã',
        'tarde' => 'Tarde',
    ];

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}