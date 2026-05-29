<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WhatsAppGrupo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = trim((string) env('ADMIN_EMAIL', ''));
        $adminPassword = trim((string) env('ADMIN_PASSWORD', ''));

        if ($adminEmail !== '' && $adminPassword !== '') {
            User::updateOrCreate([
                'email' => $adminEmail,
            ], [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'perfil' => 'admin',
                'password' => Hash::make($adminPassword),
            ]);
        }

        $grupoId = trim((string) env('WHATSAPP_TEST_GROUP_ID', ''));
        $grupoNome = trim((string) env('WHATSAPP_TEST_GROUP_NAME', ''));

        if ($grupoId !== '' && $grupoNome !== '') {
            WhatsAppGrupo::updateOrCreate([
                'grupo_id' => $grupoId,
            ], [
                'nome' => $grupoNome,
                'ativo' => true,
            ]);
        }
    }
}
