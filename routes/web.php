<?php

use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\TecnicoController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return redirect()->route('ordens.index');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return redirect()->route('ordens.index');
    })->name('dashboard');

    // Ordens de Serviço
    Route::resource('ordens', OrdemServicoController::class);

    // Técnicos
    Route::resource('tecnicos', TecnicoController::class)->except(['show']);
});

//Criar usuário admin com senha bcrypt
Route::get('/criar-admin', function () {

    User::create([
        'name' => 'Administrador',
        'email' => 'admin@gpr.com',
        'password' => Hash::make('12345678'),
    ]);

    return 'Usuário admin criado com sucesso!';
});
require __DIR__ . '/auth.php';
