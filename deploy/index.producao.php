<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
| index.php de PRODUÇÃO (Locaweb). Vai para public_html/po, que é a raiz do subdomínio
| po.vipti.com.br; a aplicação fica fora da área pública, em ~/po_app.
| Copiado pelo deploy/montar-pacote.sh — não é usado no ambiente local.
*/
$aplicacao = '/home/storage/d/66/10/vipti2/po_app';

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $aplicacao.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $aplicacao.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $aplicacao.'/bootstrap/app.php';

// A pasta pública é esta, não po_app/public (usada pelo Vite para achar os arquivos compilados).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
