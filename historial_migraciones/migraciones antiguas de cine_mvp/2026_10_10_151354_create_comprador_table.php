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
        Schema::create('comprador', function (Blueprint $table) {
            $table->increments('id_comprador');

            $table->string('nombre', 100);
            $table->string('correo', 150);
            $table->string('tipo_comprador', 20)
                ->default('INVITADO');

            $table->index('correo', 'idx_comprador_correo');
        });

        DB::statement("
        ALTER TABLE comprador
        ADD CONSTRAINT chk_comprador_tipo
        CHECK (
            tipo_comprador IN ('INVITADO', 'REGISTRADO')
        )
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprador');
    }
};
