<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sala', function (Blueprint $table) {
            $table->increments('id_sala');
            $table->string('nombre', 50);
            $table->unsignedSmallInteger('capacidad')->nullable()
                ->comment('Informativo; la fuente de verdad son los asientos');
            $table->unsignedSmallInteger('margen_limpieza_minutos')
                ->default(30);
            $table->enum('estado', [
                'ACTIVA',
                'INACTIVA',
                'MANTENIMIENTO',
            ])->default('ACTIVA');

            $table->unique('nombre', 'uq_sala_nombre');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE sala
            ADD CONSTRAINT chk_sala_margen
            CHECK (margen_limpieza_minutos >= 0)
        ");

        DB::statement("
            ALTER TABLE sala
            ADD CONSTRAINT chk_sala_capacidad
            CHECK (capacidad IS NULL OR capacidad > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sala');
    }
};
