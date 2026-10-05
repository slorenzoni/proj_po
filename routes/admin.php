<?php

use App\Enums\AreaAdmin;
use App\Http\Controllers\Admin\AndamentoLutaController;
use App\Http\Controllers\Admin\AtletaController;
use App\Http\Controllers\Admin\AtletaEstiloController;
use App\Http\Controllers\Admin\AtletaFotoController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\CategoriaPesoController;
use App\Http\Controllers\Admin\ConfiguracaoPontuacaoController;
use App\Http\Controllers\Admin\EstiloDeLutaController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\JuizController;
use App\Http\Controllers\Admin\LutaController;
use App\Http\Controllers\Admin\LutaJuizController;
use App\Http\Controllers\Admin\OrganizacaoController;
use App\Http\Controllers\Admin\PatrocinadorController;
use App\Http\Controllers\Admin\PlacarController;
use App\Http\Controllers\Admin\PostagemController;
use App\Http\Controllers\Admin\TreinadorController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\VerificacaoController;
use Illuminate\Support\Facades\Route;

/*
| Painel administrativo. O acesso exige perfil_administrador ativo (gate "acessar-admin").
| Dentro dele, cada área é liberada pelo nível de acesso (gates "admin.*", ver NivelAcesso::areas()).
| Os nomes de parâmetro são explícitos porque o singular automático do Laravel é em inglês.
*/
Route::middleware(['auth', 'verified', 'can:acessar-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');

        Route::middleware('can:'.AreaAdmin::Cadastros->gate())->group(function () {
            Route::resource('organizacoes', OrganizacaoController::class)
                ->parameters(['organizacoes' => 'organizacao'])->except('show');

            Route::resource('categorias', CategoriaController::class)
                ->parameters(['categorias' => 'categoria'])->except('show');
            Route::post('categorias/{categoria}/pesos', [CategoriaPesoController::class, 'store'])->name('categorias.pesos.store');
            Route::put('categorias-de-peso/{peso}', [CategoriaPesoController::class, 'update'])->name('pesos.update');
            Route::delete('categorias-de-peso/{peso}', [CategoriaPesoController::class, 'destroy'])->name('pesos.destroy');

            Route::resource('estilos-de-luta', EstiloDeLutaController::class)
                ->parameters(['estilos-de-luta' => 'estilo'])->names('estilos')->except('show');

            Route::resource('juizes', JuizController::class)
                ->parameters(['juizes' => 'juiz'])->except('show');

            Route::resource('treinadores', TreinadorController::class)
                ->parameters(['treinadores' => 'treinador'])->except('show');

            Route::resource('atletas', AtletaController::class)
                ->parameters(['atletas' => 'atleta'])->except('show');
            Route::post('atletas/{atleta}/fotos', [AtletaFotoController::class, 'store'])->name('atletas.fotos.store');
            Route::put('fotos-de-atleta/{foto}/principal', [AtletaFotoController::class, 'update'])->name('fotos.principal');
            Route::delete('fotos-de-atleta/{foto}', [AtletaFotoController::class, 'destroy'])->name('fotos.destroy');
            Route::post('atletas/{atleta}/estilos', [AtletaEstiloController::class, 'store'])->name('atletas.estilos.store');
            Route::delete('estilos-de-atleta/{vinculo}', [AtletaEstiloController::class, 'destroy'])->name('atletas.estilos.destroy');

            Route::resource('eventos', EventoController::class)
                ->parameters(['eventos' => 'evento']);
            Route::get('eventos/{evento}/lutas/create', [LutaController::class, 'create'])->name('eventos.lutas.create');
            Route::post('eventos/{evento}/lutas', [LutaController::class, 'store'])->name('eventos.lutas.store');

            Route::get('lutas/{luta}/edit', [LutaController::class, 'edit'])->name('lutas.edit');
            Route::put('lutas/{luta}', [LutaController::class, 'update'])->name('lutas.update');
            Route::delete('lutas/{luta}', [LutaController::class, 'destroy'])->name('lutas.destroy');
            Route::post('lutas/{luta}/juizes', [LutaJuizController::class, 'store'])->name('lutas.juizes.store');
            Route::delete('juizes-de-luta/{vinculo}', [LutaJuizController::class, 'destroy'])->name('lutas.juizes.destroy');

            Route::get('lutas/{luta}/andamento', [AndamentoLutaController::class, 'show'])->name('lutas.andamento');
            Route::post('lutas/{luta}/iniciar', [AndamentoLutaController::class, 'iniciar'])->name('lutas.iniciar');
            Route::post('lutas/{luta}/encerrar-round', [AndamentoLutaController::class, 'encerrarRound'])->name('lutas.encerrar-round');
            Route::post('lutas/{luta}/proximo-round', [AndamentoLutaController::class, 'iniciarProximoRound'])->name('lutas.proximo-round');
            Route::post('lutas/{luta}/encerrar', [AndamentoLutaController::class, 'encerrar'])->name('lutas.encerrar');
            Route::post('lutas/{luta}/cancelar', [AndamentoLutaController::class, 'cancelar'])->name('lutas.cancelar');
            Route::post('lutas/{luta}/placares', [PlacarController::class, 'store'])->name('lutas.placares.store');
            Route::delete('placares/{placar}', [PlacarController::class, 'destroy'])->name('placares.destroy');

            Route::resource('patrocinadores', PatrocinadorController::class)
                ->parameters(['patrocinadores' => 'patrocinador'])->except('show');
            Route::resource('banners', BannerController::class)->except('show');
            Route::resource('postagens', PostagemController::class)
                ->parameters(['postagens' => 'postagem'])->except('show');
        });

        Route::middleware('can:'.AreaAdmin::Verificacoes->gate())->group(function () {
            Route::get('verificacoes', [VerificacaoController::class, 'index'])->name('verificacoes.index');
            Route::get('verificacoes/{solicitacao}/documento', [VerificacaoController::class, 'documento'])->name('verificacoes.documento');
            Route::post('verificacoes/{solicitacao}/aprovar', [VerificacaoController::class, 'aprovar'])->name('verificacoes.aprovar');
            Route::post('verificacoes/{solicitacao}/rejeitar', [VerificacaoController::class, 'rejeitar'])->name('verificacoes.rejeitar');
        });

        Route::middleware('can:'.AreaAdmin::Usuarios->gate())->group(function () {
            Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
            Route::post('usuarios/{usuario}/papeis', [UsuarioController::class, 'atribuirPapel'])->name('usuarios.papeis.store');
            Route::delete('usuarios/{usuario}/papeis/{papel}', [UsuarioController::class, 'removerPapel'])->name('usuarios.papeis.destroy');
            Route::put('usuarios/{usuario}/plano', [UsuarioController::class, 'definirPlano'])->name('usuarios.plano.update');
            Route::put('usuarios/{usuario}/administrador', [UsuarioController::class, 'definirAdministrador'])->name('usuarios.administrador.update');
            Route::delete('usuarios/{usuario}/administrador', [UsuarioController::class, 'revogarAdministrador'])->name('usuarios.administrador.destroy');
        });

        Route::middleware('can:'.AreaAdmin::Configuracoes->gate())->group(function () {
            Route::get('configuracoes/pontuacao', [ConfiguracaoPontuacaoController::class, 'edit'])->name('configuracoes.pontuacao.edit');
            Route::put('configuracoes/pontuacao', [ConfiguracaoPontuacaoController::class, 'updatePontuacao'])->name('configuracoes.pontuacao.update');
            Route::put('configuracoes/pesos', [ConfiguracaoPontuacaoController::class, 'updatePesos'])->name('configuracoes.pesos.update');
            Route::delete('configuracoes/categorias/{categoria}', [ConfiguracaoPontuacaoController::class, 'voltarAoPadrao'])->name('configuracoes.categorias.destroy');
        });
    });
