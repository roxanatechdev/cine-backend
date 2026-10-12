<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */


    public function up(): void
    {
        Schema::create('entrada', function (Blueprint $table) {
            $table->increments('id_entrada');

            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_funcion_asiento');

            $table->string('tipo_entrada', 30)->nullable();

            $table->decimal('precio', 10, 2);

            $table->foreign('id_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_funcion_asiento')
                ->references('id_funcion_asiento')
                ->on('funcion_asiento')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('id_compra', 'idx_entrada_compra');
            $table->index('id_funcion_asiento', 'idx_entrada_fa');
        });

        DB::statement("
        ALTER TABLE entrada
        ADD CONSTRAINT chk_entrada_precio
        CHECK (precio >= 0)
    ");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada');
    }
};
