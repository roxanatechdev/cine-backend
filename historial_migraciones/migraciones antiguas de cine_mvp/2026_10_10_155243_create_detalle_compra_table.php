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
        Schema::create('detalle_compra', function (Blueprint $table) {
            $table->increments('id_detalle');

            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_producto');

            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);

            $table->foreign('id_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_producto')
                ->references('id_producto')
                ->on('producto')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('id_compra', 'idx_detalle_compra');
            $table->index('id_producto', 'idx_detalle_producto');
        });

        DB::statement("
        ALTER TABLE detalle_compra
        ADD CONSTRAINT chk_detalle_cantidad
        CHECK (cantidad > 0)
    ");

        DB::statement("
        ALTER TABLE detalle_compra
        ADD CONSTRAINT chk_detalle_precio
        CHECK (precio_unitario >= 0)
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_compra');
    }
};
