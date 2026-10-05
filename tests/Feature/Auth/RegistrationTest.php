<?php

use App\Enums\PapelPadrao;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'maior_de_18' => 'on',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration creates a client profile', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'maior_de_18' => 'on',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->isCliente())->toBeTrue()
        ->and($user->isAdministrador())->toBeFalse()
        ->and($user->papel_padrao)->toBe(PapelPadrao::Cliente)
        ->and($user->perfilCliente->maior_de_18)->toBeTrue()
        ->and($user->perfilCliente->data_cadastro->isToday())->toBeTrue();
});

test('registration requires confirming the user is at least 18', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('maior_de_18');
    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('validation messages are shown in brazilian portuguese', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'O campo e-mail é obrigatório.',
        'maior_de_18' => 'É preciso confirmar que você tem 18 anos ou mais.',
    ]);
});

test('email of an active user cannot be reused', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'maior_de_18' => 'on',
    ]);

    $response->assertSessionHasErrors('email');
});

test('email of a deleted user can be used in a new registration', function () {
    User::factory()->create(['email' => 'test@example.com'])->delete();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'maior_de_18' => 'on',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
    expect(User::withTrashed()->where('email', 'test@example.com')->count())->toBe(2);
});
