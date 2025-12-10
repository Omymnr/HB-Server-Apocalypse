<?php
// 1. FUNCIÓN PARA OBTENER RANGO Y COLOR
function getUserRank($rank_id, $lang = 'es') {
    // Definición de Rangos
    $ranks = [
        1 => [
            'en' => 'Member', 
            'es' => 'Miembro', 
            'color' => '#e9c10eff', // Gris claro
            'style' => 'font-weight:normal;'
        ],
        2 => [
            'en' => 'Veteran', 
            'es' => 'Veterano', 
            'color' => '#1da318ff', // Dorado
            'style' => 'font-weight:bold;'
        ],
        3 => [
            'en' => 'Administrator', 
            'es' => 'Administrador', 
            'color' => '#ff0000ff', // Rojo claro
            'style' => 'font-weight:bold; text-shadow: 0 0 5px rgba(255, 77, 77, 0.4);'
        ],
        4 => [
            'en' => 'Server Owner', 
            'es' => 'Dueño del Servidor', 
            'color' => '#03c9faff', // Rojo sangre oscuro
            'style' => 'font-weight:bold; text-transform:uppercase; text-shadow: 0 0 10px rgba(179, 0, 0, 0.6);'
        ]
    ];

    // Si el rango no existe (por error), devolver Miembro
    if (!isset($ranks[$rank_id])) return $ranks[1];

    return $ranks[$rank_id];
}

// 2. FUNCIÓN PARA DAR FORMATO AL TEXTO (BBCODE)
function parseBBCode($text) {
    // Primero protegemos contra HTML malicioso
    $text = htmlspecialchars($text);

    // Definimos las reglas de conversión
    $find = [
        '/\[b\](.*?)\[\/b\]/is',
        '/\[i\](.*?)\[\/i\]/is',
        '/\[u\](.*?)\[\/u\]/is',
        '/\[s\](.*?)\[\/s\]/is', // Tachado
        '/\[size=(.*?)\](.*?)\[\/size\]/is',
        '/\[color=(.*?)\](.*?)\[\/color\]/is',
        '/\[center\](.*?)\[\/center\]/is',
        '/\[img\](.*?)\[\/img\]/is',
        '/\[url=(.*?)\](.*?)\[\/url\]/is',
        '/\n/' // Saltos de línea
    ];

    $replace = [
        '<strong>$1</strong>',
        '<em>$1</em>',
        '<span style="text-decoration:underline;">$1</span>',
        '<span style="text-decoration:line-through;">$1</span>',
        '<span style="font-size:$1px;">$2</span>',
        '<span style="color:$1;">$2</span>',
        '<div style="text-align:center;">$1</div>',
        '<img src="$1" style="max-width:100%; border:1px solid #333;">',
        '<a href="$1" target="_blank" style="color:var(--primary-gold); text-decoration:underline;">$2</a>',
        '<br>'
    ];

    return preg_replace($find, $replace, $text);
}

// 3. FUNCIÓN PARA VERIFICAR PERMISOS DE ADMINISTRACIÓN
function canManageUser($myRank, $targetRank) {
    // El Owner (4) puede tocar a todos
    if ($myRank == 4) return true;
    
    // El Admin (3) puede tocar a Veteranos(2), Miembros(1) y Guests(0)
    // PERO NO a Owners (4) ni a otros Admins (3)
    if ($myRank == 3 && $targetRank < 3) return true;
    
    return false;
}

// 4. VERIFICAR PERMISO DE EDICIÓN DE POST
function canEditPost($myRank, $myUserId, $postAuthorId) {
    // Si soy Owner (4) o Admin (3), puedo editar TODO.
    if ($myRank >= 3) return true;
    
    // Si soy el autor del mensaje, puedo editarlo.
    if ($myUserId == $postAuthorId) return true;
    
    return false;
}
?>