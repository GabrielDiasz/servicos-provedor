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

        DB::statement("ALTER TABLE ordens_servico MODIFY status ENUM('pendente', 'passada', 'concluida', 'cancelada', 'retornar', 'sem_contato') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE ordens_servico MODIFY status ENUM('passada', 'concluida', 'cancelada', 'retornar', 'sem_contato') NOT NULL DEFAULT 'passada'");
    }
};
