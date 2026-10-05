<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\PapelPadrao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * O cadastro público sempre cria um Cliente: usuário + perfil_cliente na mesma transação.
     * Administradores são promovidos depois (comando app:promover-administrador).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'maior_de_18' => ['accepted'],
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'papel_padrao' => PapelPadrao::Cliente,
            ]);

            $user->perfilCliente()->create([
                'maior_de_18' => true,
                'data_cadastro' => now()->toDateString(),
            ]);

            // TODO (Fase 5): criar a Assinatura do plano Free no cadastro (decisão de 01/10/2026).

            return $user;
        });
    }
}
