<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upgrade_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upgrade_campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('linha_planilha');
            $table->string('nome_cliente');
            $table->string('primeiro_contato', 40)->nullable();
            $table->string('segundo_contato', 40)->nullable();
            $table->string('contato_preferido', 20)->default('auto');
            $table->string('status_envio', 20)->default('aguardando');
            $table->text('erro_envio')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();

            $table->index(['upgrade_campaign_id', 'status_envio']);
            $table->index(['upgrade_campaign_id', 'nome_cliente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upgrade_contacts');
    }
};
