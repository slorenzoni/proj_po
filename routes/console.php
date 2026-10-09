<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Hospedagem compartilhada não mantém um worker de fila rodando o tempo todo. O cron do
| servidor chama "schedule:run" a cada minuto, e ele processa o que estiver na fila
| (pontuação e ranking das lutas encerradas) e sai.
*/
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
