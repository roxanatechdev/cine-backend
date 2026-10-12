<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('asiento', function (Blueprint $table) {
            $table->increments('id_asiento');

            $table->unsignedInteger('id_sala');
            $table->string('fila', 5);
            $table->unsignedSmallInteger('numero');

            $table->string('estado', 20)->default('ACTIVO');

            $table->unique(
                ['id_sala', 'fila', 'numero'],
                'uq_asiento_sala_ubicacion'
            );

            $table->foreign('id_sala')
                ->references('id_sala')
                ->on('sala')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
        ALTER TABLE asiento
        ADD CONSTRAINT chk_asiento_estado
        CHECK (estado IN ('ACTIVO', 'INACTIVO'))
    ");
        DB::statement("
        ALTER TABLE asiento
        ADD CONSTRAINT chk_asiento_numero
        CHECK (
            numero > 0
        )
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asiento');
    }
};
