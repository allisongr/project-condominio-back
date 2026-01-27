<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Usuario;
use App\Models\PerDep;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\Mensaje;
use Illuminate\Database\Seeder;

class ContactosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el rol de Residente (debería ser id=1)
        $rolResidente = Rol::where('rol', 'Residente')->first();
        
        if (!$rolResidente) {
            // Si no existe, crear rol por defecto
            $rolResidente = Rol::create(['rol' => 'Residente']);
        }

        $id_rol = $rolResidente->id;

        // Obtener o crear los primeros 7 departamentos
        $depa_ids = [];
        
        // Si existen departamentos, usarlos; si no, crearlos
        $depas = Departamento::limit(7)->get();
        
        if ($depas->count() < 7) {
            // Crear los que falten
            for ($i = 0; $i < 7; $i++) {
                $depa = Departamento::create([
                    'depa' => '10' . ($i + 1),
                    'moroso' => false,
                    'codigo' => str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
                ]);
                $depa_ids[] = $depa->id;
            }
        } else {
            $depa_ids = $depas->pluck('id')->toArray();
        }

        // Crear personas y usuarios de prueba
        $personas_data = [
            ['nombre' => 'Fernando', 'apellido_p' => 'Godoy', 'apellido_m' => 'Jiménez', 'celular' => 1234567890],
            ['nombre' => 'Monica', 'apellido_p' => 'Martinez', 'apellido_m' => 'López', 'celular' => 1234567891],
            ['nombre' => 'Lorenzo', 'apellido_p' => 'Herrera', 'apellido_m' => 'García', 'celular' => 1234567892],
            ['nombre' => 'Francisco', 'apellido_p' => 'González', 'apellido_m' => 'Rodríguez', 'celular' => 1234567893],
            ['nombre' => 'Lucia', 'apellido_p' => 'Castañeda', 'apellido_m' => 'Sánchez', 'celular' => 1234567894],
            ['nombre' => 'Maria', 'apellido_p' => 'Perez', 'apellido_m' => 'Torres', 'celular' => 1234567895],
            ['nombre' => 'Carlos', 'apellido_p' => 'Guadalupe', 'apellido_m' => 'Flores', 'celular' => 1234567896],
        ];

        $usuarios_creados = [];
        $personas_ids = [];

        foreach ($personas_data as $idx => $persona_data) {
            $persona = Persona::firstOrCreate(
                ['nombre' => $persona_data['nombre'], 'apellido_p' => $persona_data['apellido_p']],
                $persona_data
            );
            
            $personas_ids[] = $persona->id;

            // Crear usuario si no existe
            $usuario = Usuario::firstOrCreate(
                ['id_persona' => $persona->id],
                ['pass' => bcrypt('password'), 'admin' => false]
            );

            $usuarios_creados[$usuario->id] = [
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido_p,
            ];

            // Asignar a departamento
            $depa_id = $depa_ids[$idx] ?? $depa_ids[0];
            
            PerDep::firstOrCreate(
                ['id_persona' => $persona->id, 'id_depa' => $depa_id],
                [
                    'id_rol' => $id_rol,
                    'residente' => true,
                    'codigo' => str_pad((string)($idx + 1), 10, '0', STR_PAD_LEFT),
                    'fecha_inicio' => now(),
                ]
            );
        }

        // Crear algunos mensajes de prueba en MongoDB
        $usuarios_ids = array_keys($usuarios_creados);
        
        if (count($usuarios_ids) >= 2) {
            $mensajes_prueba = [
                [
                    'remitente' => $usuarios_ids[0],
                    'destinatario' => $usuarios_ids[1],
                    'id_depaR' => $depa_ids[0],
                    'id_depaD' => $depa_ids[1],
                    'mensaje' => '¡Hola! ¿Cómo estás?',
                    'tipo' => 'personal',
                    'leido' => true,
                    'fecha' => now()->subHours(2),
                ],
                [
                    'remitente' => $usuarios_ids[1],
                    'destinatario' => $usuarios_ids[0],
                    'id_depaR' => $depa_ids[1],
                    'id_depaD' => $depa_ids[0],
                    'mensaje' => 'Bien, ¿y tú?',
                    'tipo' => 'personal',
                    'leido' => true,
                    'fecha' => now()->subHours(1)->addMinutes(30),
                ],
                [
                    'remitente' => $usuarios_ids[0],
                    'destinatario' => $usuarios_ids[1],
                    'id_depaR' => $depa_ids[0],
                    'id_depaD' => $depa_ids[1],
                    'mensaje' => '¿Cuál es la cuota de este mes?',
                    'tipo' => 'personal',
                    'leido' => false,
                    'fecha' => now()->subMinutes(30),
                ],
                [
                    'remitente' => $usuarios_ids[1],
                    'destinatario' => $usuarios_ids[0],
                    'id_depaR' => $depa_ids[1],
                    'id_depaD' => $depa_ids[0],
                    'mensaje' => 'Son 2500 pesos',
                    'tipo' => 'personal',
                    'leido' => false,
                    'fecha' => now()->subMinutes(10),
                ],
            ];

            foreach ($mensajes_prueba as $mensaje_data) {
                Mensaje::create($mensaje_data);
            }
        }

        $this->command->info('✅ Contactos y mensajes de prueba creados exitosamente');
        $this->command->info('👥 ' . count($usuarios_creados) . ' contactos creados');
        $this->command->info('💬 ' . (count($usuarios_ids) >= 2 ? 4 : 0) . ' mensajes de prueba creados');
    }
}
