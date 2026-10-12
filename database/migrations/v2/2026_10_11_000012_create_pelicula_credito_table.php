<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelicula_credito', function (Blueprint $table) {
            $table->unsignedInteger('id_pelicula');
            $table->unsignedInteger('id_persona');

            $table->enum('rol', [
                'DIRECTOR',
                'ACTOR',
                'GUIONISTA',
                'PRODUCTOR',
                'COMPOSITOR',
            ]);

            $table->string('personaje', 150)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);

            $table->primary([
                'id_pelicula',
                'id_persona',
                'rol',
            ]);

            $table->index(
                ['id_persona', 'rol'],
                'ix_credito_persona_rol'
            );

            $table->index(
                ['id_pelicula', 'rol', 'orden'],
                'ix_credito_pelicula_rol_orden'
            );

            $table->foreign(
                'id_pelicula',
                'fk_credito_pelicula'
            )->references('id_pelicula')
                ->on('pelicula')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'id_persona',
                'fk_credito_persona'
            )->references('id_persona')
                ->on('persona')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula_credito');
    }
};
