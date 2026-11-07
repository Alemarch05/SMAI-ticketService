<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Árboles de Diagnóstico por Categoría
    |--------------------------------------------------------------------------
    | Cada key ('1', '2', etc.) representa el ID de la categoría en la BD.
    | type: 'question' (SÍ/NO), 'solution' (Resuelto), 'ticket' (Requiere escalar).
    */

    '1' => [ 
        'node_1' => [
            'type' => 'question',
            'text' => '¿El cable de red está conectado correctamente a su equipo (o está conectado al WiFi)?',
            'yes_next' => 'node_2',
            'no_next' => 'solution_1',
        ],
        'node_2' => [
            'type' => 'question',
            'text' => '¿Reinició el router o switch de su área?',
            'yes_next' => 'ticket_1',
            'no_next' => 'solution_2',
        ],
        'solution_1' => [
            'type' => 'solution',
            'text' => 'Por favor, conecte el cable de red o conéctese a la red WiFi e intente nuevamente.',
        ],
        'solution_2' => [
            'type' => 'solution',
            'text' => 'Por favor, reinicie el router/switch, espere 2 minutos y verifique si tiene conexión.',
        ],
        'ticket_1' => [
            'type' => 'ticket',
            'text' => 'El problema persiste. Por favor, continúe para generar un ticket de soporte.',
        ]
    ],

    
    '2' => [], // Equipo no enciende
    '3' => [], // Falla de impresión
    '4' => [], // Lentitud
];