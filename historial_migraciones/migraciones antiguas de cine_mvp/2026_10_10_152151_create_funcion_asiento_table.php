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
        Schema::create('funcion_asiento', function (Blueprint $table) {
            $table->increments('id_funcion_asiento');

            $table->unsignedInteger('id_funcion');
            $table->unsignedInteger('id_asiento');

            $table->string('estado', 20)->default('DISPONIBLE');
            $table->dateTime('bloqueado_hasta')->nullable();

            $table->unique(
                ['id_funcion', 'id_asiento'],
                'uq_funcion_asiento'
            );

            $table->index(['id_funcion', 'estado'], 'idx_fa_estado');

            $table->foreign('id_funcion')
                ->references('id_funcion')
                ->on('funcion')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_asiento')
                ->references('id_asiento')
                ->on('asiento')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
        ALTER TABLE funcion_asiento
        ADD CONSTRAINT chk_funcion_asiento_estado
        CHECK (
            estado IN ('DISPONIBLE', 'BLOQUEADO', 'VENDIDO')
        )
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcion_asiento');
    }
};
