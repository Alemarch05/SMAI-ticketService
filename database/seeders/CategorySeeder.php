<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'No tengo Internet',
                'description' => 'Problemas con conectividad por cable Ethernet o Wi-Fi.',
                'is_active' => true,
            ],
            [
                'name' => 'El equipo no enciende',
                'description' => 'Fallas de alimentación eléctrica o encendido del computador.',
                'is_active' => true,
            ],
            [
                'name' => 'La impresora no imprime',
                'description' => 'Atascos, problemas de cola de impresión o falta de conexión.',
                'is_active' => true,
            ],
            [
                'name' => 'Lentitud en el equipo',
                'description' => 'Bloqueos de sistema, consumo alto de recursos o congelamiento.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
