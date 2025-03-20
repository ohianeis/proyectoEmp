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
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',45);
            $table->text('observacion');
            $table->string('tipoContrato',45);
            $table->string('horario',45);
            $table->date('fechaCierre')->nullable();
            $table->integer('nPuestos');
           $table->unsignedBigInteger('motivo_id')->nullable();
              $table->foreign('motivo_id')->references('id')->on('motivos');
            $table->unsignedBigInteger('estado_id');//debe ser mismo tipo que id de estados

            $table->foreign('estado_id')->references('id')->on('estados');//pongo a user_id como llave foranea con el id de la tabla estados
            $table->unsignedBigInteger('empresa_id');//debe ser mismo tipo que id de estados

            $table->foreign('empresa_id')->references('id')->on('empresas');//pongo a user_id como llave foranea con el id de la tabla estados
         
         
            $table->timestamps();
          //  $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
