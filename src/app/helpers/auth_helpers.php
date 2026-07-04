<?php
/**
 * Agregar esta función al archivo helpers.php o crear un nuevo archivo
 * app/helpers/auth_helpers.php
 */

/**
 * Obtener el usuario actual de la sesión
 * @return array|null
 */
function get_usuario_actual() {
    // Verificar si hay una sesión activa
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar si existe el ID del usuario en la sesión
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    // Si ya tenemos los datos del usuario en sesión, devolverlos
    if (isset($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }
    
    // Si solo tenemos el ID, buscar los datos del usuario
    $db = Database::getInstance();
    $sql = "SELECT id, nombre_completo, nombre_usuario, rol 
            FROM usuarios 
            WHERE id = ? AND activo = 1";
    
    $stmt = $db->query($sql, [$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Guardar en sesión para no consultar cada vez
        $_SESSION['user_data'] = $usuario;
        return $usuario;
    }
    
    return null;
}

/**
 * Obtener solo el ID del usuario actual
 * @return int|null
 */
function get_usuario_id() {
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Verificar si el usuario está autenticado
 * @return bool
 */
function usuario_autenticado() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}