<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rdocumento_cuota')) {
            return;
        }

        Schema::table('rdocumento_cuota', function (Blueprint $table) {
            if (! Schema::hasColumn('rdocumento_cuota', 'idreserva')) {
                $table->integer('idreserva')->default(0)->after('idrdocumento');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE rdocumento_cuota MODIFY idreserva INT NOT NULL');
            DB::statement("ALTER TABLE rdocumento_cuota MODIFY tipo ENUM('pago','asociacion') NOT NULL");
            DB::statement("ALTER TABLE rdocumento_cuota MODIFY estado_cuota ENUM('pagado') NOT NULL DEFAULT 'pagado'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rdocumento_cuota')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rdocumento_cuota MODIFY tipo ENUM('amortizacion','comprobante_asociado') NOT NULL");
        }

        Schema::table('rdocumento_cuota', function (Blueprint $table) {
            if (Schema::hasColumn('rdocumento_cuota', 'idreserva')) {
                $table->dropColumn('idreserva');
            }
        });
    }
};
