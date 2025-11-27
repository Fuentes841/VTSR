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
$response = ['tiene_recetas' => false, 'recetas' => [], 'tiene_pedidos' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingrediente_id'])) {
    $ingrediente_id = (int)$_POST['ingrediente_id'];
    
    try {
        $conn = getDBConnection();
        
        // Buscar recetas que usan este ingrediente y están en el menú de la tienda
        $sql_recetas = "SELECT DISTINCT r.nombre 
                        FROM receta r 
                        JOIN ingrediente_por_receta ipr ON r.id_receta = ipr.receta_id 
                        JOIN tienda_receta tr ON r.id_receta = tr.receta_id 
                        WHERE ipr.ingrediente_id = ? AND tr.tienda_id = ?";
        
        $stmt_recetas = $conn->prepare($sql_recetas);
        $stmt_recetas->bind_param("ii", $ingrediente_id, $tienda_id);
        $stmt_recetas->execute();
        $result_recetas = $stmt_recetas->get_result();
        
        $recetas = [];
        while ($row = $result_recetas->fetch_assoc()) {
            $recetas[] = $row['nombre'];
        }
        
        if (!empty($recetas)) {
            $response['tiene_recetas'] = true;
            $response['recetas'] = $recetas;
        }
        
        // Verificar si el producto está siendo usado en pedidos
        $sql_pedidos = "SELECT COUNT(*) as count 
                        FROM producto_pedido pp 
                        WHERE pp.producto_tienda_id = ? AND pp.producto_ingrediente_id = ?";
        
        $stmt_pedidos = $conn->prepare($sql_pedidos);
        $stmt_pedidos->bind_param("ii", $tienda_id, $ingrediente_id);
        $stmt_pedidos->execute();
        $result_pedidos = $stmt_pedidos->get_result();
        $row_pedidos = $result_pedidos->fetch_assoc();
        
        if ($row_pedidos['count'] > 0) {
            $response['tiene_pedidos'] = true;
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $response['error'] = 'Error de conexión: ' . $e->getMessage();
    }
}

echo json_encode($response);
?> 