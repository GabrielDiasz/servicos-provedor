<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\UpgradeController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppGrupoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('ordens.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('upgrade')->name('upgrade.')->group(function () {
        Route::get('/', [UpgradeController::class, 'index'])->name('index');
        Route::post('/importar', [UpgradeController::class, 'importar'])->name('importar');
        Route::post('/{campaign}/enviar', [UpgradeController::class, 'enviar'])->name('enviar');
        Route::get('/{campaign}/status', [UpgradeController::class, 'status'])->name('status');
        Route::delete('/{campaign}', [UpgradeController::class, 'destroy'])->name('destroy');
    });

    Route::post('ordens/{ordem}/enviar-whatsapp', [OrdemServicoController::class, 'enviarWhatsApp'])
        ->name('ordens.enviar-whatsapp');

    Route::patch('ordens/{ordem}/status', [OrdemServicoController::class, 'atualizarStatus'])
        ->name('ordens.atualizar-status');

    Route::patch('ordens/{ordem}/tecnico', [OrdemServicoController::class, 'atualizarTecnico'])
        ->name('ordens.atualizar-tecnico');

    Route::post('ordens/buscar-sgp', [OrdemServicoController::class, 'buscarSgp'])
        ->name('ordens.buscar-sgp');

    Route::resource('ordens', OrdemServicoController::class)->parameters([
        'ordens' => 'ordem',
    ]);

    Route::resource('tecnicos', TecnicoController::class)->except(['show']);

    Route::resource('whatsapp-grupos', WhatsAppGrupoController::class)->except(['show']);

    Route::resource('usuarios', UserController::class)
        ->except(['show'])
        ->middleware('can:gerenciar-usuarios');
});

require __DIR__.'/auth.php';
