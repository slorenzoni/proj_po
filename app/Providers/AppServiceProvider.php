<?php

namespace App\Providers;

use App\Enums\AreaAdmin;
use App\Models\User;
use App\Support\RespostaDeLimite;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarLimitesGerais();
        $this->configureDefaults();
        $this->registerBlueprintMacros();
        $this->registerGates();
    }

    /**
     * Colunas padrão exigidas pelo dicionário de dados em todas as tabelas.
     */
    protected function registerBlueprintMacros(): void
    {
        /**
         * UUID público, usado apenas em URLs (route model binding). A PK continua BIGINT.
         * Índice único comum (não parcial): um UUID nunca é reutilizado, nem após soft delete.
         */
        Blueprint::macro('publicUuid', function (): ColumnDefinition {
            /** @var Blueprint $this */
            return $this->uuid('uuid')->unique();
        });

        /**
         * Bloco audit_* preenchido automaticamente pelo trait App\Concerns\Auditable.
         */
        Blueprint::macro('auditColumns', function (): void {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('audit_id_user')->nullable();
            $this->string('audit_name_user', 255)->nullable();
            $this->text('audit_origin_url')->nullable();
            $this->string('audit_request_method', 255)->nullable();
            $this->text('audit_http_referer')->nullable();
            $this->text('audit_route_name')->nullable();
            $this->text('audit_controller_action')->nullable();
            $this->string('audit_origin_ip', 45)->nullable();
            $this->text('audit_browser')->nullable();
            $this->string('audit_db_user', 255)->nullable();
        });
    }

    /**
     * Autorizações globais.
     */
    /**
     * Limites gerais (SEGURANCA.md, PM4): as telas logadas por usuário, e o palpite e o placar
     * dos fãs (que não tinham limite nenhum; comentários e dicas já têm 10/min nas rotas).
     */
    protected function configurarLimitesGerais(): void
    {
        $quem = fn (Request $request): string => (string) ($request->user()?->id ?: $request->ip());

        // Uso normal fica muito abaixo disso; serve para frear robô ou script.
        RateLimiter::for('usuario', fn (Request $request) => Limit::perMinute(120)->by('usuario:'.$quem($request)));

        RateLimiter::for('votos', function (Request $request) use ($quem) {
            $resposta = RespostaDeLimite::noAviso('Muitos envios em pouco tempo. Tente novamente em :tempo.');

            return [
                Limit::perMinute(20)->by('votos-min:'.$quem($request))->response($resposta),
                Limit::perHour(300)->by('votos-hora:'.$quem($request))->response($resposta),
            ];
        });
    }

    protected function registerGates(): void
    {
        Gate::define('acessar-admin', fn (User $user): bool => $user->isAdministrador());

        foreach (AreaAdmin::cases() as $area) {
            Gate::define($area->gate(), fn (User $user): bool => $user->podeAcessarArea($area));
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Com APP_URL em https, todo endereço gerado (redirecionamentos inclusive) sai em https,
        // mesmo que a requisição chegue em http vinda do proxy. Sem isso o login volta para
        // http e o cookie de sessão "secure" não é enviado.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
