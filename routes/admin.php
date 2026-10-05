<?php

use Illuminate\Support\Facades\Route;

/*
| Painel administrativo. O acesso exige perfil_administrador ativo (gate "acessar-admin").
| O que cada admin pode fazer lá dentro será controlado pelo nivel_acesso nas próximas fases.
*/
Route::middleware(['auth', 'verified', 'can:acessar-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');
    });
