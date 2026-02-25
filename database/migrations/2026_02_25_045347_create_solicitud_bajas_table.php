<?php

use App\Enums\ResultadoBaja;
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
        Schema::create('solicitud_bajas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('motivo_baja_id');
            $table->foreign('motivo_baja_id')->references('id')->on('motivo_bajas');
            $table->text('comentario')->nullable();
            $table->boolean('tramitada')->default(false);
            $table->enum('resultado', [
                ResultadoBaja::PENDIENTE->value,
                ResultadoBaja::ACEPTADA->value,
                ResultadoBaja::RECHAZADA->value
            ])->default(ResultadoBaja::PENDIENTE->value);
            $table->text('notaAdmin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_bajas');
    }
};
