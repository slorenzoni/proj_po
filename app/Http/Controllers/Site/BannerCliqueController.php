<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ExibicaoDeBanners;
use Illuminate\Http\RedirectResponse;

class BannerCliqueController extends Controller
{
    /**
     * Conta o clique e leva ao site do patrocinador. O destino é sempre o link
     * cadastrado no painel — nunca um endereço vindo da requisição.
     */
    public function __invoke(Banner $banner, ExibicaoDeBanners $banners): RedirectResponse
    {
        $destino = $banners->registrarClique($banner);

        abort_if($destino === null, 404);

        return redirect()->away($destino);
    }
}
