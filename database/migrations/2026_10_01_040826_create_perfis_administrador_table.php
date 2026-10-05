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
        Schema::create('perfis_administrador', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            $table->string('nivel_acesso', 30);
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
        Schema::dropIfExists('perfis_administrador');
    }
};
