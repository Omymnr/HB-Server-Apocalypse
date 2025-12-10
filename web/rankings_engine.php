<?php
// rankings_engine.php
// Este script lee los archivos del servidor y genera un JSON caché.

// Aumentamos el tiempo de ejecución por si hay muchos personajes
set_time_limit(300); 

// CONFIGURACIÓN
$charDir = "D:/HB-Server-Apocalypse/Files/Characters/";
$cacheFile = "rankings_cache.json";
$topLimit = 50; // Top 50 jugadores por categoría

// Arrays para almacenar datos
$players = [];

// 1. Escanear Directorios (AscII*)
// Usamos glob para encontrar solo carpetas que empiecen por AscII
$folders = glob($charDir . "AscII*", GLOB_ONLYDIR);

if ($folders) {
    foreach ($folders as $folder) {
        // Escanear los .txt dentro de cada carpeta AscII
        $files = glob($folder . "/*.txt");
        
        if ($files) {
            foreach ($files as $filePath) {
                // Leer contenido del archivo
                $content = file_get_contents($filePath);
                
                // Extraer nombre del archivo (Nombre del PJ)
                $charName = basename($filePath, ".txt");
                
                // Variables por defecto
                $level = 0;
                $eks = 0;
                $contrib = 0;
                
                // Parseo manual línea por línea (Más seguro que parse_ini_file para HB)
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Buscar Level
                    if (strpos($line, 'character-LEVEL') !== false) {
                        $parts = explode('=', $line);
                        $level = (int)trim(end($parts));
                    }
                    // Buscar EKs
                    if (strpos($line, 'character-EK-Count') !== false) {
                        $parts = explode('=', $line);
                        $eks = (int)trim(end($parts));
                    }
                    // Buscar Contribución
                    if (strpos($line, 'character-contribution') !== false) {
                        $parts = explode('=', $line);
                        $contrib = (int)trim(end($parts));
                    }
                }

                // Filtrar GMs o cuentas vacías (Opcional: Nivel > 0)
                if ($level > 0) {
                    $players[] = [
                        'name' => $charName,
                        'level' => $level,
                        'eks' => $eks,
                        'contrib' => $contrib
                    ];
                }
            }
        }
    }
}

// 2. Funciones de Ordenamiento
function sortByLevel($a, $b) {
    return $b['level'] - $a['level'];
}
function sortByEKs($a, $b) {
    return $b['eks'] - $a['eks'];
}
function sortByContrib($a, $b) {
    return $b['contrib'] - $a['contrib'];
}

// 3. Generar las 3 Listas
$rankLevel = $players;
usort($rankLevel, 'sortByLevel');
$rankLevel = array_slice($rankLevel, 0, $topLimit);

$rankEKs = $players;
usort($rankEKs, 'sortByEKs');
$rankEKs = array_slice($rankEKs, 0, $topLimit);

$rankContrib = $players;
usort($rankContrib, 'sortByContrib');
$rankContrib = array_slice($rankContrib, 0, $topLimit);

// 4. Guardar Cache
$data = [
    'timestamp' => time(),
    'level' => $rankLevel,
    'eks' => $rankEKs,
    'contrib' => $rankContrib
];

file_put_contents($cacheFile, json_encode($data));
?>