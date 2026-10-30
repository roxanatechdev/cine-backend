<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('compra', 'stock_apartado')) {
            Schema::table('compra', function (Blueprint $table) {
                $table->boolean('stock_apartado')->default(false)->after('token_acceso_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('compra', 'stock_apartado')) {
            Schema::table('compra', function (Blueprint $table) {
                $table->dropColumn('stock_apartado');
            });
        }
    }
};
