<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelicula', function (Blueprint $table) {
            $table->increments('id_pelicula');
            $table->string('titulo', 150);
            $table->text('sinopsis');

            $table->unsignedInteger('id_genero_principal')
                ->nullable();

            $table->enum('clasificacion', [
                'G',
                'PG',
                'PG-13',
                'R',
                'NC-17',
            ])->nullable();

            $table->unsignedSmallInteger('duracion_minutos')
                ->nullable();

            $table->date('fecha_estreno')->nullable();

            $table->string('idioma_original_codigo', 15)
                ->nullable();

            $table->enum('estado', [
                'BORRADOR',
                'ACTIVA',
                'INACTIVA',
            ])->default('BORRADOR');

            $table->timestamp('creado_en')->useCurrent();

            $table->timestamp('actualizado_en')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index('titulo', 'ix_pelicula_titulo');
            $table->index(
                ['estado', 'fecha_estreno'],
                'ix_pelicula_estado_estreno'
            );
            $table->index(
                'id_genero_principal',
                'ix_pelicula_genero'
            );
            $table->index(
                'idioma_original_codigo',
                'ix_pelicula_idioma'
            );

            $table->foreign(
                'id_genero_principal',
                'fk_pelicula_genero'
            )->references('id_genero')
                ->on('genero')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'idioma_original_codigo',
                'fk_pelicula_idioma'
            )->references('codigo')
                ->on('idioma')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE pelicula
            ADD CONSTRAINT chk_pelicula_duracion
            CHECK (
                duracion_minutos IS NULL
                OR duracion_minutos > 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula');
    }
};
