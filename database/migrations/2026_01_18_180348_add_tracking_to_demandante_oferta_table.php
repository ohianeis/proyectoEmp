<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandante_oferta', function (Blueprint $table) {
            // 1. Añadimos si la empresa ha visto al candidato
            $table->boolean('revisado')->default(false)->after('demandante_id');
            
            // 2. Añadir la relación al nuevo estado del candidato (Inscrito, Entrevista...)
            // Uso default(1) porque el Seeder creó 'Inscrito' con ID 1
            $table->unsignedBigInteger('estado_candidato_id')->default(1)->after('revisado');
            $table->foreign('estado_candidato_id')->references('id')->on('estado_candidatos');
            
            // 3. Opcional: Un campo de notas por si la empresa quiere anotar algo tras la entrevista
            $table->text('notas_reclutador')->nullable()->after('estado_candidato_id');
        });
    }

    public function down(): void
    {
        Schema::table('demandante_oferta', function (Blueprint $table) {
            // Eliminar clave foránea y luego las columnas
            $table->dropForeign(['estado_candidato_id']);
            $table->dropColumn(['revisado', 'estado_candidato_id', 'notas_reclutador']);
        });
    }
};