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
        Schema::create('papel_user', function (Blueprint $table) {
            $table->id();
            $table->publicUuid();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('papel_id')->constrained('papeis');
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
        Schema::dropIfExists('papel_user');
    }
};
