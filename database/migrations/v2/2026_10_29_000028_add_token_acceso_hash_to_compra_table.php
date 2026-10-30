<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('compra', 'token_acceso_hash')) {
            Schema::table('compra', function (Blueprint $table) {
                $table->char('token_acceso_hash', 64)->nullable()->unique('uq_compra_token_acceso_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('compra', 'token_acceso_hash')) {
            Schema::table('compra', function (Blueprint $table) {
                $table->dropUnique('uq_compra_token_acceso_hash');
                $table->dropColumn('token_acceso_hash');
            });
        }
    }
};
