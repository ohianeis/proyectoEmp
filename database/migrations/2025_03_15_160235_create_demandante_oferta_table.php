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
        Schema::create('demandante_oferta', function (Blueprint $table) {
            $table->id();
            $table->string('fecha',45);
            $table->unsignedBigInteger('proceso_id');
            $table->foreign('proceso_id')->references('id')->on('procesos');
            $table->unsignedBigInteger('demandante_id');
            $table->foreign('demandante_id')->references('id')->on('demandantes')->onDelete('cascade');
            $table->unsignedBigInteger('oferta_id');
            $table->foreign('oferta_id')->references('id')->on('ofertas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandante_oferta');
    }
};
