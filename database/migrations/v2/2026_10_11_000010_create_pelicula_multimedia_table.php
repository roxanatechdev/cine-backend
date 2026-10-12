<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelicula_multimedia', function (Blueprint $table) {
            $table->bigIncrements('id_multimedia');
            $table->unsignedInteger('id_pelicula');

            $table->enum('tipo', [
                'POSTER',
                'TRAILER',
                'IMAGEN',
                'VIDEO',
            ]);

            $table->string('url', 500);
            $table->string('texto_alternativo', 255)->nullable();

            $table->tinyInteger('es_principal')->default(0);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->tinyInteger('activo')->default(1);

            $table->timestamp('creado_en')->useCurrent();

            $table->index(
                ['id_pelicula', 'tipo', 'activo', 'orden'],
                'ix_multimedia_pelicula_tipo'
            );

            $table->index(
                ['id_pelicula', 'tipo', 'es_principal', 'activo'],
                'ix_multimedia_principal'
            );

            $table->foreign(
                'id_pelicula',
                'fk_multimedia_pelicula'
            )->references('id_pelicula')
                ->on('pelicula')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });

        DB::statement("
            ALTER TABLE pelicula_multimedia
            ADD CONSTRAINT chk_multimedia_principal
            CHECK (es_principal IN (0, 1))
        ");

        DB::statement("
            ALTER TABLE pelicula_multimedia
            ADD CONSTRAINT chk_multimedia_activo
            CHECK (activo IN (0, 1))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula_multimedia');
    }
};
