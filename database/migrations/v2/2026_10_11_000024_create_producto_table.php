<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id_producto');
            $table->unsignedInteger('id_categoria_producto')->nullable();

            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio_actual', 10, 2);
            $table->string('imagen', 255)->nullable();
            $table->unsignedInteger('stock')->nullable()
                ->comment('NULL = inventario no controlado');

            $table->enum('estado', [
                'ACTIVO',
                'INACTIVO',
                'AGOTADO',
            ])->default('ACTIVO');

            $table->index(
                ['id_categoria_producto', 'estado'],
                'ix_producto_categoria_estado'
            );

            $table->foreign('id_categoria_producto', 'fk_producto_categoria')
                ->references('id_categoria_producto')
                ->on('categoria_producto')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
            ALTER TABLE producto
            ADD CONSTRAINT chk_producto_precio
            CHECK (precio_actual >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
