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
    Schema::table('empresas', function (Blueprint $table) {
        // Añadimos campos de texto útiles
        $table->text('descripcion')->nullable()->after('nombre');
        $table->string('web')->nullable()->after('localidad');
        $table->string('telefono_contacto')->nullable()->after('web');
        
    });
}

public function down(): void
{
    Schema::table('empresas', function (Blueprint $table) {
        $table->dropColumn(['descripcion', 'web', 'telefono_contacto']);
    });
}
};
