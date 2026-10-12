<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('funcion', function (Blueprint $table) {
            $table->increments('id_funcion');

            $table->unsignedInteger('id_pelicula');
            $table->unsignedInteger('id_sala');

            $table->date('fecha');
            $table->time('hora');

            $table->string('formato', 20);
            $table->string('estado', 20)->default('PROGRAMADA');

            $table->index('fecha', 'idx_funcion_fecha');
            $table->index(['id_pelicula', 'fecha'], 'idx_funcion_pelicula_fecha');
            $table->index(['id_sala', 'fecha'], 'idx_funcion_sala_fecha');

            $table->foreign('id_pelicula')
                ->references('id_pelicula')
                ->on('pelicula')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_sala')
                ->references('id_sala')
                ->on('sala')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
        ALTER TABLE funcion
        ADD CONSTRAINT chk_funcion_formato
        CHECK (formato IN ('2D', '3D', 'XD', 'PREMIER'))
    ");

        DB::statement("
        ALTER TABLE funcion
        ADD CONSTRAINT chk_funcion_estado
        CHECK (
            estado IN ('PROGRAMADA', 'CANCELADA', 'FINALIZADA')
        )
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcion');
    }
};
