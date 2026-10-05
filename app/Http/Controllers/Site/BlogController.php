<?php

namespace App\Http\Controllers\Site;

use App\Enums\PosicaoBanner;
use App\Enums\StatusPostagem;
use App\Http\Controllers\Controller;
use App\Models\Postagem;
use App\Services\ExibicaoDeBanners;
use App\Services\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private readonly MediaStorage $media) {}

    public function index(ExibicaoDeBanners $banners): Response
    {
        return Inertia::render('site/blog/Index', [
            'postagens' => $this->publicadas()
                ->latest('data_publicacao')
                ->paginate(9)
                ->through(fn (Postagem $postagem): array => [
                    'uuid' => $postagem->uuid,
                    'slug' => $postagem->slug,
                    'titulo' => $postagem->titulo,
                    'resumo' => $postagem->meta_description,
                    'capa' => $this->media->url($postagem->imagem_capa),
                    'patrocinado' => $postagem->patrocinado,
                    'data_publicacao' => $postagem->data_publicacao?->toDateString(),
                ]),
            'banners' => $banners->para(PosicaoBanner::Blog),
        ]);
    }

    /**
     * Rascunhos, arquivadas e postagens com data futura não são públicas.
     */
    public function show(string $slug, ExibicaoDeBanners $banners): Response
    {
        $postagem = $this->publicadas()
            ->with(['user:id,name', 'patrocinador:id,nome,link_site', 'categoria:id,nome'])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('site/blog/Show', [
            'postagem' => [
                'titulo' => $postagem->titulo,
                'resumo' => $postagem->meta_description,
                // O conteúdo é tratado como Markdown; HTML digitado é exibido como texto,
                // nunca interpretado, e links com esquemas perigosos são removidos.
                'conteudo_html' => Str::markdown($postagem->conteudo, [
                    'html_input' => 'escape',
                    'allow_unsafe_links' => false,
                ]),
                'capa' => $this->media->url($postagem->imagem_capa),
                'autor' => $postagem->user?->name,
                'categoria' => $postagem->categoria?->nome,
                'data_publicacao' => $postagem->data_publicacao?->toDateString(),
                'patrocinador' => $postagem->patrocinador === null ? null : [
                    'nome' => $postagem->patrocinador->nome,
                    'link' => $postagem->patrocinador->link_site,
                ],
                'fonte_original_url' => $postagem->fonte_original_url,
            ],
            'banners' => $banners->para(PosicaoBanner::Blog),
        ]);
    }

    /**
     * @return Builder<Postagem>
     */
    private function publicadas(): Builder
    {
        return Postagem::query()
            ->where('status', StatusPostagem::Publicado)
            ->whereDate('data_publicacao', '<=', today());
    }
}
