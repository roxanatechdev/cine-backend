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
        Schema::create('sala', function (Blueprint $table) {
            $table->increments('id_sala');

            $table->string('nombre', 50)->unique();
            $table->string('estado', 20)->default('ACTIVA');
        });

        DB::statement("
        ALTER TABLE sala
        ADD CONSTRAINT chk_sala_estado
        CHECK (estado IN ('ACTIVA', 'INACTIVA'))
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sala');
    }
};
