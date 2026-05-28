<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nome');
            $table->string('cliente_telefone', 20);
            $table->string('bairro');
            $table->enum('tipo_servico', [
                'instalacao',
                'reparo',
                'upgrade',
                'reativacao',
                'desconectado',
                'troca_senha',
                'mudanca_endereco',
                'cancelamento',
            ]);
            $table->enum('turno', ['manha', 'tarde']);
            $table->enum('prioridade', ['normal', 'alta', 'urgente'])->default('normal');
            $table->enum('status', [
                'pendente',
                'passada',
                'concluida',
                'cancelada',
                'retornar',
                'sem_contato',
            ])->default('pendente');
            $table->date('data_marcacao');
            $table->text('observacao')->nullable();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servico');
    }
};
