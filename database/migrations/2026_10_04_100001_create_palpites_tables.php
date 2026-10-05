<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participação dos usuários numa luta: palpite (e seu histórico de trocas),
 * placar dos fãs e comentários.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('palpites', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('vencedor_escolhido_id')->constrained('atletas');
            $table->string('metodo_escolhido', 30)->nullable();
            $table->integer('round_escolhido')->nullable();
            // 0 = pré-luta; N = troca feita no intervalo após o round N.
            $table->integer('round_da_troca')->default(0);
            // Percentual aplicado sobre a pontuação base, definido pelo momento da última troca.
            $table->decimal('peso_aplicado', 5, 2)->default(100);
            // Nulo até a luta ser encerrada; já com o peso aplicado.
            $table->decimal('pontos_obtidos', 6, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['luta_id', 'user_id']);
        });

        // Log de cada troca de palpite, para auditoria e contestação. A data da troca é o created_at.
        Schema::create('palpite_historicos', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('palpite_id')->constrained('palpites');
            $table->foreignId('vencedor_escolhido_id')->constrained('atletas');
            $table->string('metodo_escolhido', 30)->nullable();
            $table->integer('round_escolhido')->nullable();
            $table->integer('round_da_troca')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('placar_fans', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('user_id')->constrained('users');
            $table->integer('round');
            // Sistema 10-point must: um lado = 10, o outro entre 7 e 10 (validado na aplicação).
            $table->integer('pontos_atleta_a');
            $table->integer('pontos_atleta_b');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['luta_id', 'user_id', 'round']);
        });

        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('user_id')->constrained('users');
            $table->text('mensagem');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['luta_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensagens');
        Schema::dropIfExists('placar_fans');
        Schema::dropIfExists('palpite_historicos');
        Schema::dropIfExists('palpites');
    }
};
