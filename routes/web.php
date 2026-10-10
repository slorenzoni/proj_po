<?php

use App\Http\Controllers\Site\AtletaController;
use App\Http\Controllers\Site\BannerCliqueController;
use App\Http\Controllers\Site\ComentarioController;
use App\Http\Controllers\Site\DicaController;
use App\Http\Controllers\Site\EventoController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LutaController;
use App\Http\Controllers\Site\PainelController;
use App\Http\Controllers\Site\PalpiteController;
use App\Http\Controllers\Site\PlacarFanController;
use App\Http\Controllers\Site\RankingController;
use App\Http\Controllers\Site\SolicitacaoVerificacaoController;
use Illuminate\Support\Facades\Route;

/*
| Site público: qualquer visitante vê eventos, lutas, atletas e ranking.
| Participar (palpitar, pontuar rounds, comentar) exige conta com e-mail confirmado.
*/
Route::get('/', HomeController::class)->name('home');

Route::get('eventos', [EventoController::class, 'index'])->name('eventos.index');
Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
Route::get('lutas/{luta}', [LutaController::class, 'show'])->name('lutas.show');
Route::get('atletas/{atleta}', [AtletaController::class, 'show'])->name('atletas.show');

Route::get('ranking', [RankingController::class, 'geral'])->name('ranking.geral');
Route::get('ranking/eventos/{evento}', [RankingController::class, 'evento'])->name('ranking.evento');
Route::get('ranking/organizacoes/{organizacao}', [RankingController::class, 'organizacao'])->name('ranking.organizacao');

Route::get('banners/{banner}/clique', BannerCliqueController::class)->name('banners.clique');

// throttle:usuario — limite geral por usuário nas telas logadas (SEGURANCA.md, PM4).
Route::middleware(['auth', 'verified', 'throttle:usuario'])->group(function () {
    Route::get('dashboard', PainelController::class)->name('dashboard');

    Route::post('lutas/{luta}/palpite', [PalpiteController::class, 'store'])
        ->middleware('throttle:votos')
        ->name('palpites.store');
    Route::post('lutas/{luta}/placar-dos-fans', [PlacarFanController::class, 'store'])
        ->middleware('throttle:votos')
        ->name('placar-dos-fans.store');
    Route::post('lutas/{luta}/comentarios', [ComentarioController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('comentarios.store');

    Route::post('lutas/{luta}/dicas', [DicaController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('dicas.store');

    Route::get('verificacao', [SolicitacaoVerificacaoController::class, 'index'])->name('verificacao.index');
    Route::post('verificacao', [SolicitacaoVerificacaoController::class, 'store'])->name('verificacao.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
