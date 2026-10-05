<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perfis_cliente', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            $table->string('foto_perfil_url', 255)->nullable();
            $table->string('email_secundario', 150)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->boolean('maior_de_18')->default(false);
            $table->string('tipo', 30)->nullable();
            $table->date('data_cadastro');
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
        Schema::dropIfExists('perfis_cliente');
    }
};
