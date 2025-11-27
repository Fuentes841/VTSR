<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_receta'])) {
    $id_receta = (int)$_POST['id_receta'];
    try {
        $conexion = getDBConnection();
        // Verificar que la receta esté en el menú de la tienda
        $query = "SELECT 1 FROM tienda_receta WHERE tienda_id = ? AND receta_id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['usuario_id'], $id_receta);
        mysqli_stmt_execute($stmt);
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            throw new Exception('No tienes permiso para eliminar esta receta del menú.');
        }
        // Eliminar la relación en tienda_receta
        $query_del = "DELETE FROM tienda_receta WHERE tienda_id = ? AND receta_id = ?";
        $stmt_del = mysqli_prepare($conexion, $query_del);
        mysqli_stmt_bind_param($stmt_del, "ii", $_SESSION['usuario_id'], $id_receta);
        mysqli_stmt_execute($stmt_del);
        mysqli_close($conexion);
        $_SESSION['success'] = 'Receta eliminada del menú correctamente.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar la receta del menú: ' . $e->getMessage();
    }
}
header('Location: perfil.php');
exit(); 