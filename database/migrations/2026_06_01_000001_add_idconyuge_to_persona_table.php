<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persona') || Schema::hasColumn('persona', 'idconyuge')) {
            return;
        }

        Schema::table('persona', function (Blueprint $table) {
            $table->unsignedBigInteger('idconyuge')->nullable()->after('estado_civil');
            $table->index('idconyuge');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('persona') || ! Schema::hasColumn('persona', 'idconyuge')) {
            return;
        }

        Schema::table('persona', function (Blueprint $table) {
            $table->dropIndex(['idconyuge']);
            $table->dropColumn('idconyuge');
        });
    }
};
