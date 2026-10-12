<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reembolso', function (Blueprint $table) {
            $table->increments('id_reembolso');
            $table->unsignedInteger('id_pago');

            $table->decimal('importe', 12, 2);
            $table->string('motivo', 255)->nullable();

            $table->enum('estado', [
                'SOLICITADO',
                'PROCESANDO',
                'COMPLETADO',
                'RECHAZADO',
            ])->default('SOLICITADO');

            $table->dateTime('solicitado_en')->useCurrent();
            $table->dateTime('completado_en')->nullable();

            $table->string('referencia_externa', 150)->nullable();

            $table->index(
                ['id_pago', 'estado'],
                'ix_reembolso_pago_estado'
            );

            $table->unique(
                'referencia_externa',
                'uq_reembolso_referencia_externa'
            );

            $table->foreign('id_pago', 'fk_reembolso_pago')
                ->references('id_pago')
                ->on('pago')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE reembolso
            ADD CONSTRAINT chk_reembolso_importe
            CHECK (importe > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('reembolso');
    }
};
