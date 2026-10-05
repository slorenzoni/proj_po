<?php

use App\Enums\NivelAcesso;
use App\Models\Papel;
use App\Models\User;
use App\Models\UserPapel;

beforeEach(function () {
    $this->admin = administrador();
    $this->actingAs($this->admin);
});

test('the list can be searched by name or email', function () {
    User::factory()->create(['name' => 'Maria Souza', 'email' => 'maria@example.com']);
    User::factory()->create(['name' => 'João Lima', 'email' => 'joao@example.com']);

    $this->get(route('admin.usuarios.index', ['busca' => 'maria@']))
        ->assertInertia(fn ($page) => $page
            ->component('admin/usuarios/Index')
            ->has('usuarios.data', 1)
            ->where('usuarios.data.0.name', 'Maria Souza'));
});

test('a user becomes an administrator and can have the level changed', function () {
    $usuario = User::factory()->create();

    $this->put(route('admin.usuarios.administrador.update', $usuario), ['nivel_acesso' => NivelAcesso::Cadastrador->value])
        ->assertSessionHasNoErrors();
    $this->put(route('admin.usuarios.administrador.update', $usuario), ['nivel_acesso' => NivelAcesso::Moderador->value])
        ->assertSessionHasNoErrors();

    expect($usuario->perfilAdministrador()->sole()->nivel_acesso)->toBe(NivelAcesso::Moderador);
});

test('an invalid access level is rejected', function () {
    $usuario = User::factory()->create();

    $this->put(route('admin.usuarios.administrador.update', $usuario), ['nivel_acesso' => 'dono'])
        ->assertSessionHasErrors('nivel_acesso');

    expect($usuario->refresh()->isAdministrador())->toBeFalse();
});

test('revoking the administrator profile removes access to the panel', function () {
    $outroAdmin = administrador(NivelAcesso::Cadastrador);

    $this->delete(route('admin.usuarios.administrador.destroy', $outroAdmin));

    expect($outroAdmin->fresh()->isAdministrador())->toBeFalse();

    $this->actingAs($outroAdmin->fresh())->get(route('admin.dashboard'))->assertForbidden();
});

test('administrators cannot change or revoke their own profile', function () {
    $this->put(route('admin.usuarios.administrador.update', $this->admin), ['nivel_acesso' => NivelAcesso::Cadastrador->value]);
    $this->delete(route('admin.usuarios.administrador.destroy', $this->admin));

    expect($this->admin->perfilAdministrador()->sole()->nivel_acesso)->toBe(NivelAcesso::SuperAdmin);
});

test('a role is assigned once and removed keeping its history', function () {
    $usuario = User::factory()->create();
    $papel = Papel::factory()->create();

    $this->post(route('admin.usuarios.papeis.store', $usuario), ['papel_id' => $papel->id])->assertSessionHasNoErrors();
    $this->post(route('admin.usuarios.papeis.store', $usuario), ['papel_id' => $papel->id])->assertSessionHasNoErrors();

    expect($usuario->papeis()->count())->toBe(1);

    $this->delete(route('admin.usuarios.papeis.destroy', [$usuario, $papel]));

    expect($usuario->papeis()->count())->toBe(0)
        ->and(UserPapel::withTrashed()->count())->toBe(1);
});

test('the user page offers only the roles not yet assigned', function () {
    $usuario = User::factory()->create();
    $atribuido = Papel::factory()->create();
    $disponivel = Papel::factory()->create();
    $usuario->atribuirPapel($atribuido);

    $this->get(route('admin.usuarios.show', $usuario))
        ->assertInertia(fn ($page) => $page
            ->component('admin/usuarios/Show')
            ->where('ehProprioUsuario', false)
            ->has('papeisDisponiveis', 1)
            ->where('papeisDisponiveis.0.value', $disponivel->id));
});
