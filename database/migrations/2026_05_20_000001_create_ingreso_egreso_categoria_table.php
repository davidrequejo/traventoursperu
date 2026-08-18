<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ingreso_egreso_categoria')) {
            return;
        }

        Schema::create('ingreso_egreso_categoria', function (Blueprint $table) {
            $table->id('idingreso_egreso_categoria');
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 250)->nullable();
            $table->char('estado_trash', 1)->default('1');
            $table->timestamps();
            $table->unsignedBigInteger('user_trash')->nullable();
            $table->unsignedBigInteger('user_created')->nullable();
            $table->unsignedBigInteger('user_updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingreso_egreso_categoria');
    }
};
