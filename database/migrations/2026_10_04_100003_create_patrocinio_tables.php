<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patrocinadores e seus banners.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patrocinadores', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 150);
            // Caminho do arquivo no disco de mídia.
            $table->string('logo_url', 255)->nullable();
            $table->string('link_site', 255)->nullable();
            $table->string('email_contato', 150)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->date('data_inicio_contrato')->nullable();
            $table->date('data_fim_contrato')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('patrocinador_id')->constrained('patrocinadores');
            // Caminho do arquivo no disco de mídia.
            $table->string('imagem_url', 255);
            $table->string('link_destino', 255)->nullable();
            $table->string('posicao', 30);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->string('modelo_cobranca', 20);
            $table->decimal('valor_contrato', 10, 2)->nullable();
            $table->integer('impressoes')->default(0);
            $table->integer('cliques')->default(0);
            $table->integer('ordem_exibicao')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['posicao', 'status', 'data_inicio', 'data_fim']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('patrocinadores');
    }
};
