<?php

use App\Enums\EscopoRanking;
use App\Enums\PlanoAssinatura;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Enums\StatusEvento;
use App\Enums\StatusSolicitacaoVerificacao;
use App\Models\Atleta;
use App\Models\Banner;
use App\Models\Evento;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\Ranking;
use App\Models\SolicitacaoVerificacao;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('visitors can browse the public pages without an account', function () {
    $luta = Luta::factory()->create();

    $this->get(route('home'))->assertInertia(fn ($page) => $page->component('site/Home'));
    $this->get(route('eventos.index'))->assertInertia(fn ($page) => $page->component('site/eventos/Index'));
    $this->get(route('eventos.show', $luta->evento))->assertInertia(fn ($page) => $page->component('site/eventos/Show')->has('evento.lutas', 1));
    $this->get(route('lutas.show', $luta))->assertInertia(fn ($page) => $page->component('site/lutas/Show'));
    $this->get(route('atletas.show', $luta->participanteA))->assertInertia(fn ($page) => $page->component('site/atletas/Show')->has('lutas', 1));
    $this->get(route('ranking.geral'))->assertInertia(fn ($page) => $page->component('site/ranking/Index'));
    $this->get(route('ranking.evento', $luta->evento))->assertOk();
    $this->get(route('ranking.organizacao', $luta->evento->organizacao))->assertOk();
});

test('the events list separates upcoming from finished events', function () {
    $proximo = Evento::factory()->create(['status' => StatusEvento::Agendado]);
    $encerrado = Evento::factory()->create(['status' => StatusEvento::Encerrado]);

    $this->get(route('eventos.index'))
        ->assertInertia(fn ($page) => $page->has('eventos.data', 1)->where('eventos.data.0.uuid', $proximo->uuid));

    $this->get(route('eventos.index', ['quando' => 'encerrados']))
        ->assertInertia(fn ($page) => $page->has('eventos.data', 1)->where('eventos.data.0.uuid', $encerrado->uuid));
});

test('a ranking page shows only its scope and the position of the visitor', function () {
    $user = User::factory()->create();
    $evento = Evento::factory()->create();
    Ranking::factory()->for($user)->create(['posicao' => 4, 'pontos' => 30]);
    Ranking::factory()->for($user)->create([
        'escopo' => EscopoRanking::Evento, 'referencia_id' => $evento->id, 'posicao' => 1, 'pontos' => 10,
    ]);

    // Visitante primeiro: depois do actingAs() a sessão do teste continua logada.
    $this->get(route('ranking.geral'))
        ->assertInertia(fn ($page) => $page
            ->where('linhas.data.0.pontos', '30.00')
            ->where('linhas.data.0.sou_eu', false)
            ->where('minhaPosicao', null));

    $this->actingAs($user)->get(route('ranking.evento', $evento))
        ->assertInertia(fn ($page) => $page
            ->has('linhas.data', 1)
            ->where('linhas.data.0.sou_eu', true)
            ->where('minhaPosicao', ['posicao' => 1, 'pontos' => '10.00']));
});

test('a banner is shown only while active and inside its period, counting the impression', function () {
    Storage::fake('public');
    $exibido = Banner::factory()->create(['posicao' => PosicaoBanner::Home, 'data_inicio' => today()->subDay(), 'data_fim' => today()]);
    Banner::factory()->create(['posicao' => PosicaoBanner::Home, 'status' => StatusBanner::Pausado]);
    Banner::factory()->create(['posicao' => PosicaoBanner::Home, 'data_inicio' => today()->addDay()]);
    Banner::factory()->create(['posicao' => PosicaoBanner::Home, 'data_inicio' => today()->subWeek(), 'data_fim' => today()->subDay()]);
    $outraPosicao = Banner::factory()->create(['posicao' => PosicaoBanner::Evento]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->has('banners', 1)->where('banners.0.uuid', $exibido->uuid));

    expect($exibido->refresh()->impressoes)->toBe(1)
        ->and($outraPosicao->refresh()->impressoes)->toBe(0);
});

