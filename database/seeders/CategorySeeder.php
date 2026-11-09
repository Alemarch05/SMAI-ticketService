<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Usamos updateOrCreate para evitar duplicados si lo ejecutas varias veces
        Category::updateOrCreate(['id' => 1], [
            'name' => 'El equipo no enciende', 
            'description' => 'Fallo eléctrico o de hardware',
            'is_active' => true
        ]);
        
        Category::updateOrCreate(['id' => 2], [
            'name' => 'Fallo de red o conexión', 
            'description' => 'Problemas de conectividad local o internet',
            'is_active' => true
        ]);
        
        Category::updateOrCreate(['id' => 3], [
            'name' => 'Problema de software', 
            'description' => 'Fallo en aplicaciones o sistema operativo',
            'is_active' => true
        ]);
        
        Category::updateOrCreate(['id' => 99], [
            'name' => 'Otros', 
            'description' => 'Incidencia especificada manualmente por el usuario',
            'is_active' => true
        ]);
    }
}