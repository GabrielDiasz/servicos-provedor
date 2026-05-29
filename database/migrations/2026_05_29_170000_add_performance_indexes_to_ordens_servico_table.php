<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->index(['data_marcacao', 'status'], 'os_data_status_idx');
            $table->index(['status', 'updated_at'], 'os_status_updated_idx');
            $table->index(['status', 'tipo_servico'], 'os_status_tipo_idx');
            $table->index(['tecnico_id', 'status'], 'os_tecnico_status_idx');
            $table->index('created_at', 'os_created_idx');
            $table->index('sgp_cliente_id', 'os_sgp_cliente_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropIndex('os_data_status_idx');
            $table->dropIndex('os_status_updated_idx');
            $table->dropIndex('os_status_tipo_idx');
            $table->dropIndex('os_tecnico_status_idx');
            $table->dropIndex('os_created_idx');
            $table->dropIndex('os_sgp_cliente_idx');
        });
    }
};
