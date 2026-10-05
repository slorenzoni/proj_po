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
        Schema::create('organizacoes', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->string('nome', 150);
            // Caminho do arquivo no disco de mídia (config filesystems.media_disk), não a URL completa.
            $table->string('logo_url', 255)->nullable();
            $table->string('pais_origem', 60)->nullable();
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
        Schema::dropIfExists('organizacoes');
    }
};
