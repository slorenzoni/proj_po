<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assinatura do plano (Free/Membro) e selo de verificado (solicitação + cobrança recorrente).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            $table->string('plano', 20);
            // Decisão 01/10/2026: o plano Free também gera registro (valor 0, sem gateway e sem cobrança).
            $table->string('periodicidade', 20)->nullable();
            $table->string('gateway', 30)->nullable();
            $table->string('status', 20)->default('ativa');
            $table->decimal('valor', 10, 2)->default(0);
            $table->date('data_inicio');
            $table->date('proxima_cobranca')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['user_id', 'status']);
        });

        Schema::create('solicitacoes_verificacao', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            // Caminho do arquivo no disco de mídia.
            $table->string('documento_url', 255);
            $table->text('descricao')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->text('motivo_rejeicao')->nullable();
            $table->foreignId('analisado_por_user_id')->nullable()->constrained('users');
            $table->dateTime('analisado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index('status');
        });

        Schema::create('assinaturas_verificacao', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('solicitacao_verificacao_id')->constrained('solicitacoes_verificacao');
            $table->string('periodicidade', 20);
            $table->string('gateway', 30);
            $table->decimal('valor', 10, 2);
            $table->string('status', 20)->default('ativa');
            $table->date('data_inicio');
            $table->date('proxima_cobranca')->nullable();
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
        Schema::dropIfExists('assinaturas_verificacao');
        Schema::dropIfExists('solicitacoes_verificacao');
        Schema::dropIfExists('assinaturas');
    }
};
