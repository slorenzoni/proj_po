<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos, lutas, juízes escalados por luta e placar oficial.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('organizacao_id')->constrained('organizacoes');
            $table->string('nome', 200);
            $table->dateTime('data');
            $table->string('local', 200)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('pais', 60)->nullable();
            // Decisão 01/10/2026: o vídeo é sempre link/embed do YouTube.
            $table->string('link_canal_youtube', 255)->nullable();
            $table->string('tipo_transmissao', 20)->nullable();
            $table->string('status', 20)->default('agendado');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['status', 'data']);
        });

        Schema::create('lutas', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('evento_id')->constrained('eventos');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('categoria_peso_id')->constrained('categorias_peso');
            $table->foreignId('participante_a_id')->constrained('atletas');
            $table->foreignId('participante_b_id')->constrained('atletas');
            // 1 = luta principal (main event); aumenta indo para o começo do card.
            $table->integer('ordem_na_card');
            $table->string('tipo_card', 20)->nullable();
            // Nulo em modalidades sem rounds (categorias.usa_rounds = false, ex.: Judô).
            $table->integer('numero_rounds')->nullable();
            $table->decimal('chance_do_a', 5, 2)->nullable();
            $table->decimal('chance_do_b', 5, 2)->nullable();
            $table->string('status', 20)->default('agendada');
            // Andamento informado manualmente pelo admin; libera/bloqueia a troca do palpite ao vivo.
            $table->integer('round_atual')->nullable();
            $table->boolean('em_intervalo')->default(false);
            $table->foreignId('vencedor_id')->nullable()->constrained('atletas');
            $table->string('metodo_vitoria', 30)->nullable();
            $table->integer('round_fim')->nullable();
            $table->string('tempo_fim', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['evento_id', 'ordem_na_card']);
        });

        // Decisão 01/10/2026: substitui lutas.juiz_id. O placar só aceita juízes laterais vinculados aqui.
        Schema::create('luta_juizes', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('juiz_id')->constrained('juizes');
            $table->string('funcao', 20);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('placares', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('juiz_id')->constrained('juizes');
            $table->integer('round');
            // Sistema 10-point must (10, 9, 8, 7...).
            $table->integer('pontos_atleta_a');
            $table->integer('pontos_atleta_b');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['luta_id', 'juiz_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placares');
        Schema::dropIfExists('luta_juizes');
        Schema::dropIfExists('lutas');
        Schema::dropIfExists('eventos');
    }
};
