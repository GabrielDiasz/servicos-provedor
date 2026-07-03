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
        Schema::create('upgrade_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nome_arquivo');
            $table->unsignedInteger('total_clientes')->default(0);
            $table->unsignedInteger('selecionados')->default(0);
            $table->unsignedInteger('enviados')->default(0);
            $table->unsignedInteger('falhas')->default(0);
            $table->string('status_envio')->default('importado');
            $table->text('erro_ultimo')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upgrade_campaigns');
    }
};
