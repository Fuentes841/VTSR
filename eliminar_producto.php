<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

// Verificar que el usuario esté logueado y sea una tienda
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$tienda_id = $_SESSION['usuario_id'];
$response = ['success' => false, 'message' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingrediente_id'])) {
    $ingrediente_id = (int)$_POST['ingrediente_id'];
    
    try {
        $conn = getDBConnection();
        
        // Verificar si el producto está asociado a recetas
        $sql_check_recetas = "SELECT COUNT(*) as count 
                              FROM receta r 
                              JOIN ingrediente_por_receta ipr ON r.id_receta = ipr.receta_id 
                              JOIN tienda_receta tr ON r.id_receta = tr.receta_id 
                              WHERE ipr.ingrediente_id = ? AND tr.tienda_id = ?";
        
        $stmt_check_recetas = $conn->prepare($sql_check_recetas);
        $stmt_check_recetas->bind_param("ii", $ingrediente_id, $tienda_id);
        $stmt_check_recetas->execute();
        $result_check_recetas = $stmt_check_recetas->get_result();
        $row_check_recetas = $result_check_recetas->fetch_assoc();
        
        if ($row_check_recetas['count'] > 0) {
            $response['message'] = 'El producto está asociado a recetas y no puede ser eliminado.';
        } else {
            // Verificar si el producto está en pedidos
            $sql_check_pedidos = "SELECT COUNT(*) as count 
                                  FROM producto_pedido pp 
                                  WHERE pp.producto_tienda_id = ? AND pp.producto_ingrediente_id = ?";
            
            $stmt_check_pedidos = $conn->prepare($sql_check_pedidos);
            $stmt_check_pedidos->bind_param("ii", $tienda_id, $ingrediente_id);
            $stmt_check_pedidos->execute();
            $result_check_pedidos = $stmt_check_pedidos->get_result();
            $row_check_pedidos = $result_check_pedidos->fetch_assoc();
            
            if ($row_check_pedidos['count'] > 0) {
                $response['message'] = 'El producto está siendo usado en pedidos y no puede ser eliminado.';
            } else {
                // Eliminar el producto
                $sql_delete = "DELETE FROM producto WHERE tienda_id = ? AND ingrediente_id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $tienda_id, $ingrediente_id);
                
                if ($stmt_delete->execute()) {
                    if ($stmt_delete->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Producto eliminado correctamente.';
                    } else {
                        $response['message'] = 'El producto no existe o ya fue eliminado.';
                    }
                } else {
                    $response['message'] = 'Error al eliminar el producto de la base de datos.';
                }
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $response['message'] = 'Error de conexión: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Datos inválidos.';
}

echo json_encode($response);
?> 