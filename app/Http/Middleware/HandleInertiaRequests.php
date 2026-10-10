<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                // Só controla a exibição do menu; a autorização real é o gate "acessar-admin".
                'isAdministrador' => (bool) $request->user()?->isAdministrador(),
                // Áreas do painel que o nível do administrador enxerga; a autorização real são os gates "admin.*".
                'areasAdmin' => array_column($request->user()?->perfilAdministrador?->nivel_acesso->areas() ?? [], 'value'),
            ],
            // Chave PÚBLICA do captcha do Cloudflare (null = captcha desligado, ex.: testes) — ver
            // components/CaptchaCloudflare.vue e SEGURANCA.md (PG3).
            'captchaSiteKey' => Turnstile::ativo() ? config('services.turnstile.site_key') : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
