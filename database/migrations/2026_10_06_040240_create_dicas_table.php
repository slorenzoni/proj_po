<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dicas da luta (decisão de 06/10/2026): análises em texto livre, visíveis para todos e
 * escritas só por comentaristas e por Membros com selo de verificado.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dicas', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('luta_id')->constrained('lutas');
            $table->foreignId('user_id')->constrained('users');
            $table->text('texto');
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
        Schema::dropIfExists('dicas');
    }
};
