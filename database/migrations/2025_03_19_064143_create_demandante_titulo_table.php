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
        Schema::create('demandante_titulo', function (Blueprint $table) {
            $table->id();
            $table->string('centro');
            $table->year('año');
            $table->boolean('cursando');
            $table->unsignedBigInteger('titulo_id');
            $table->unsignedBigInteger('demandante_id');

            //foraneas
            $table->foreign('titulo_id')->references('id')->on('titulos')->onDelete('cascade');
            $table->foreign('demandante_id')->references('id')->on('demandantes')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandante_titulo');
    }
};
