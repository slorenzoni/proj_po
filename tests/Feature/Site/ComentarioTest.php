<?php

use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\Luta;
use App\Models\Mensagem;
use App\Models\Papel;
use App\Models\Treinador;
use App\Models\User;

function comentar(User $user, Luta $luta, string $mensagem = 'Que luta!')
{
    return test()->actingAs($user)->post(route('comentarios.store', $luta), ['mensagem' => $mensagem]);
}

test('guests must log in to comment', function () {
    $this->post(route('comentarios.store', Luta::factory()->create()), ['mensagem' => 'Oi'])
        ->assertRedirect(route('login'));
});

test('a free client cannot comment', function () {
    comentar(cliente(), Luta::factory()->create())->assertForbidden();

    expect(Mensagem::query()->count())->toBe(0);
});

test('members and special roles can comment', function (Closure $autor) {
    $luta = Luta::factory()->create();
    $user = $autor();

    comentar($user, $luta)->assertSessionHasNoErrors();

    expect(Mensagem::query()->sole())
        ->user_id->toBe($user->id)
        ->luta_id->toBe($luta->id)
        ->mensagem->toBe('Que luta!');
})->with([
    'membro' => [fn () => membro()],
    'administrador' => [fn () => administrador()],
    'comentarista' => [function () {
        $user = cliente();
        $user->atribuirPapel(Papel::factory()->create(['nome' => Papel::COMENTARISTA]));

        return $user->fresh();
    }],
    'atleta com conta' => [fn () => Atleta::factory()->for(User::factory())->create()->user],
    'treinador com conta' => [fn () => Treinador::factory()->for(User::factory())->create()->user],
]);

test('a comment needs text and has a size limit', function (string $mensagem) {
    comentar(membro(), Luta::factory()->create(), $mensagem)->assertSessionHasErrors('mensagem');
})->with([
    'vazio' => '',
    'longo demais' => str_repeat('a', 501),
]);

test('a cancelled fight no longer receives comments', function () {
    comentar(membro(), Luta::factory()->create(['status' => StatusLuta::Cancelada]))
        ->assertSessionHasErrors('mensagem');
});

test('the fight page lists comments with the highlight of special roles', function () {
    $luta = Luta::factory()->create();
    $admin = administrador();
    $membro = membro();
    Mensagem::factory()->for($luta)->for($membro)->create(['mensagem' => 'Primeiro', 'created_at' => now()->subMinute()]);
    Mensagem::factory()->for($luta)->for($admin)->create(['mensagem' => 'Segundo']);
    Mensagem::factory()->create();

    $this->actingAs($membro)->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->where('podeComentar', true)
            ->has('comentarios', 2)
            ->where('comentarios.0.mensagem', 'Segundo')
            ->where('comentarios.0.destaque', 'Administrador')
            ->where('comentarios.1.destaque', null));

    $this->actingAs(cliente())->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page->where('podeComentar', false));
});
