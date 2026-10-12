<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->unsignedInteger('id_comprador');

            $table->dateTime('creada_en')->useCurrent();
            $table->dateTime('vence_reserva')->nullable();

            $table->char('moneda', 3)->default('BOB');
            $table->decimal('total', 12, 2)->default(0.00);

            $table->enum('estado', [
                'INICIADA',
                'PENDIENTE_PAGO',
                'CONFIRMADA',
                'CANCELADA',
                'EXPIRADA',
            ])->default('INICIADA');

            $table->dateTime('cancelada_en')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();

            $table->index(
                ['id_comprador', 'creada_en'],
                'ix_compra_comprador_fecha'
            );

            $table->index(
                ['estado', 'vence_reserva'],
                'ix_compra_estado_vence'
            );

            $table->foreign('id_comprador', 'fk_compra_comprador')
                ->references('id_comprador')
                ->on('comprador')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE compra
            ADD CONSTRAINT chk_compra_total
            CHECK (total >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('compra');
    }
};
