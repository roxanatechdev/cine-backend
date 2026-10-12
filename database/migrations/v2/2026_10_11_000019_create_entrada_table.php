<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrada', function (Blueprint $table) {
            $table->increments('id_entrada');
            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_funcion_asiento');

            $table->decimal('precio_venta', 10, 2);

            $table->enum('estado', [
                'RESERVADA',
                'CONFIRMADA',
                'UTILIZADA',
                'CANCELADA',
                'EXPIRADA',
            ])->default('RESERVADA');

            $table->dateTime('reservada_en')->useCurrent();
            $table->dateTime('confirmada_en')->nullable();
            $table->dateTime('utilizada_en')->nullable();

            $table->dateTime('cerrada_en')
                ->nullable()
                ->comment('Fecha de cancelacion o expiracion');

            $table->index(
                ['id_compra', 'estado'],
                'ix_entrada_compra_estado'
            );

            $table->index(
                ['id_funcion_asiento', 'estado'],
                'ix_entrada_funcion_asiento_estado'
            );

            $table->foreign('id_compra', 'fk_entrada_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'id_funcion_asiento',
                'fk_entrada_funcion_asiento'
            )->references('id_funcion_asiento')
                ->on('funcion_asiento')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE entrada
            ADD CONSTRAINT chk_entrada_precio
            CHECK (precio_venta >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada');
    }
};
