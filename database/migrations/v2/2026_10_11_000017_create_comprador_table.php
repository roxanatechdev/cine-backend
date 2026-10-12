<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprador', function (Blueprint $table) {
            $table->increments('id_comprador');
            $table->string('nombre', 100);
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();

            $table->enum('tipo_comprador', [
                'INVITADO',
                'REGISTRADO',
            ])->default('INVITADO');

            $table->timestamp('creado_en')->useCurrent();

            $table->index('correo', 'ix_comprador_correo');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprador');
    }
};
