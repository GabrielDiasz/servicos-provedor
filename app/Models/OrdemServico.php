<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    private const ROW_STYLES = [
        'passada' => [
            'classes' => 'os-row os-row-passada',
            'id_classes' => 'os-row-id os-row-id-passada',
        ],
        'concluida' => [
            'classes' => 'os-row os-row-concluida',
            'id_classes' => 'os-row-id os-row-id-concluida',
        ],
        'pendente' => [
            'classes' => 'os-row os-row-pendente',
            'id_classes' => 'os-row-id os-row-id-pendente',
        ],
        'retornar' => [
            'classes' => 'os-row os-row-retornar',
            'id_classes' => 'os-row-id os-row-id-retornar',
        ],
        'sem_contato' => [
            'classes' => 'os-row os-row-sem-contato',
            'id_classes' => 'os-row-id os-row-id-sem-contato',
        ],
        'sem_viabilidade' => [
            'classes' => 'os-row os-row-sem-viabilidade',
            'id_classes' => 'os-row-id os-row-id-sem-viabilidade',
        ],
        'cancelada' => [
            'classes' => 'os-row os-row-cancelada',
            'id_classes' => 'os-row-id os-row-id-cancelada',
        ],
        'default' => [
            'classes' => 'os-row os-row-default',
            'id_classes' => 'os-row-id os-row-id-default',
        ],
    ];

    private const PRIORIDADE_CLASSES = [
        'normal' => 'os-badge os-prioridade-normal',
        'alta' => 'os-badge os-prioridade-alta',
        'urgente' => 'os-badge os-prioridade-urgente',
        'default' => 'os-badge os-prioridade-default',
    ];

    private const TURNO_CLASSES = [
        'manha' => 'os-badge os-turno-manha',
        'tarde' => 'os-badge os-turno-tarde',
        'default' => 'os-badge os-turno-default',
    ];

    private const STATUS_CLASSES = [
        'pendente' => 'os-badge os-status-pendente',
        'passada' => 'os-badge os-status-passada',
        'concluida' => 'os-badge os-status-concluida',
        'cancelada' => 'os-badge os-status-cancelada',
        'retornar' => 'os-badge os-status-retornar',
        'sem_contato' => 'os-badge os-status-sem-contato',
        'sem_viabilidade' => 'os-badge os-status-sem-viabilidade',
        'default' => 'os-badge os-status-default',
    ];

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
        'sgp_ocorrencia_sgp_id',
        'sgp_os_numero',
        'sgp_sync_status',
        'sgp_sync_error',
        'whatsapp_send_status',
        'whatsapp_send_error',
        'whatsapp_sent_at',
        'whatsapp_sent_for_sgp_ocorrencia_numero',
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
        'whatsapp_sent_at' => 'datetime',
    ];

    // Labels para exibição na tela
    public const TIPOS = [
        'instalacao' => 'Instalação',
        'reparo' => 'Reparo',
        'upgrade' => 'Upgrade',
        'reativacao' => 'Reativação',
        'desconectado' => 'Desconectado',
        'troca_senha' => 'Troca de Senha',
        'mudanca_endereco' => 'Mudança de Endereço',
        'cancelamento' => 'Cancelamento',
    ];

    public const STATUS = [
        'pendente' => 'Pendente',
        'passada' => 'Passada',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada',
        'retornar' => 'Retornar',
        'sem_contato' => 'Sem Contato',
        'sem_viabilidade' => 'Sem Viabilidade',
    ];

    public const STATUS_ABERTOS = [
        'passada',
    ];

    public const PRIORIDADES = [
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    public const TURNOS = [
        'manha' => 'Manhã',
        'tarde' => 'Tarde',
    ];

    public function canSendWhatsapp(): bool
    {
        return filled($this->tecnico_id) && $this->status !== 'concluida';
    }

    public function getRowStyleAttribute(): array
    {
        return self::ROW_STYLES[$this->status] ?? self::ROW_STYLES['default'];
    }

    public function getRowClassesAttribute(): string
    {
        return $this->row_style['classes'];
    }

    public function getRowIdClassesAttribute(): string
    {
        return $this->row_style['id_classes'];
    }

    public function getPrioridadeClassesAttribute(): string
    {
        return self::PRIORIDADE_CLASSES[$this->prioridade] ?? self::PRIORIDADE_CLASSES['default'];
    }

    public function getTurnoClassesAttribute(): string
    {
        return self::TURNO_CLASSES[$this->turno] ?? self::TURNO_CLASSES['default'];
    }

    public function getStatusClassesAttribute(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? self::STATUS_CLASSES['default'];
    }

    public function getTipoServicoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_servico] ?? ($this->tipo_servico ?: '-');
    }

    public function getPrioridadeLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridade] ?? ($this->prioridade ?: '-');
    }

    public function getTurnoLabelAttribute(): string
    {
        return self::TURNOS[$this->turno] ?? ($this->turno ?: '-');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? ($this->status ?: '-');
    }

    public function getSgpSyncStatusLabelAttribute(): string
    {
        return match ($this->sgp_sync_status) {
            'queued' => 'Na fila',
            'processando' => 'Processando',
            'sincronizado' => 'Sincronizado',
            'erro' => 'Erro',
            'ignorado' => 'Ignorado',
            default => $this->sgp_sync_status ?: '-',
        };
    }

    public function getWhatsappSendStatusLabelAttribute(): string
    {
        return match ($this->whatsapp_send_status) {
            'queued' => 'Na fila',
            'processando' => 'Processando',
            'sent' => 'Enviado',
            'erro' => 'Erro',
            'ignorado' => 'Ignorado',
            default => $this->whatsapp_send_status ?: '-',
        };
    }

    public function getStatusOptionsAttribute(): array
    {
        return self::STATUS;
    }

    public function getEditableStatusOptionsAttribute(): array
    {
        if ($this->status === 'passada') {
            return self::STATUS;
        }

        return array_diff_key(self::STATUS, ['passada' => true]);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