test('clicking a banner counts the click and goes to the sponsor link', function () {
    $banner = Banner::factory()->create(['link_destino' => 'https://patrocinador.example/promo']);

    $this->get(route('banners.clique', $banner))->assertRedirect('https://patrocinador.example/promo');

    expect($banner->refresh()->cliques)->toBe(1);
});

test('a banner without a link has no click address', function () {
    $this->get(route('banners.clique', Banner::factory()->create(['link_destino' => null])))->assertNotFound();
});

test('the client panel summarises the score and lists only the own picks', function () {
    $user = membro();
    $meu = Palpite::factory()->for($user)->create(['pontos_obtidos' => 15]);
    Palpite::factory()->create();
    Ranking::factory()->for($user)->create(['posicao' => 3, 'pontos' => 15, 'palpites_perfeitos' => 0]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('resumo.plano', PlanoAssinatura::Membro->label())
            ->where('resumo.pontos', '15.00')
            ->where('resumo.posicao', 3)
            ->where('resumo.palpites', 1)
            ->has('palpites.data', 1)
            ->where('palpites.data.0.uuid', $meu->uuid));
});

test('a member requests the verified badge once at a time', function () {
    Storage::fake('public');
    $user = membro();

    $this->actingAs($user)->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
        'descricao' => 'Sou atleta profissional.',
    ])->assertSessionHasNoErrors();

    $solicitacao = SolicitacaoVerificacao::query()->sole();

    expect($solicitacao)
        ->user_id->toBe($user->id)
        ->status->toBe(StatusSolicitacaoVerificacao::Pendente);

    Storage::disk('public')->assertExists($solicitacao->documento_url);

    $this->actingAs($user)->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->create('outro.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('documento');

    expect(SolicitacaoVerificacao::query()->count())->toBe(1);
});

test('a rejected member can request the badge again', function () {
    Storage::fake('public');
    $user = membro();
    SolicitacaoVerificacao::factory()->for($user)->create(['status' => StatusSolicitacaoVerificacao::Rejeitada]);

    $this->actingAs($user)->get(route('verificacao.index'))
        ->assertInertia(fn ($page) => $page->component('Verificacao')->where('podeSolicitar', true)->has('solicitacoes', 1));

    $this->actingAs($user)->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->image('rg.jpg'),
    ])->assertSessionHasNoErrors();
});

test('the badge request rejects other file types and accounts that are not members', function () {
    Storage::fake('public');

    $this->actingAs(membro())->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->create('virus.exe', 10),
    ])->assertSessionHasErrors('documento');

    $this->actingAs(cliente())->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('documento');

    $this->actingAs(administrador())->post(route('verificacao.store'), [
        'documento' => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('documento');

    expect(SolicitacaoVerificacao::query()->count())->toBe(0);
});

test('an event video link becomes an embeddable address only for videos', function (?string $link, ?string $esperado) {
    expect(Evento::factory()->make(['link_canal_youtube' => $link])->youtubeEmbedUrl())->toBe($esperado);
})->with([
    'vídeo' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'vídeo com outros parâmetros' => ['https://www.youtube.com/watch?t=10&v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'link curto' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'transmissão ao vivo' => ['https://www.youtube.com/live/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'canal' => ['https://www.youtube.com/@ufc', null],
    'outro site' => ['https://example.com/watch?v=dQw4w9WgXcQ', null],
    'vazio' => [null, null],
]);

test('the athlete page shows the result of each fight from the athlete point of view', function () {
    $atleta = Atleta::factory()->create();
    $vitoria = Luta::factory()->create(['participante_a_id' => $atleta->id]);
    $vitoria->update(['vencedor_id' => $atleta->id]);
    $derrota = Luta::factory()->create(['participante_b_id' => $atleta->id]);
    $derrota->update(['vencedor_id' => $derrota->participante_a_id]);

    $this->get(route('atletas.show', $atleta))
        ->assertInertia(fn ($page) => $page
            ->has('lutas', 2)
            ->where('lutas.0.venceu', false)
            ->where('lutas.0.adversario', $derrota->participanteA->nome)
            ->where('lutas.1.venceu', true));
});
