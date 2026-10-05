<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Momento em que o último round terminou. O placar dos fãs abre quando o round
 * termina e fecha após o prazo configurado — sem esse horário não há como medir o prazo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lutas', function (Blueprint $table) {
            $table->dateTime('round_encerrado_em')->nullable()->after('em_intervalo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lutas', function (Blueprint $table) {
            $table->dropColumn('round_encerrado_em');
        });
    }
};
