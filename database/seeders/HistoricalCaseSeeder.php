<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalCaseSeeder extends Seeder
{
    public function run(): void
    {
        $cases = [
            [
                'problem_type' => 'No tengo Internet',
                'cause' => 'Adaptador de red deshabilitado',
                'solution' => 'Se accedió a la configuración de red en el Panel de Control y se habilitó el adaptador Ethernet.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'problem_type' => 'No tengo Internet',
                'cause' => 'Cable Ethernet desconectado o defectuoso',
                'solution' => 'Se reemplazó el parche de red (cable Ethernet) conectado a la roseta de la pared.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'problem_type' => 'El equipo no enciende',
                'cause' => 'Multitoma / Regulador apagado o desconectado',
                'solution' => 'Se verificó la conexión de energía eléctrica y se encendió el interruptor del regulador.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('TK_historical_cases')->insert($cases);
    }
}