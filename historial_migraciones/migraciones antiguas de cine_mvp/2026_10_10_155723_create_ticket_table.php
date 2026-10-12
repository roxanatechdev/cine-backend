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
        Schema::create('ticket', function (Blueprint $table) {
            $table->increments('id_ticket');

            $table->unsignedInteger('id_compra');

            $table->string('codigo_validacion', 100);

            $table->dateTime('fecha_emision')
                ->useCurrent();

            $table->string('estado', 20)
                ->default('EMITIDO');

            $table->unique('id_compra', 'uq_ticket_compra');

            $table->unique('codigo_validacion', 'uq_ticket_codigo');

            $table->foreign('id_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
        ALTER TABLE ticket
        ADD CONSTRAINT chk_ticket_estado
        CHECK (
            estado IN ('EMITIDO', 'UTILIZADO', 'ANULADO')
        )
    ");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
