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
        'sgp_cliente_link',
        'sgp_cliente_id',
        'sgp_contrato_id',
        'sgp_cpf_cnpj',
        'sgp_data_nascimento',
        'sgp_plano',
        'sgp_vencimento',
        'sgp_pppoe_login',
        'sgp_pppoe_senha',
        'sgp_endereco',
        'sgp_dados',
        'sgp_ocorrencia_numero',
        'sgp_os_numero',
        'sgp_sync_status',
        'sgp_sync_error',
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
        'sgp_data_nascimento' => 'date',
        'sgp_dados' => 'array',
    ];

    // Labels para exibição na tela
    public const TIPOS = [
        'instalacao'       => 'Instalação',
        'reparo'           => 'Reparo',
        'upgrade'          => 'Upgrade',
        'reativacao'       => 'Reativação',
        'desconectado'     => 'Desconectado',
        'troca_senha'      => 'Troca de Senha',
        'mudanca_endereco' => 'Mudança de Endereço',
        'cancelamento'     => 'Cancelamento',
    ];

    public const STATUS = [
        'pendente'        => 'Pendente',
        'passada'         => 'Passada',
        'concluida'       => 'Concluída',
        'cancelada'       => 'Cancelada',
        'retornar'        => 'Retornar',
        'sem_contato'     => 'Sem Contato',
        'sem_viabilidade' => 'Sem Viabilidade',
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
