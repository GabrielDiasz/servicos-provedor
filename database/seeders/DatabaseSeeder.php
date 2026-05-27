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
        User::updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@gpr.com'),
        ], [
            'name' => env('ADMIN_NAME', 'Administrador'),
            'perfil' => 'admin',
            'password' => Hash::make(env('ADMIN_PASSWORD', '12345678')),
        ]);

        WhatsAppGrupo::updateOrCreate([
            'grupo_id' => env('WHATSAPP_TEST_GROUP_ID', '120363428623245389@g.us'),
        ], [
            'nome' => env('WHATSAPP_TEST_GROUP_NAME', 'Grupo de Teste'),
            'ativo' => true,
        ]);
    }
}
