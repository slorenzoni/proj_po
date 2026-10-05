<?php

namespace Database\Seeders;

use App\Models\Papel;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de papéis "só-permissão". Idempotente: pode rodar em produção.
 *
 * Não usa WithoutModelEvents: o UUID e a auditoria são gerados por eventos do model.
 */
class PapelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Papel::query()->firstOrCreate(
            ['nome' => Papel::COMENTARISTA],
            ['descricao' => 'Pode comentar nas lutas com destaque, mesmo sem plano Membro.'],
        );
    }
}
