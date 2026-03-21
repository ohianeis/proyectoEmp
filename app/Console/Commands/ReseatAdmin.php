<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**Sirve por si admin se olvida de contraseña , el comando en consola con artisan lo restaura */
class ReseatAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
// Cambia 'reseat' por 'reset' (o el nombre que prefieras)
protected $signature = 'admin:reset';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       // Buscamor al ID 1 (Admin Maestro)
    $admin = \App\Models\User::find(1);

    if ($admin) {
        $nuevaPass = env('ADMIN_PASSWORD', 'administrador');
        $admin->password = \Illuminate\Support\Facades\Hash::make($nuevaPass);
        $admin->save();

        $this->info("✅ ÉXITO: La contraseña del Admin (ID 1) ha sido restaurada.");
        $this->line("Nueva contraseña: " . $nuevaPass);
    } else {
        $this->error("❌ ERROR: No se encontró el usuario con ID 1.");
    }
    }
}
