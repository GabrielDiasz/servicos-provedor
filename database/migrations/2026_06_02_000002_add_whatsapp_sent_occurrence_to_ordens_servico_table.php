<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('whatsapp_sent_for_sgp_ocorrencia_numero')->nullable()->after('whatsapp_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn('whatsapp_sent_for_sgp_ocorrencia_numero');
        });
    }
};
