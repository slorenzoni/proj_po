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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 50);
            // Decisão 01/10/2026: Judô não tem rounds (sem round no palpite, palpite ao vivo e placar dos fãs).
            $table->boolean('usa_rounds')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();
        });

        Schema::create('categorias_peso', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->string('nome', 60);
            $table->decimal('peso_minimo_kg', 5, 2)->nullable();
            $table->decimal('peso_maximo_kg', 5, 2)->nullable();
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
        Schema::dropIfExists('categorias_peso');
        Schema::dropIfExists('categorias');
    }
};
