<?php

namespace App\Http\Controllers\Site;

use App\Enums\MetodoPalpite;
use App\Enums\PosicaoBanner;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ApresentaLutas;
use App\Models\Luta;
use App\Models\Mensagem;
use App\Models\Palpite;
use App\Models\Placar;
use App\Models\PlacarFan;
use App\Models\User;
use App\Services\ExibicaoDeBanners;
use App\Services\Palpites\RegistradorDePalpite;
use App\Services\PlacarDosFans;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página da luta: vídeo, palpite, placar dos fãs e comentários. Não há tempo real
 * (decisão de 01/10/2026) — tudo reflete o momento em que a página foi carregada.
 */
class LutaController extends Controller
{
    use ApresentaLutas;

    /**
     * Comentários exibidos por página carregada.
     */
    private const LIMITE_DE_COMENTARIOS = 50;

    public function show(
        Request $request,
        Luta $luta,
        RegistradorDePalpite $registrador,
        PlacarDosFans $placarDosFans,
        ExibicaoDeBanners $banners,
    ): Response {
        $luta->load([...self::RELACOES_DA_LUTA, 'evento', 'categoria:id,nome,usa_rounds']);

        /** @var User|null $user */
        $user = $request->user();
        $usaRounds = $luta->numero_rounds !== null;
        $janela = $user === null ? null : $registrador->janela($luta, $user);

        return Inertia::render('site/lutas/Show', [
            'luta' => [
                ...$this->resumoDaLuta($luta),
                'categoria' => $luta->categoria->nome,
                'usa_rounds' => $usaRounds,
                'round_atual' => $luta->round_atual,
                'em_intervalo' => $luta->em_intervalo,
                'tempo_fim' => $luta->tempo_fim,
                'chance_do_a' => $luta->chance_do_a,
                'chance_do_b' => $luta->chance_do_b,
                'evento' => [
                    'uuid' => $luta->evento->uuid,
                    'nome' => $luta->evento->nome,
                    'data' => $luta->evento->data->toIso8601String(),
                    'video' => $luta->evento->youtubeEmbedUrl(),
                    'link_youtube' => $luta->evento->link_canal_youtube,
                ],
            ],
            'palpite' => [
                'janela' => $janela === null ? null : [
                    'aberta' => $janela->aberta,
                    'ao_vivo' => $janela->aoVivo,
                    'peso' => $janela->peso,
                    'motivo' => $janela->motivo,
                ],
                'meu' => $user === null ? null : $this->palpiteDoUsuario($luta, $user),
                'metodos' => array_map(
                    fn (MetodoPalpite $metodo): array => [
                        'value' => $metodo->value,
                        'label' => $metodo->label(),
                        // Decisão sempre acontece no último round: não se escolhe o round.
                        'permite_round' => $usaRounds && $metodo !== MetodoPalpite::Decisao,
                    ],
                    MetodoPalpite::paraModalidade($usaRounds),
                ),
                'distribuicao' => $this->distribuicaoDosPalpites($luta),
            ],
            'placar' => $usaRounds ? [
                'round_aberto' => $placarDosFans->roundAberto($luta),
                'rounds_pontuados' => $user === null ? [] : PlacarFan::query()
                    ->whereBelongsTo($luta)->whereBelongsTo($user)->orderBy('round')->pluck('round'),
                'medias' => $placarDosFans->medias($luta),
                'oficial' => $luta->placares()->with('juiz:id,nome')->orderBy('round')->get()
                    ->map(fn (Placar $placar): array => [
                        'juiz' => $placar->juiz?->nome,
                        'round' => $placar->round,
                        'pontos_atleta_a' => $placar->pontos_atleta_a,
                        'pontos_atleta_b' => $placar->pontos_atleta_b,
                    ]),
            ] : null,
            'comentarios' => $luta->mensagens()
                ->with(['user.perfilAdministrador', 'user.papeis', 'user.atleta:id,user_id', 'user.treinador:id,user_id'])
                ->latest()
                ->limit(self::LIMITE_DE_COMENTARIOS)
                ->get()
                ->map(fn (Mensagem $mensagem): array => [
                    'uuid' => $mensagem->uuid,
                    'autor' => $mensagem->user?->name,
                    'verificado' => (bool) $mensagem->user?->verificado,
                    'destaque' => $mensagem->user?->destaqueNosComentarios(),
                    'mensagem' => $mensagem->mensagem,
                    'criado_em' => $mensagem->created_at?->toIso8601String(),
                ]),
            'podeComentar' => (bool) $user?->podeComentar(),
            'banners' => [
                'luta' => $banners->para(PosicaoBanner::Luta),
                'chat' => $banners->para(PosicaoBanner::Chat),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function palpiteDoUsuario(Luta $luta, User $user): ?array
    {
        $palpite = Palpite::query()->whereBelongsTo($luta)->whereBelongsTo($user)->first();

        return $palpite === null ? null : [
            'vencedor_id' => $palpite->vencedor_escolhido_id,
            'metodo' => $palpite->metodo_escolhido?->value,
            'metodo_label' => $palpite->metodo_escolhido?->label(),
            'round' => $palpite->round_escolhido,
            'peso_aplicado' => $palpite->peso_aplicado,
            'pontos_obtidos' => $palpite->pontos_obtidos,
        ];
    }

    /**
     * Quantos palpites cada participante recebeu.
     *
     * @return array{a: int, b: int}
     */
    private function distribuicaoDosPalpites(Luta $luta): array
    {
        $porVencedor = $luta->palpites()
            ->toBase()
            ->selectRaw('vencedor_escolhido_id, count(*) as total')
            ->groupBy('vencedor_escolhido_id')
            ->pluck('total', 'vencedor_escolhido_id');

        return [
            'a' => (int) $porVencedor->get($luta->participante_a_id, 0),
            'b' => (int) $porVencedor->get($luta->participante_b_id, 0),
        ];
    }
}
