<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('producto', function (Blueprint $table) {
        if (!Schema::hasColumn('producto', 'tamano')) {
            $table->string('tamano', 50)->nullable();
        }

        if (!Schema::hasColumn('producto', 'tipo_variante')) {
            $table->string('tipo_variante', 50)->nullable();
        }

        if (!Schema::hasColumn('producto', 'contenido_ml')) {
            $table->unsignedSmallInteger('contenido_ml')->nullable();
        }

        if (!Schema::hasColumn('producto', 'peso_gramos')) {
            $table->unsignedInteger('peso_gramos')->nullable();
        }

        if (!Schema::hasColumn('producto', 'es_promocion')) {
            $table->boolean('es_promocion')->default(false);
        }

        if (!Schema::hasColumn('producto', 'precio_promocional')) {
            $table->decimal('precio_promocional', 10, 2)->nullable();
        }

        if (!Schema::hasColumn('producto', 'promocion_inicio')) {
            $table->dateTime('promocion_inicio')->nullable();
        }

        if (!Schema::hasColumn('producto', 'promocion_fin')) {
            $table->dateTime('promocion_fin')->nullable();
        }
    });
}
};
