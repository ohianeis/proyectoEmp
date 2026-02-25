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
            $table->boolean('visibleAlumno')->default(false);
            $table->boolean('visibleEmpresa')->default(false);
            $table->boolean('soloAdmin')->default(false);
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
