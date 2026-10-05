<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ranking materializado dos palpites (geral, por evento e por organização),
 * para não recalcular a cada acesso.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('escopo', 20);
            // Id do evento ou da organização, conforme o escopo; nulo no ranking geral. Sem FK por apontar para tabelas diferentes.
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('pontos', 10, 2)->default(0);
            $table->integer('palpites_perfeitos')->default(0);
            $table->integer('vencedores_corretos')->default(0);
            $table->integer('posicao')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['escopo', 'referencia_id', 'posicao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
