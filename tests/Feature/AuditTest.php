<?php

use App\Models\User;

test('writes made through a request record who did it and where', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', 'PestBrowser/1.0')
        ->patch(route('profile.update'), [
            'name' => 'Novo Nome',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->audit_id_user)->toBe($user->id)
        ->and($user->audit_name_user)->toBe('Novo Nome')
        ->and($user->audit_request_method)->toBe('PATCH')
        ->and($user->audit_route_name)->toBe('profile.update')
        ->and($user->audit_origin_url)->toBe(route('profile.update'))
        ->and($user->audit_browser)->toBe('PestBrowser/1.0')
        ->and($user->audit_origin_ip)->toBe('127.0.0.1')
        ->and($user->audit_db_user)->toBe(config('database.connections.mysql.username'));
});

test('soft delete keeps the record and records who deleted it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    $deleted = User::withTrashed()->findOrFail($user->id);

    expect($deleted->trashed())->toBeTrue()
        ->and($deleted->audit_id_user)->toBe($user->id)
        ->and($deleted->audit_route_name)->toBe('profile.destroy')
        ->and($deleted->audit_request_method)->toBe('DELETE');
});

test('audit fields are not exposed to the frontend', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.email', $user->email)
            ->missing('auth.user.audit_origin_ip')
            ->missing('auth.user.audit_browser')
            ->missing('auth.user.audit_id_user')
        );
});
