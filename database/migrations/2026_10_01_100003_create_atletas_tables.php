<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atleta + fotos (máx. 3) + estilos de luta com treinador por estilo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('atletas', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 150);
            $table->string('apelido', 100)->nullable();
            $table->string('tipo', 30)->default('lutador');
            $table->string('equipe', 150)->nullable();
            $table->string('pais', 60)->nullable();
            $table->string('cidade_natal', 100)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->integer('altura_cm')->nullable();
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->integer('alcance_cm')->nullable();
            $table->string('stance', 20)->nullable();
            $table->text('biografia')->nullable();
            // Totais e "invicto" são calculados pelo model a partir do detalhamento.
            $table->integer('vitorias')->default(0);
            $table->integer('vitorias_ko')->default(0);
            $table->integer('vitorias_submissao')->default(0);
            $table->integer('vitorias_decisao')->default(0);
            $table->integer('empates')->default(0);
            $table->integer('derrotas')->default(0);
            $table->integer('derrotas_ko')->default(0);
            $table->integer('derrotas_submissao')->default(0);
            $table->integer('derrotas_decisao')->default(0);
            $table->boolean('invicto')->default(true);
            $table->integer('ranking')->nullable();
            // Preenchido só se o atleta (ex.: ex-lutador) tiver conta para logar.
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index('nome');
        });

        Schema::create('atleta_fotos', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('atleta_id')->constrained('atletas');
            // Caminho do arquivo no disco de mídia.
            $table->string('foto_url', 255);
            $table->integer('ordem');
            $table->boolean('principal')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('atleta_estilos', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('atleta_id')->constrained('atletas');
            $table->foreignId('estilo_id')->constrained('estilos_luta');
            $table->foreignId('treinador_id')->nullable()->constrained('treinadores');
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atleta_estilos');
        Schema::dropIfExists('atleta_fotos');
        Schema::dropIfExists('atletas');
    }
};
