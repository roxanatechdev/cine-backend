<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelicula_pais', function (Blueprint $table) {
            $table->unsignedInteger('id_pelicula');
            $table->char('codigo_pais', 2);

            $table->primary([
                'id_pelicula',
                'codigo_pais',
            ]);

            $table->index(
                'codigo_pais',
                'ix_pelicula_pais_pais'
            );

            $table->foreign(
                'id_pelicula',
                'fk_pelicula_pais_pelicula'
            )->references('id_pelicula')
                ->on('pelicula')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'codigo_pais',
                'fk_pelicula_pais_pais'
            )->references('codigo')
                ->on('pais')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula_pais');
    }
};
