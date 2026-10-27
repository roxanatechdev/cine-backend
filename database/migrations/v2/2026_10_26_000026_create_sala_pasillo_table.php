<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sala_pasillo', function (Blueprint $table) {
            $table->increments('id_pasillo');
            $table->unsignedInteger('id_sala');
            $table->string('fila', 5);
            $table->unsignedSmallInteger('despues_de_numero');
            $table->timestamps();

            $table->foreign('id_sala', 'fk_sala_pasillo_sala')
                ->references('id_sala')->on('sala')->cascadeOnDelete();
            $table->unique(
                ['id_sala', 'fila', 'despues_de_numero'],
                'uq_sala_pasillo_ubicacion'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sala_pasillo');
    }
};
