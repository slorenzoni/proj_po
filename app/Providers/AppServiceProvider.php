<?php

namespace App\Providers;

use App\Enums\AreaAdmin;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
