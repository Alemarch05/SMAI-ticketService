<?php

return [
    'Fallo de Encendido / Hardware' => [
        'Revisé las conexiones eléctricas, la regleta y el cable de poder.',
        'Mantuve presionado el botón de encendido 15 segundos para descargar la estática.',
        'Limpié y reinserté las memorias RAM y probé con otra fuente de poder.',
    ],
    'Hardware (Personalizado)' => [
        'Inspeccioné físicamente el componente reportado para descartar daños visibles.',
        'Ajusté los conectores internos y periféricos vinculados al fallo.',
        'Realicé pruebas aisladas y reemplacé la pieza defectuosa.',
    ],
    'Fallo de Red o Conexión' => [
        'Verifiqué que el modo avión estuviera apagado y revisé el cable Ethernet.',
        'Ejecuté ipconfig /release, ipconfig /renew y ipconfig /flushdns en consola.',
        'Actualicé el controlador de la tarjeta de red y probé la salida a internet.',
    ],
    'Red (Personalizado)' => [
        'Revisé la configuración de IP estática/DHCP y la puerta de enlace del equipo.',
        'Comprobé el estado del puerto en el switch y el punto de acceso cercano.',
        'Restablecí los adaptadores de red y validé los permisos de conexión local.',
    ],
    'Problema de Software / Aplicaciones' => [
        'Forcé el cierre de la aplicación desde el Administrador de tareas y la reinicié.',
        'Limpié la caché y los archivos temporales del programa afectado.',
        'Ejecuté una reparación de la instalación y validé los permisos de usuario.',
    ],
    'Software (Personalizado)' => [
        'Revisé el visor de eventos para identificar el error exacto de la aplicación.',
        'Verifiqué e instalé las librerías y dependencias faltantes del programa.',
        'Reinstalé la versión compatible del software y ajusté su configuración.',
    ],
    'Lentitud del Equipo o Sistema' => [
        'Cerré las aplicaciones pesadas en segundo plano y liberé espacio en el disco C:.',
        'Deshabilité los programas innecesarios del inicio automático de Windows.',
        'Ejecuté sfc /scannow y DISM para reparar archivos corruptos del sistema.',
    ],
    'Lentitud (Personalizado)' => [
        'Monitoreé el consumo de CPU, RAM y disco durante la tarea específica.',
        'Detuve los procesos que generaban cuello de botella en el rendimiento.',
        'Realicé limpieza de temporales y verifiqué el estado de salud del disco.',
    ],
];
