<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Monta os valores do bloco audit_* a partir do contexto da execução atual
 * (usuário autenticado, requisição HTTP e usuário do banco de dados).
 */
class AuditContext
{
    public function __construct(
        protected Application $app,
        protected AuthFactory $auth,
        protected ConfigRepository $config,
    ) {}

    /**
     * @return array{
     *     audit_id_user: int|null,
     *     audit_name_user: string|null,
     *     audit_origin_url: string|null,
     *     audit_request_method: string|null,
     *     audit_http_referer: string|null,
     *     audit_route_name: string|null,
     *     audit_controller_action: string|null,
     *     audit_origin_ip: string|null,
     *     audit_browser: string|null,
     *     audit_db_user: string|null,
     * }
     */
    public function attributes(): array
    {
        $user = $this->auth->guard()->user();

        return [
            'audit_id_user' => $user?->getAuthIdentifier(),
            'audit_name_user' => $user?->getAttribute('name') ?? ($this->isConsole() ? 'console' : null),
            ...$this->requestAttributes(),
            'audit_db_user' => $this->databaseUser(),
        ];
    }

    /**
     * Dados da requisição HTTP. Em comandos Artisan/filas não há requisição real,
     * então registra apenas o comando executado.
     *
     * @return array{
     *     audit_origin_url: string|null,
     *     audit_request_method: string|null,
     *     audit_http_referer: string|null,
     *     audit_route_name: string|null,
     *     audit_controller_action: string|null,
     *     audit_origin_ip: string|null,
     *     audit_browser: string|null,
     * }
     */
    protected function requestAttributes(): array
    {
        if ($this->isConsole()) {
            return [
                'audit_origin_url' => 'console: '.implode(' ', (array) ($_SERVER['argv'] ?? [])),
                'audit_request_method' => null,
                'audit_http_referer' => null,
                'audit_route_name' => null,
                'audit_controller_action' => null,
                'audit_origin_ip' => null,
                'audit_browser' => null,
            ];
        }

        /** @var Request $request */
        $request = $this->app->make('request');
        $route = $request->route();

        return [
            'audit_origin_url' => $request->fullUrl(),
            'audit_request_method' => $request->method(),
            'audit_http_referer' => $request->headers->get('referer'),
            'audit_route_name' => $route?->getName(),
            'audit_controller_action' => $route?->getActionName(),
            'audit_origin_ip' => $request->ip(),
            'audit_browser' => $request->userAgent(),
        ];
    }

    /**
     * Usuário de banco configurado na conexão padrão (o mesmo que executa a escrita).
     */
    protected function databaseUser(): ?string
    {
        $connection = $this->config->get('database.default');

        return $this->config->get("database.connections.{$connection}.username");
    }

    /**
     * Testes rodam "no console", mas simulam requisições HTTP — por isso são tratados como HTTP.
     */
    protected function isConsole(): bool
    {
        return $this->app->runningInConsole() && ! $this->app->runningUnitTests();
    }
}
