<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patrocinadores, banners e postagens do blog (patrocinadas ou não).
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

        Schema::create('postagens', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('titulo', 200);
            $table->string('slug', 220);
            $table->text('conteudo');
            $table->string('meta_description', 160)->nullable();
            // Caminho do arquivo no disco de mídia.
            $table->string('imagem_capa', 255)->nullable();
            // Autor/publicador: deve possuir perfil_administrador (validado na aplicação).
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('patrocinador_id')->nullable()->constrained('patrocinadores');
            // Calculado pelo model a partir de patrocinador_id.
            $table->boolean('patrocinado')->default(false);
            $table->string('fonte_original_url', 255)->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias');
            $table->string('status', 20)->default('rascunho');
            $table->date('data_publicacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index('slug');
            $table->index(['status', 'data_publicacao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postagens');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('patrocinadores');
    }
};
