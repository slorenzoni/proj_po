<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estilos de luta, treinadores e juízes (cadastros simples que não dependem de atleta).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estilos_luta', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 60);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('treinadores', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            // Preenchido só se o treinador tiver conta para logar no sistema.
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('nome', 150);
            $table->string('pais', 60)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('juizes', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 150);
            $table->string('pais', 60)->nullable();
            $table->string('certificado_por', 150)->nullable();
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
        Schema::dropIfExists('juizes');
        Schema::dropIfExists('treinadores');
        Schema::dropIfExists('estilos_luta');
    }
};
