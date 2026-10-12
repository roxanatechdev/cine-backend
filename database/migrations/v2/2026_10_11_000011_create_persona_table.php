<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona', function (Blueprint $table) {
            $table->increments('id_persona');
            $table->string('nombre_completo', 180);
            $table->string('nombre_busqueda', 180)->nullable();
            $table->string('foto', 255)->nullable();

            $table->index(
                'nombre_completo',
                'ix_persona_nombre'
            );

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
