<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ingreso_egreso') || Schema::hasColumn('ingreso_egreso', 'tipo_movimiento')) {
            return;
        }

        Schema::table('ingreso_egreso', function (Blueprint $table) {
            $table->enum('tipo_movimiento', ['INGRESO', 'EGRESO'])
                ->default('EGRESO')
                ->after('idotros_gastos_categoria');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ingreso_egreso') || ! Schema::hasColumn('ingreso_egreso', 'tipo_movimiento')) {
            return;
        }

        Schema::table('ingreso_egreso', function (Blueprint $table) {
            $table->dropColumn('tipo_movimiento');
        });
    }
};
