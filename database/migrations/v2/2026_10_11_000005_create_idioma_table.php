<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idioma', function (Blueprint $table) {
            $table->string('codigo', 15)->primary();
            $table->string('nombre', 100)->unique('uq_idioma_nombre');
            $table->tinyInteger('activo')->default(1);

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE idioma
            ADD CONSTRAINT chk_idioma_activo
            CHECK (activo IN (0, 1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('idioma');
    }
};
