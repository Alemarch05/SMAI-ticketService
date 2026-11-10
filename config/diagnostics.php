<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Árboles de Diagnóstico por Categoría
    |--------------------------------------------------------------------------
    | type: 'question' (SÍ/NO), 'solution' (Resuelto), 'ticket' (Requiere escalar).
    */

    // 1: Fallo de Encendido / Hardware
    '1' => [ 
        'node_1' => [
            'type' => 'question',
            'text' => '¿Verificó que el cable de poder esté conectado firmemente al tomacorriente y al equipo?',
            'yes_next' => 'node_2',
            'no_next' => 'solution_1',
        ],
        'node_2' => [
            'type' => 'question',
            'text' => '¿El tomacorriente tiene energía? (Pruebe conectando un cargador u otro dispositivo)',
            'yes_next' => 'node_3',
            'no_next' => 'solution_2',
        ],
        'node_3' => [
            'type' => 'question',
            'text' => 'Al presionar el botón de encendido, ¿el equipo hace algún sonido o enciende alguna luz?',
            'yes_next' => 'ticket_1',
            'no_next' => 'ticket_2',
        ],
        'solution_1' => [
            'type' => 'solution',
            'text' => 'Por favor, conecte el cable de poder firmemente e intente encender el equipo nuevamente.',
        ],
        'solution_2' => [
            'type' => 'solution',
            'text' => 'El problema parece ser eléctrico en el área. Por favor, reporte la falta de energía a mantenimiento físico.',
        ],
        'ticket_1' => [
            'type' => 'ticket',
            'text' => 'Posible fallo de hardware interno o pantalla. Por favor, continúe para generar un ticket.',
        ],
        'ticket_2' => [
            'type' => 'ticket',
            'text' => 'Posible fallo en la fuente de poder. Por favor, continúe para generar un ticket.',
        ]
    ],

    // 2: Fallo de red o conexión
    '2' => [
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
            'text' => 'El problema de red persiste. Por favor, continúe para generar un ticket de soporte.',
        ]
    ],

    // 3: Problema de Software / Aplicaciones
    '3' => [
        'node_1' => [
            'type' => 'question',
            'text' => '¿Cerró por completo el programa afectado y lo volvió a abrir?',
            'yes_next' => 'node_2',
            'no_next' => 'solution_1',
        ],
        'node_2' => [
            'type' => 'question',
            'text' => '¿El sistema arroja un código o mensaje de error específico en la pantalla?',
            'yes_next' => 'ticket_1',
            'no_next' => 'ticket_2',
        ],
        'solution_1' => [
            'type' => 'solution',
            'text' => 'Por favor, cierre la aplicación, reinicie su equipo e intente nuevamente. Muchos fallos se resuelven liberando la memoria caché.',
        ],
        'ticket_1' => [
            'type' => 'ticket',
            'text' => 'Por favor, anote el código de error para incluirlo en la descripción y continúe para generar un ticket.',
        ],
        'ticket_2' => [
            'type' => 'ticket',
            'text' => 'La aplicación no responde de manera silenciosa. Por favor, continúe para generar un ticket.',
        ]
    ],

    // 4: Lentitud del Equipo o Sistema
    '4' => [
        'node_1' => [
            'type' => 'question',
            'text' => '¿Ha reiniciado su equipo por completo en el día de hoy?',
            'yes_next' => 'node_2',
            'no_next' => 'solution_1',
        ],
        'node_2' => [
            'type' => 'question',
            'text' => '¿La lentitud ocurre únicamente al usar un programa en específico (ej. navegador o sistema interno)?',
            'yes_next' => 'ticket_1',
            'no_next' => 'node_3',
        ],
        'node_3' => [
            'type' => 'question',
            'text' => '¿Ha notado si el sistema está descargando o instalando actualizaciones de Windows en segundo plano?',
            'yes_next' => 'solution_2',
            'no_next' => 'ticket_2',
        ],
        'solution_1' => [
            'type' => 'solution',
            'text' => 'Por favor, reinicie su equipo (Inicio -> Apagar -> Reiniciar). El 80% de los problemas de lentitud se resuelven liberando la memoria RAM de esta manera.',
        ],
        'solution_2' => [
            'type' => 'solution',
            'text' => 'Las actualizaciones en segundo plano consumen muchos recursos. Por favor, permita que el equipo termine de actualizarse por unos 30 minutos y reinicie.',
        ],
        'ticket_1' => [
            'type' => 'ticket',
            'text' => 'El problema parece estar aislado en una aplicación específica. Por favor, continúe para generar un ticket indicando qué programa presenta la lentitud.',
        ],
        'ticket_2' => [
            'type' => 'ticket',
            'text' => 'El equipo presenta lentitud generalizada que requiere revisión técnica. Por favor, continúe para generar un ticket.',
        ]
    ],
];