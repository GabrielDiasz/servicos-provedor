<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('grupo_id')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('tecnicos', function (Blueprint $table) {
            $table->foreignId('whatsapp_grupo_id')
                ->nullable()
                ->after('telefone')
                ->constrained('whatsapp_grupos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tecnicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('whatsapp_grupo_id');
        });

        Schema::dropIfExists('whatsapp_grupos');
    }
};
