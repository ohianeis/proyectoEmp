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
    Schema::table('ofertas', function (Blueprint $table) {
        // Añadimos la columna. La ponemos nullable por si acaso tienes ofertas antiguas,
        // y la conectamos con la tabla familias.
        $table->unsignedBigInteger('familia_id')->nullable()->after('empresa_id');
        
        // Creamos la clave foránea
        $table->foreign('familia_id')->references('id')->on('familias')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('ofertas', function (Blueprint $table) {
        $table->dropForeign(['familia_id']);
        $table->dropColumn('familia_id');
    });
}
};
