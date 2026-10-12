<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiento', function (Blueprint $table) {
            $table->increments('id_asiento');
            $table->unsignedInteger('id_sala');
            $table->string('fila', 5);
            $table->unsignedSmallInteger('numero');

            $table->enum('tipo', [
                'ESTANDAR',
                'PREFERENCIAL',
                'ACCESIBLE',
            ])->default('ESTANDAR');

            $table->tinyInteger('activo')->default(1);

            $table->unique(
                ['id_sala', 'fila', 'numero'],
                'uq_asiento_sala_fila_numero'
            );

            $table->index(
                ['id_sala', 'activo'],
                'ix_asiento_sala_activo'
            );

            $table->foreign('id_sala', 'fk_asiento_sala')
                ->references('id_sala')
                ->on('sala')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE asiento
            ADD CONSTRAINT chk_asiento_numero
            CHECK (numero > 0)
        ");

        DB::statement("
            ALTER TABLE asiento
            ADD CONSTRAINT chk_asiento_activo
            CHECK (activo IN (0, 1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento');
    }
};
