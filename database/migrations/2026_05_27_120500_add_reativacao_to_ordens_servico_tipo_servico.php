<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE ordens_servico MODIFY tipo_servico ENUM('instalacao', 'reparo', 'upgrade', 'reativacao', 'desconectado', 'troca_senha', 'mudanca_endereco', 'cancelamento') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE ordens_servico MODIFY tipo_servico ENUM('instalacao', 'reparo', 'upgrade', 'desconectado', 'troca_senha', 'mudanca_endereco', 'cancelamento') NOT NULL");
    }
};
