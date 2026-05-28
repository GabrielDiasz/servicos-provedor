<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('sgp_ocorrencia_numero')->nullable()->after('sgp_dados');
            $table->string('sgp_os_numero')->nullable()->after('sgp_ocorrencia_numero');
            $table->string('sgp_sync_status')->nullable()->after('sgp_os_numero');
            $table->text('sgp_sync_error')->nullable()->after('sgp_sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn([
                'sgp_ocorrencia_numero',
                'sgp_os_numero',
                'sgp_sync_status',
                'sgp_sync_error',
            ]);
        });
    }
};
