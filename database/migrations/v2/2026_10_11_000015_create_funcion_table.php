<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcion', function (Blueprint $table) {
            $table->increments('id_funcion');
            $table->unsignedInteger('id_pelicula');
            $table->unsignedInteger('id_sala');

            $table->dateTime('inicio')
                ->comment('Fecha y hora de inicio de la proyeccion');

            $table->enum('formato', ['2D', '3D'])->default('2D');

            $table->decimal('precio_base', 10, 2);

            $table->enum('estado', [
                'PROGRAMADA',
                'EN_CURSO',
                'FINALIZADA',
                'CANCELADA',
            ])->default('PROGRAMADA');

            $table->timestamp('creado_en')->useCurrent();

            $table->index(
                ['id_sala', 'inicio', 'estado'],
                'ix_funcion_sala_inicio_estado'
            );

            $table->index(
                ['id_pelicula', 'inicio'],
                'ix_funcion_pelicula_inicio'
            );

            $table->foreign(
                'id_pelicula',
                'fk_funcion_pelicula'
            )->references('id_pelicula')
                ->on('pelicula')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'id_sala',
                'fk_funcion_sala'
            )->references('id_sala')
                ->on('sala')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE funcion
            ADD CONSTRAINT chk_funcion_precio
            CHECK (precio_base >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('funcion');
    }
};
