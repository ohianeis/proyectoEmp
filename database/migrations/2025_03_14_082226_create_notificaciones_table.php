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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
           // $table->boolean('visto')->nullable(); lo voy aponer en la tabla pivto de notifiaciones demandantes para ofertas adjudicadas
           
            $table->unsignedBigInteger('accione_id');
            $table->unsignedBigInteger('notificacioneable_id');//pongo id centro o empresa
            $table->string('notificacioneable_type');//modelo centro o empresa
            $table->unsignedBigInteger('relacioneable_id');//id de titulo u oferta que recibe la accion
            $table->string('relacioneable_type');//modelo de titulo u oferta que recibe la accion
            $table->foreign('accione_id')->references('id')->on('acciones');

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
