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
        Schema::create('pago', function (Blueprint $table) {
            $table->increments('id_pago');

            $table->unsignedInteger('id_compra');

            $table->string('metodo', 30);
            $table->decimal('monto', 10, 2);

            $table->string('estado', 20)
                ->default('PENDIENTE');

            $table->dateTime('fecha_hora')
                ->useCurrent();

            $table->foreign('id_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('id_compra', 'idx_pago_compra');
            $table->index('estado', 'idx_pago_estado');
        });

        DB::statement("
        ALTER TABLE pago
        ADD CONSTRAINT chk_pago_metodo
        CHECK (metodo IN ('QR', 'TARJETA'))
    ");

        DB::statement("
        ALTER TABLE pago
        ADD CONSTRAINT chk_pago_monto
        CHECK (monto >= 0)
    ");

        DB::statement("
        ALTER TABLE pago
        ADD CONSTRAINT chk_pago_estado
        CHECK (
            estado IN (
                'PENDIENTE',
                'APROBADO',
                'RECHAZADO'
            )
        )
    ");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
