<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genero', function (Blueprint $table) {
            $table->increments('id_genero');
            $table->string('nombre', 80)->unique('uq_genero_nombre');
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('activo')->default(1);

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE genero
            ADD CONSTRAINT chk_genero_activo
            CHECK (activo IN (0, 1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('genero');
    }
};
