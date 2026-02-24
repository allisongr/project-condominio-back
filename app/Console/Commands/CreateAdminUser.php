<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Persona;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
        {--email= : Email del administrador}
        {--password= : Contraseña del administrador}
        {--nombre= : Nombre del administrador}
        {--apellido= : Apellido del administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un usuario administrador con email verificado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('   CREAR USUARIO ADMINISTRADOR');
        $this->info('===========================================');
        $this->newLine();

        // Obtener datos del administrador
        $email = $this->option('email') ?? $this->ask('Email del administrador');
        
        // Verificar si el email ya existe
        if (Usuario::where('email', $email)->exists()) {
            $this->error("❌ El email '$email' ya está registrado.");
            return Command::FAILURE;
        }

        $password = $this->option('password') ?? $this->secret('Contraseña (mínimo 6 caracteres)');
        
        if (strlen($password) < 6) {
            $this->error('❌ La contraseña debe tener al menos 6 caracteres.');
            return Command::FAILURE;
        }

        $nombre = $this->option('nombre') ?? $this->ask('Nombre');
        $apellido = $this->option('apellido') ?? $this->ask('Apellido Paterno');
        $apellidoM = $this->ask('Apellido Materno (opcional)', '');
        $celular = $this->ask('Teléfono (opcional)', '');

        $this->newLine();
        $this->info('Creando administrador...');

        try {
            // Crear persona
            $persona = Persona::create([
                'nombre' => $nombre,
                'apellido_p' => $apellido,
                'apellido_m' => $apellidoM ?: null,
                'celular' => $celular ?: null,
                'activo' => true,
            ]);

            // Crear usuario administrador con email verificado
            $usuario = Usuario::create([
                'id_persona' => $persona->id,
                'email' => $email,
                'pass' => bcrypt($password),
                'admin' => true,
                'email_verified_at' => now(), // Email verificado automáticamente
            ]);

            $this->newLine();
            $this->info('===========================================');
            $this->info('✅ ¡Administrador creado exitosamente!');
            $this->info('===========================================');
            $this->newLine();
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $usuario->id],
                    ['Nombre', $persona->nombre . ' ' . $persona->apellido_p],
                    ['Email', $usuario->email],
                    ['Rol', 'Administrador'],
                    ['Email Verificado', '✓ Sí'],
                    ['Activo', '✓ Sí'],
                ]
            );
            $this->newLine();
            $this->info('Ahora puedes iniciar sesión con estas credenciales.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error al crear el administrador: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
