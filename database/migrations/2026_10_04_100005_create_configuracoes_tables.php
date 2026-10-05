<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuração da pontuação dos palpites e dos pesos por momento da troca.
 *
 * Nas duas tabelas, categoria_id nulo é o padrão geral; uma linha com categoria
 * preenchida substitui o padrão só para aquela modalidade.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracoes_pontuacao', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias');
            $table->integer('pontos_vencedor');
            $table->integer('pontos_vencedor_metodo');
            $table->integer('pontos_vencedor_round');
            // Vencedor + método + round.
            $table->integer('pontos_perfeito');
            // Tempo, após o fim do round, em que o usuário ainda pode pontuá-lo no placar dos fãs.
            $table->integer('prazo_placar_fans_minutos');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('pesos_troca_palpite', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias');
            // Duração da luta a que a grade se aplica (3 ou 5 rounds).
            $table->integer('numero_rounds');
            // 0 = pré-luta; N = troca feita no intervalo após o round N.
            $table->integer('round_da_troca');
            // Percentual aplicado sobre a pontuação base.
            $table->decimal('peso', 5, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['numero_rounds', 'round_da_troca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesos_troca_palpite');
        Schema::dropIfExists('configuracoes_pontuacao');
    }
};
