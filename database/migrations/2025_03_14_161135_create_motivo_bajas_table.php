<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('motivo_bajas', function (Blueprint $table) {
            $table->id();
            $table->string('motivo');
            $table->boolean('visible_alumno')->default(false);
            $table->boolean('visible_empresa')->default(false);
            $table->boolean('solo_admin')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motivo_bajas');
    }
};
