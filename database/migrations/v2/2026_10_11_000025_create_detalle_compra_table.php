
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_compra', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id_detalle_compra');
            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_producto');
            $table->unsignedSmallInteger('cantidad');

            $table->decimal('precio_unitario', 10, 2)
                ->comment('Precio congelado al momento de comprar');

            $table->unique(
                ['id_compra', 'id_producto'],
                'uq_detalle_compra_producto'
            );

            $table->index(
                'id_producto',
                'ix_detalle_compra_producto'
            );

            $table->foreign('id_compra', 'fk_detalle_compra_compra')
                ->references('id_compra')
                ->on('compra')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_producto', 'fk_detalle_compra_producto')
                ->references('id_producto')
                ->on('producto')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("
            ALTER TABLE detalle_compra
            ADD CONSTRAINT chk_detalle_cantidad
            CHECK (cantidad > 0)
        ");

        DB::statement("
            ALTER TABLE detalle_compra
            ADD CONSTRAINT chk_detalle_precio
            CHECK (precio_unitario >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compra');
    }
};
