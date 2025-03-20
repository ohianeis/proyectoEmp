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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->string('linea1');
            $table->string('linea2')->nullable();
            $table->string('ciudad',100);
            $table->string('provincia',100);
            $table->integer('codigoPostal');
            $table->boolean('visible');
            $table->unsignedBigInteger('direccioneable_id');
            $table->string('direccioneable_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
