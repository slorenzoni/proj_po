<?php

use App\Models\Papel;
use App\Models\User;
use App\Models\UserPapel;

test('assigning a role creates an audited pivot with a public uuid', function () {
    $user = User::factory()->create();
    $papel = Papel::factory()->create(['nome' => Papel::COMENTARISTA]);

    $user->atribuirPapel($papel);

    $vinculo = UserPapel::query()->sole();

    expect($user->hasPapel(Papel::COMENTARISTA))->toBeTrue()
        ->and($vinculo->uuid)->toBeString()->not->toBeEmpty()
        ->and($vinculo->audit_db_user)->not->toBeNull();
});

test('assigning the same role twice keeps a single active link', function () {
    $user = User::factory()->create();
    $papel = Papel::factory()->create();

    $user->atribuirPapel($papel);
    $user->atribuirPapel($papel);

    expect(UserPapel::query()->count())->toBe(1);
});

test('removing a role soft deletes the link', function () {
    $user = User::factory()->create();
    $papel = Papel::factory()->create(['nome' => Papel::COMENTARISTA]);
    $user->atribuirPapel($papel);

    $user->removerPapel($papel);

    expect($user->hasPapel(Papel::COMENTARISTA))->toBeFalse()
        ->and(UserPapel::query()->count())->toBe(0)
        ->and(UserPapel::withTrashed()->count())->toBe(1);
});

test('a removed role can be assigned again', function () {
    $user = User::factory()->create();
    $papel = Papel::factory()->create(['nome' => Papel::COMENTARISTA]);
    $user->atribuirPapel($papel);
    $user->removerPapel($papel);

    $user->atribuirPapel($papel);

    expect($user->hasPapel(Papel::COMENTARISTA))->toBeTrue()
        ->and(UserPapel::withTrashed()->count())->toBe(2);
});

test('models are bound in routes by their public uuid', function () {
    $papel = Papel::factory()->create();

    expect($papel->getRouteKey())->toBe($papel->uuid)
        ->and($papel->getKey())->toBeInt();
});
