<?php

use App\Enums\StatusLuta;
use App\Models\Dica;
use App\Models\Luta;
use App\Models\Papel;
use App\Models\User;

function verificado(User $user): User
{
    $user->forceFill(['verificado' => true, 'verificado_em' => now()])->save();

    return $user;
}

function darDica(User $user, Luta $luta, string $texto = 'Volkanovski deve controlar a distância com o jab.')
{
    return test()->actingAs($user)->post(route('dicas.store', $luta), ['texto' => $texto]);
}

test('verified members and commentators can give tips', function (Closure $autor) {
    $luta = Luta::factory()->create();
    $user = $autor();

    darDica($user, $luta)->assertSessionHasNoErrors();

    expect(Dica::query()->sole())
        ->user_id->toBe($user->id)
        ->luta_id->toBe($luta->id);
})->with([
    'membro verificado' => [fn () => verificado(membro())],
    'comentarista' => [function () {
        $user = cliente();
        $user->atribuirPapel(Papel::factory()->create(['nome' => Papel::COMENTARISTA]));

        return $user->fresh();
    }],
]);

test('other accounts cannot give tips', function (Closure $autor) {
    darDica($autor(), Luta::factory()->create())->assertForbidden();

    expect(Dica::query()->count())->toBe(0);
})->with([
    'cliente free' => [fn () => cliente()],
    'membro sem selo' => [fn () => membro()],
    'verificado que deixou de ser membro' => [fn () => verificado(cliente())],
    'administrador' => [fn () => administrador()],
]);

test('guests must log in to give tips', function () {
    $this->post(route('dicas.store', Luta::factory()->create()), ['texto' => 'Dica'])
        ->assertRedirect(route('login'));
});

test('a tip needs text, has a size limit and is closed after the fight ends', function (string $texto, StatusLuta $status) {
    darDica(verificado(membro()), Luta::factory()->create(['status' => $status]), $texto)
        ->assertSessionHasErrors('texto');

    expect(Dica::query()->count())->toBe(0);
})->with([
    'vazia' => ['', StatusLuta::Agendada],
    'longa demais' => [str_repeat('a', 2001), StatusLuta::Agendada],
    'luta encerrada' => ['Dica atrasada', StatusLuta::Encerrada],
    'luta cancelada' => ['Dica inútil', StatusLuta::Cancelada],
]);

test('everyone sees the tips, with the highlight of who wrote them', function () {
    $luta = Luta::factory()->create();
    $verificado = verificado(membro());
    Dica::factory()->for($luta)->for($verificado)->create(['texto' => 'Dica do verificado']);
    Dica::factory()->create();

    $this->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->has('dicas', 1)
            ->where('dicas.0.texto', 'Dica do verificado')
            ->where('dicas.0.destaque', 'Verificado')
            ->where('podeDarDica', false));

    $this->actingAs($verificado)->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page->where('podeDarDica', true));
});
