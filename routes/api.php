<?php

use App\Http\Controllers\CspRelatorioController;
use Illuminate\Support\Facades\Route;

/*
| Rotas da API: sem sessão e sem CSRF, com o limite 'api' (AppServiceProvider).
|
| Relatos de violação de CSP mandados pelo navegador (SEGURANCA.md, PM1).
*/
Route::post('csp-relatorio', CspRelatorioController::class)->name('csp.relatorio');
