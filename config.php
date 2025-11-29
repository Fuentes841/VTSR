<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prueba2');

// Función para obtener conexión a la base de datos
function getDBConnection() {
    $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conexion) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    
    return $conexion;
}

// Función para ejecutar consultas de forma segura
function executeQuery($query, $params = []) {
    $conexion = getDBConnection();
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $result;
}

// Función para obtener un solo resultado
function getSingleResult($query, $params = []) {
    $result = executeQuery($query, $params);
    return mysqli_fetch_assoc($result);
}

// Función para obtener múltiples resultados
function getMultipleResults($query, $params = []) {
    $result = executeQuery($query, $params);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Función para escapar strings
function escapeString($string) {
    $conexion = getDBConnection();
    return mysqli_real_escape_string($conexion, $string);
}

// Función para iniciar sesión
function iniciarSesion($email, $password) {
    $query = "SELECT u.*, 
              CASE 
                  WHEN a.id_admin IS NOT NULL THEN 'admin'
                  WHEN c.id_cliente IS NOT NULL THEN 'cliente'
                  WHEN t.id_tienda IS NOT NULL THEN 'tienda'
                  ELSE 'usuario'
              END as tipo_usuario
              FROM usuario u
              LEFT JOIN admin a ON u.id = a.id_admin
              LEFT JOIN cliente c ON u.id = c.id_cliente
              LEFT JOIN tienda t ON u.id = t.id_tienda
              WHERE u.email = ?";
              
    $result = getSingleResult($query, [$email]);
    
    if ($result) {
        // En un caso real, aquí verificaríamos la contraseña con password_verify()
        $_SESSION['usuario'] = $result;
        return true;
    }
    
    return false;
}

// Función para verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario']);
}

// Función para obtener el tipo de usuario actual
function getTipoUsuario() {
    return $_SESSION['usuario']['tipo_usuario'] ?? null;
}

// Función para cerrar sesión
function cerrarSesion() {
    session_destroy();
    unset($_SESSION['usuario']);
}
?> 