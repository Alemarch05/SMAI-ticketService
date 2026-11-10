<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------
        // FAMILIA 1: HARDWARE / ENCENDIDO
        // ---------------------------------------------------
        Category::updateOrCreate(['id' => 1], [
            'name' => 'Fallo de Encendido / Hardware', 
            'description' => 'El equipo no enciende o presenta problemas físicos predeterminados',
            'is_active' => true
        ]);

        Category::updateOrCreate(['id' => 11], [
            'name' => 'Hardware (Personalizado)', 
            'description' => 'Incidencia de hardware especificada manualmente por el usuario',
            'is_active' => true
        ]);
        
        // ---------------------------------------------------
        // FAMILIA 2: RED / CONEXIÓN
        // ---------------------------------------------------
        Category::updateOrCreate(['id' => 2], [
            'name' => 'Fallo de Red o Conexión', 
            'description' => 'Problemas de conectividad local o internet predeterminados',
            'is_active' => true
        ]);

        Category::updateOrCreate(['id' => 22], [
            'name' => 'Red (Personalizado)', 
            'description' => 'Incidencia de red especificada manualmente por el usuario',
            'is_active' => true
        ]);
        
        // ---------------------------------------------------
        // FAMILIA 3: SOFTWARE / APLICACIONES
        // ---------------------------------------------------
        Category::updateOrCreate(['id' => 3], [
            'name' => 'Problema de Software / Aplicaciones', 
            'description' => 'Fallo en programas o sistema operativo predeterminado',
            'is_active' => true
        ]);

        Category::updateOrCreate(['id' => 33], [
            'name' => 'Software (Personalizado)', 
            'description' => 'Incidencia de software especificada manualmente por el usuario',
            'is_active' => true
        ]);

        // ---------------------------------------------------
        // FAMILIA 4: LENTITUD
        // ---------------------------------------------------
        Category::updateOrCreate(['id' => 4], [
            'name' => 'Lentitud del Equipo o Sistema', 
            'description' => 'El sistema o aplicaciones responden lento de forma predeterminada',
            'is_active' => true
        ]);

        Category::updateOrCreate(['id' => 44], [
            'name' => 'Lentitud (Personalizado)', 
            'description' => 'Incidencia de lentitud especificada manualmente por el usuario',
            'is_active' => true
        ]);
    }
}