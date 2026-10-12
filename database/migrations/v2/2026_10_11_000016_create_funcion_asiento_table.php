<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcion_asiento', function (Blueprint $table) {
            $table->increments('id_funcion_asiento');
            $table->unsignedInteger('id_funcion');
            $table->unsignedInteger('id_asiento');

            $table->decimal('precio', 10, 2)
                ->comment('Precio aplicado a este asiento en esta funcion');

            $table->unique(
                ['id_funcion', 'id_asiento'],
                'uq_funcion_asiento'
            );

            $table->index(
                'id_asiento',
                'ix_funcion_asiento_asiento'
            );

            $table->foreign(
                'id_funcion',
                'fk_funcion_asiento_funcion'
            )->references('id_funcion')
                ->on('funcion')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign(
                'id_asiento',
                'fk_funcion_asiento_asiento'
            )->references('id_asiento')
                ->on('asiento')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE funcion_asiento
            ADD CONSTRAINT chk_funcion_asiento_precio
            CHECK (precio >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('funcion_asiento');
    }
};
