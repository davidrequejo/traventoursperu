<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ingreso_egreso')) {
            return;
        }

        Schema::create('ingreso_egreso', function (Blueprint $table) {
            $table->id('idingreso_egreso');
            $table->unsignedBigInteger('idproveedor')->nullable();
            $table->unsignedBigInteger('idtrabajador')->nullable();
            $table->unsignedBigInteger('idotros_gastos_categoria')->nullable();
            $table->enum('tipo_movimiento', ['INGRESO', 'EGRESO'])->default('EGRESO');
            $table->string('tipo_comprobante', 30)->nullable();
            $table->string('serie_comprobante', 30)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->string('name_day', 30)->nullable();
            $table->string('name_month', 30)->nullable();
            $table->unsignedSmallInteger('name_year')->nullable();
            $table->decimal('precio_sin_igv', 12, 2)->default(0);
            $table->decimal('precio_igv', 12, 2)->default(0);
            $table->decimal('val_igv', 5, 2)->default(18);
            $table->decimal('precio_con_igv', 12, 2)->default(0);
            $table->text('descripcion_comprobante')->nullable();
            $table->string('comprobante', 255)->nullable();
            $table->char('estado_trash', 1)->default('1');
            $table->timestamps();
            $table->unsignedBigInteger('user_trash')->nullable();
            $table->unsignedBigInteger('user_created')->nullable();
            $table->unsignedBigInteger('user_updated')->nullable();

            $table->index('idproveedor');
            $table->index('idtrabajador');
            $table->index('idotros_gastos_categoria');
            $table->index('tipo_movimiento');
            $table->index('fecha_ingreso');
            $table->index('estado_trash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingreso_egreso');
    }
};
