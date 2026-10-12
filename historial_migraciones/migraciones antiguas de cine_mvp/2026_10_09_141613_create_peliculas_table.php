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
        Schema::create('pelicula', function (Blueprint $table) {
            $table->increments('id_pelicula');

            $table->string('titulo', 150);
            $table->text('sinopsis');
            $table->string('imagen', 255)->nullable();
            $table->string('genero', 80)->nullable();
            $table->string('clasificacion', 20)->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->nullable();
            $table->date('fecha_estreno')->nullable();
            $table->string('trailer', 255)->nullable();
            $table->string('estado', 20)->default('ACTIVA');
        });

        DB::statement("
        ALTER TABLE pelicula
        ADD CONSTRAINT chk_pelicula_duracion
        CHECK (
            duracion_minutos IS NULL
            OR duracion_minutos > 0
        )
    ");

        DB::statement("
        ALTER TABLE pelicula
        ADD CONSTRAINT chk_pelicula_estado
        CHECK (estado IN ('ACTIVA', 'INACTIVA'))
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelicula');
    }
};
