<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_receta'])) {
    $id_receta = (int)$_POST['id_receta'];
    try {
        $conexion = getDBConnection();
        // Verificar que la receta pertenezca al usuario
        $query = "SELECT id_receta FROM receta WHERE id_receta = ? AND tienda_id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "ii", $id_receta, $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!mysqli_fetch_assoc($result)) {
            throw new Exception('No tienes permiso para eliminar esta receta.');
        }
        // Verificar si la receta está en el menú de otras tiendas
        $query_tiendas = "SELECT COUNT(*) as total, SUM(CASE WHEN tienda_id = ? THEN 1 ELSE 0 END) as propias FROM tienda_receta WHERE receta_id = ?";
        $stmt_tiendas = mysqli_prepare($conexion, $query_tiendas);
        mysqli_stmt_bind_param($stmt_tiendas, "ii", $_SESSION['usuario_id'], $id_receta);
        mysqli_stmt_execute($stmt_tiendas);
        $result_tiendas = mysqli_stmt_get_result($stmt_tiendas);
        $row_tiendas = mysqli_fetch_assoc($result_tiendas);
        if ($row_tiendas['total'] > 1 || ($row_tiendas['total'] == 1 && $row_tiendas['propias'] != 1)) {
            // Está en el menú de otra tienda
            $_SESSION['error'] = 'No se puede eliminar la receta porque forma parte del menú de otra tienda.';
        } else {
            // Eliminar ingredientes asociados (por si no hay ON DELETE CASCADE)
            $query_del_ing = "DELETE FROM ingrediente_por_receta WHERE receta_id = ?";
            $stmt_del_ing = mysqli_prepare($conexion, $query_del_ing);
            mysqli_stmt_bind_param($stmt_del_ing, "i", $id_receta);
            mysqli_stmt_execute($stmt_del_ing);
            // Eliminar de tienda_receta
            $query_del_tr = "DELETE FROM tienda_receta WHERE receta_id = ?";
            $stmt_del_tr = mysqli_prepare($conexion, $query_del_tr);
            mysqli_stmt_bind_param($stmt_del_tr, "i", $id_receta);
            mysqli_stmt_execute($stmt_del_tr);
            // Eliminar la receta
            $query_del_receta = "DELETE FROM receta WHERE id_receta = ?";
            $stmt_del_receta = mysqli_prepare($conexion, $query_del_receta);
            mysqli_stmt_bind_param($stmt_del_receta, "i", $id_receta);
            mysqli_stmt_execute($stmt_del_receta);
            $_SESSION['success'] = 'Receta eliminada correctamente.';
        }
        mysqli_close($conexion);
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar la receta: ' . $e->getMessage();
    }
}
header('Location: perfil.php');
exit(); 