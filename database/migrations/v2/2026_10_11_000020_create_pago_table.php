<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->unsignedInteger('id_compra');

            $table->enum('metodo', [
                'QR',
                'TARJETA',
            ]);

            $table->decimal('importe', 12, 2);

            $table->enum('estado', [
                'PENDIENTE',
                'APROBADO',
                'RECHAZADO',
            ])->default('PENDIENTE');

            $table->string('referencia_externa', 150)->nullable();

            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('aprobado_en')->nullable();

            $table->index(
                ['id_compra', 'estado'],
                'ix_pago_compra_estado'
            );

            $table->unique(
                'referencia_externa',
                'uq_pago_referencia_externa'
            );

            $table->foreign('id_compra', 'fk_pago_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE pago
            ADD CONSTRAINT chk_pago_importe
            CHECK (importe > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
