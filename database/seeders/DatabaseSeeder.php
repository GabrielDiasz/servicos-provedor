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

        $this->criarOuAtualizarAtendente(
            'ATTENDANT_PABLO',
            'Pablo Bomfim',
            'pablo@gpr.local'
        );

        $this->criarOuAtualizarAtendente(
            'ATTENDANT_PAULO',
            'Paulo Henrique',
            'paulo@gpr.local'
        );

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

    private function criarOuAtualizarAtendente(string $prefixo, string $nomePadrao, string $emailPadrao): void
    {
        $nome = trim((string) env($prefixo.'_NAME', $nomePadrao));
        $email = trim((string) env($prefixo.'_EMAIL', $emailPadrao));
        $password = trim((string) env($prefixo.'_PASSWORD', ''));

        if ($email === '' || $password === '') {
            return;
        }

        User::updateOrCreate([
            'email' => $email,
        ], [
            'name' => $nome !== '' ? $nome : $nomePadrao,
            'perfil' => 'atendente',
            'password' => Hash::make($password),
        ]);
    }
}
