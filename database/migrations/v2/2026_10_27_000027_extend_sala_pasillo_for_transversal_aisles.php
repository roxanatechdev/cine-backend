<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sala_pasillo', function (Blueprint $table) {
            // Los registros existentes representan pasillos entre asientos de una misma fila.
            $table->string('orientacion', 20)->default('LONGITUDINAL');
            // Solo se utiliza en pasillos entre dos filas consecutivas.
            $table->string('fila_siguiente', 5)->nullable();
        });

        Schema::table('sala_pasillo', function (Blueprint $table) {
            // Los pasillos transversales no se ubican después de un número de asiento.
            $table->unsignedSmallInteger('despues_de_numero')->nullable()->change();
            $table->unique(
                ['id_sala', 'orientacion', 'fila', 'fila_siguiente'],
                'uq_sala_pasillo_transversal'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sala_pasillo', function (Blueprint $table) {
            $table->dropUnique('uq_sala_pasillo_transversal');
        });

        Schema::table('sala_pasillo', function (Blueprint $table) {
            $table->unsignedSmallInteger('despues_de_numero')->nullable(false)->change();
            $table->dropColumn(['orientacion', 'fila_siguiente']);
        });
    }
};
