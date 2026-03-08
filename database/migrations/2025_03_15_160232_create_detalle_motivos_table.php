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
       Schema::create('detalle_motivos', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        // Relacionamos con tu tabla actual de 'motivos'
        $table->unsignedBigInteger('motivo_id'); 
        $table->foreign('motivo_id')->references('id')->on('motivos')->onDelete('cascade');
        $table->boolean('activo')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_motivos');
    }
};
