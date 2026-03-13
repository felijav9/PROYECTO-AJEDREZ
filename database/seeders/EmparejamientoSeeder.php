<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Emparejamiento;
use App\Models\Jugador;
use App\Models\Ronda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmparejamientoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. LIMPIEZA INICIAL (Para que "actúe como la gente" y no duplique)
        Schema::disableForeignKeyConstraints();
        DB::table('emparejamientos')->truncate();
        Schema::enableForeignKeyConstraints();

        // Helper para obtener ID de jugador
        $getJ = function ($nombre) {
            $jugador = Jugador::where('nombre', trim($nombre))->first();
            if (!$jugador) {
                // Si no existe, lo crea para que el seeder no muera
                $this->command->warn("Jugador creado sobre la marcha: {$nombre}");
                return Jugador::create(['nombre' => $nombre])->id;
            }
            return $jugador->id;
        };

        $colores = ['A' => 'blancas', 'B' => 'negras', 'C' => 'blancas', 'D' => 'negras'];
        $tableros = ['A','B','C','D'];

        // Lógica Round Robin corregida (Rondas 1-5)
        $rondasEquipos = [
            1 => [['Bloops','Gambito de Dama'], ['Changos FC','Gambitos'], ['Apertura Maestra','Los Campeones']],
            2 => [['Bloops','Gambitos'], ['Gambito de Dama','Apertura Maestra'], ['Changos FC','Los Campeones']],
            3 => [['Bloops','Los Campeones'], ['Gambitos','Apertura Maestra'], ['Gambito de Dama','Changos FC']],
            4 => [['Bloops','Changos FC'], ['Gambitos','Los Campeones'], ['Gambito de Dama','Apertura Maestra']],
            5 => [['Gambitos','Gambito de Dama'], ['Apertura Maestra','Bloops'], ['Los Campeones','Changos FC']],
        ];

        // Rondas 6-10 (Vuelta con colores invertidos)
        for ($i = 6; $i <= 10; $i++) {
            $rondasEquipos[$i] = $rondasEquipos[$i - 5];
        }

        foreach ($rondasEquipos as $numRonda => $partidos) {
            // Aseguramos que la ronda exista en la DB
            $ronda = Ronda::firstOrCreate(['numero' => $numRonda]);

            foreach ($partidos as $partidoIndex => $partido) {
                [$equipoLocal, $equipoVisitante] = $partido;
                $estacion = ($partidoIndex + $numRonda - 1) % 3 + 1;

                foreach ($tableros as $mesaIndex => $tablero) {
                    $visitanteTablero = $tablero;
                    $localTablero = $tablero;

                    // Alternar colores por ronda para que no jueguen siempre con las mismas
                    $colorBase = $colores[$tablero];
                    if ($numRonda > 5) {
                        $color = ($colorBase == 'blancas') ? 'negras' : 'blancas';
                    } else {
                        $color = ($numRonda % 2 == 1) ? $colorBase : ($colorBase == 'blancas' ? 'negras' : 'blancas');
                    }

                    if ($color == 'blancas') {
                        $blancas = $getJ($this->getJugadorNombre($equipoLocal, $localTablero));
                        $negras = $getJ($this->getJugadorNombre($equipoVisitante, $visitanteTablero));
                    } else {
                        $blancas = $getJ($this->getJugadorNombre($equipoVisitante, $visitanteTablero));
                        $negras = $getJ($this->getJugadorNombre($equipoLocal, $localTablero));
                    }

                    Emparejamiento::create([
                        'ronda_id' => $ronda->id,
                        'blancas_id' => $blancas,
                        'negras_id' => $negras,
                        'mesa' => $mesaIndex + 1,
                        'estacion' => $estacion,
                        'resultado' => null,
                    ]);
                }
            }
        }
        $this->command->info("¡Seeder ejecutado con éxito!");
    }

    private function getJugadorNombre($equipo, $tablero)
    {
        $jugadores = [
            'Los Campeones' => ['A'=>'Daniel Solis','B'=>'David Nolasco','C'=>'Carla Blanco','D'=>'Andrea Roblero'],
            'Changos FC' => ['A'=>'Joaquin Mendez','B'=>'Abner Utuy','C'=>'Esteban Abril','D'=>'Nahil Ortiz'],
            'Gambitos' => ['A'=>'Juan Diego Pacheco','B'=>'Mario','C'=>'Joshua','D'=>'Alejandra Abril'],
            'Bloops' => ['A'=>'David Najera','B'=>'Christopher Diaz','C'=>'Ajbe Ortiz','D'=>'Fernando Jolon'],
            'Apertura Maestra' => ['A'=>'Steven Acevedo','B'=>'Andres Gomez','C'=>'Celeste Mendez','D'=>'Mateo Roblero'],
            'Gambito de Dama' => ['A'=>'Edgar Gonzalez','B'=>'Carlos Esteban','C'=>'Emiliano Pacheco','D'=>'Saqmuj Aguilar'],
        ];
        return $jugadores[$equipo][$tablero];
    }
}