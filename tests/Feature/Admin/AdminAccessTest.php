<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('users without an administrator profile cannot access the admin panel', function () {
    $user = User::factory()->cliente()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('administrators can access the admin panel', function () {
    $user = User::factory()->administrador()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('users with both client and administrator profiles can access the admin panel', function () {
    $user = User::factory()->cliente()->administrador()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('a deleted administrator profile no longer grants access', function () {
    $user = User::factory()->administrador()->create();
    $user->perfilAdministrador->delete();

    $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertForbidden();
});

test('the admin menu flag is shared only with administrators', function (bool $isAdministrador) {
    $factory = User::factory();
    $user = ($isAdministrador ? $factory->administrador() : $factory->cliente())->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.isAdministrador', $isAdministrador));
})->with([
    'administrador' => true,
    'cliente' => false,
]);
