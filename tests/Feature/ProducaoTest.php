<?php

use App\Models\ConfiguracaoPontuacao;
use App\Models\Papel;
use App\Models\PesoTrocaPalpite;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;

test('seeding production creates the catalogs but never the development user', function () {
    app()->detectEnvironment(fn () => 'production');

    // Chamado direto: pelo artisan, o db:seed em produção pediria confirmação.
    app(DatabaseSeeder::class)->setContainer(app())->__invoke();

    expect(User::query()->count())->toBe(0)
        ->and(Papel::query()->where('nome', Papel::COMENTARISTA)->exists())->toBeTrue()
        ->and(ConfiguracaoPontuacao::query()->count())->toBe(1)
        ->and(PesoTrocaPalpite::query()->count())->toBe(8);
});

test('the scheduler processes the queue every minute without overlapping', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($evento) => str_contains($evento->command ?? '', 'queue:work'));

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe('* * * * *')
        ->and($evento->withoutOverlapping)->toBeTrue()
        ->and($evento->command)->toContain('--stop-when-empty');
});

test('with an https app url every generated address uses https, even behind a plain http proxy', function () {
    config(['app.url' => 'https://po.vipti.com.br']);
    (new AppServiceProvider(app()))->boot();

    $user = User::factory()->cliente()->create(['password' => 'senha-de-teste']);

    $this->post('http://po.vipti.com.br/login', ['email' => $user->email, 'password' => 'senha-de-teste'])
        ->assertRedirect('https://po.vipti.com.br/dashboard');
});
