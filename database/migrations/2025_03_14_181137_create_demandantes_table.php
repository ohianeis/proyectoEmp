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
        Schema::create('demandantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',100);
            $table->integer('telefono');
            $table->text('experienciaLaboral')->nullable();
            $table->unsignedBigInteger('situacione_id');
            $table->foreign('situacione_id')->references('id')->on('situaciones');
            $table->unsignedBigInteger('centro_id')->nullable();
            $table->foreign('centro_id')->references('id')->on('centros')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandantes');
    }
};
