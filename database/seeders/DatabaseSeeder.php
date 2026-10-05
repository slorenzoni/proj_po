<?php

namespace Database\Seeders;

use App\Enums\NivelAcesso;
use App\Enums\PapelPadrao;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Não usa WithoutModelEvents: o UUID e a auditoria são gerados por eventos dos models.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PapelSeeder::class);

        // Usuário de desenvolvimento com os dois perfis (cliente + super-admin).
        User::factory()
            ->cliente()
            ->administrador(NivelAcesso::SuperAdmin)
            ->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'papel_padrao' => PapelPadrao::Administrador,
            ]);
    }
}
