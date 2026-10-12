<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket', function (Blueprint $table) {
            $table->increments('id_ticket');
            $table->unsignedInteger('id_compra');

            $table->string('codigo_validacion', 100);

            $table->dateTime('emitido_en')->useCurrent();

            $table->enum('estado', [
                'EMITIDO',
                'UTILIZADO',
                'ANULADO',
            ])->default('EMITIDO');

            $table->dateTime('utilizado_en')->nullable();

            $table->unique('id_compra', 'uq_ticket_compra');

            $table->unique(
                'codigo_validacion',
                'uq_ticket_codigo'
            );

            $table->foreign('id_compra', 'fk_ticket_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
