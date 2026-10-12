<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_producto', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id_categoria_producto');
            $table->string('nombre', 80);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('activo')->default(1);

            $table->unique(
                'nombre',
                'uq_categoria_producto_nombre'
            );
        });

        DB::statement("
            ALTER TABLE categoria_producto
            ADD CONSTRAINT chk_categoria_producto_activo
            CHECK (activo IN (0, 1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_producto');
    }
};
