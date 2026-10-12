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
        Schema::create('producto', function (Blueprint $table) {
            $table->increments('id_producto');

            $table->unsignedInteger('id_categoria')->nullable();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio', 10, 2);
            $table->string('imagen', 255)->nullable();

            $table->string('estado', 20)->default('ACTIVO');

            $table->index('id_categoria', 'idx_producto_categoria');
            $table->index('estado', 'idx_producto_estado');

            $table->foreign('id_categoria')
                ->references('id_categoria')
                ->on('categoria_producto')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
        ALTER TABLE producto
        ADD CONSTRAINT chk_producto_precio
        CHECK (precio >= 0)
    ");

        DB::statement("
        ALTER TABLE producto
        ADD CONSTRAINT chk_producto_estado
        CHECK (estado IN ('ACTIVO', 'INACTIVO', 'AGOTADO'))
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
