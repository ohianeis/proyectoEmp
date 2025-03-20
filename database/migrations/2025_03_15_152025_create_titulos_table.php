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
        Schema::create('titulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',100)->unique();
            $table->boolean('activado');
            $table->unsignedBigInteger('nivele_id')->nullable();
            $table->foreign('nivele_id')->references('id')->on('niveles')->onDelete('set null');
            $table->unsignedBigInteger('centro_id')->nullable();
            $table->foreign('centro_id')->references('id')->on('centros')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titulos');
    }
};
