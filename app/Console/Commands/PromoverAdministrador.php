<?php

namespace App\Console\Commands;

use App\Enums\NivelAcesso;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Concede o perfil de administrador a um usuário já cadastrado.
 * Necessário para criar o primeiro admin, já que o cadastro público só cria clientes.
 */
#[Signature('app:promover-administrador {email : E-mail do usuário} {--nivel=super-admin : super-admin, moderador ou cadastrador}')]
#[Description('Concede (ou altera) o perfil de administrador de um usuário existente')]
class PromoverAdministrador extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nivel = NivelAcesso::tryFrom((string) $this->option('nivel'));

        if ($nivel === null) {
            $this->error('Nível inválido. Use: '.implode(', ', array_column(NivelAcesso::cases(), 'value')).'.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Usuário não encontrado. Ele precisa se cadastrar antes de ser promovido.');

            return self::FAILURE;
        }

        $user->perfilAdministrador()->updateOrCreate([], ['nivel_acesso' => $nivel]);

        $this->info("{$user->email} agora é administrador ({$nivel->value}).");

        return self::SUCCESS;
    }
}
