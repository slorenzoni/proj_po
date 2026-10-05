<?php

use App\Enums\NivelAcesso;
use App\Models\User;

test('promotes an existing user to administrator', function () {
    $user = User::factory()->cliente()->create();

    $this->artisan('app:promover-administrador', ['email' => $user->email])
        ->assertSuccessful();

    $user->refresh();

    expect($user->isAdministrador())->toBeTrue()
        ->and($user->isCliente())->toBeTrue()
        ->and($user->perfilAdministrador->nivel_acesso)->toBe(NivelAcesso::SuperAdmin);
});

test('changes the access level of an existing administrator', function () {
    $user = User::factory()->administrador(NivelAcesso::SuperAdmin)->create();

    $this->artisan('app:promover-administrador', ['email' => $user->email, '--nivel' => 'moderador'])
        ->assertSuccessful();

    expect($user->perfilAdministrador()->count())->toBe(1)
        ->and($user->refresh()->perfilAdministrador->nivel_acesso)->toBe(NivelAcesso::Moderador);
});

test('fails for an unknown email', function () {
    $this->artisan('app:promover-administrador', ['email' => 'ninguem@example.com'])
        ->assertFailed();
});

test('fails for an invalid access level', function () {
    $user = User::factory()->create();

    $this->artisan('app:promover-administrador', ['email' => $user->email, '--nivel' => 'dono'])
        ->assertFailed();

    expect($user->refresh()->isAdministrador())->toBeFalse();
});
