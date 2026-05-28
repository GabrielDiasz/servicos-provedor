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

        DB::statement('ALTER TABLE ordens_servico DROP FOREIGN KEY ordens_servico_tecnico_id_foreign');
        DB::statement('ALTER TABLE ordens_servico MODIFY tecnico_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE ordens_servico ADD CONSTRAINT ordens_servico_tecnico_id_foreign FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE ordens_servico DROP FOREIGN KEY ordens_servico_tecnico_id_foreign');
        DB::statement('ALTER TABLE ordens_servico MODIFY tecnico_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE ordens_servico ADD CONSTRAINT ordens_servico_tecnico_id_foreign FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id) ON DELETE RESTRICT');
    }
};
