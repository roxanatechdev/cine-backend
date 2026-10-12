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
        Schema::create('compra', function (Blueprint $table) {
            $table->increments('id_compra');

            $table->unsignedInteger('id_comprador');

            $table->dateTime('fecha_hora')
                ->useCurrent();

            $table->string('estado', 20)
                ->default('INICIADA');

            $table->decimal('total', 10, 2)
                ->default(0.00);

            $table->foreign('id_comprador')
                ->references('id_comprador')
                ->on('comprador')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('id_comprador', 'idx_compra_comprador');
            $table->index('estado', 'idx_compra_estado');
        });

        DB::statement("
        ALTER TABLE compra
        ADD CONSTRAINT chk_compra_estado
        CHECK (
            estado IN (
                'INICIADA',
                'PENDIENTE_PAGO',
                'CONFIRMADA',
                'CANCELADA',
                'EXPIRADA'
            )
        )
    ");

        DB::statement("
        ALTER TABLE compra
        ADD CONSTRAINT chk_compra_total
        CHECK (total >= 0)
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra');
    }
};
