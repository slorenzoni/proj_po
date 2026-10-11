<?php

/*
| Configurações de segurança do PROJ_PO (SEGURANCA.md).
*/

return [

    /*
    | Content-Security-Policy (PM1). Diz ao navegador de onde a pagina pode
    | carregar script, estilo, imagem, fonte e iframe - barreira extra
    | contra XSS e contra embutir o site em outro.
    |
    | 'relatorio': o navegador NAO bloqueia nada, so relata o que bloquearia
    |              (log storage/logs/csp-AAAA-MM-DD.log). Modo inicial: uma
    |              CSP errada quebra o site, entao primeiro se observa.
    | 'bloquear':  vale de verdade. Passar pra ele (CSP_MODO=bloquear no .env
    |              + config:cache) depois de alguns dias sem relato inesperado.
    | 'desligado': nao envia CSP (emergencia).
    */
    'csp' => [
        'modo' => env('CSP_MODO', 'relatorio'),
    ],

    /*
    | Strict-Transport-Security: o navegador passa a so usar https neste
    | dominio pelo tempo indicado. Sem includeSubDomains e sem preload de
    | proposito (o dominio principal e outros subdominios nao sao deste
    | sistema). 0 = nao envia.
    */
    'hsts_segundos' => (int) env('HSTS_SEGUNDOS', 31536000),

];
