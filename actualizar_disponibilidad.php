<?php
session_start();
require_once 'config.php';

// Verificar que el usuario sea una tienda y esté logueado
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// Obtener los datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$receta_id = $data['receta_id'] ?? null;
$disponible = $data['disponible'] ?? null;
$tienda_id = $_SESSION['usuario_id'];

if ($receta_id === null || $disponible === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Faltan datos en la solicitud.']);
    exit();
}

try {
    $conn = getDBConnection();

    if ($disponible) {
        // Verificar stock de todos los ingredientes asociados a la receta para esta tienda
        $sql = "SELECT i.nombre, p.stock
                FROM ingrediente_por_receta ir
                JOIN ingrediente i ON ir.ingrediente_id = i.id_ingrediente
                LEFT JOIN producto p ON p.ingrediente_id = i.id_ingrediente AND p.tienda_id = ?
                WHERE ir.receta_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tienda_id, $receta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ingredientes_sin_stock = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['stock'] === null || $row['stock'] <= 0) {
                $ingredientes_sin_stock[] = $row['nombre'];
            }
        }
        if (!empty($ingredientes_sin_stock)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No posee stock de los siguientes productos:\n- ' . implode("\n- ", $ingredientes_sin_stock)
            ]);
            exit();
        }
    }

    // Actualizar el estado de disponibilidad
    $sql = "UPDATE tienda_receta SET disponible = ? WHERE tienda_id = ? AND receta_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $disponible_val = $disponible ? 1 : 0;
    $stmt->bind_param("iii", $disponible_val, $tienda_id, $receta_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Disponibilidad actualizada.']);
    } else {
        throw new Exception("Error al actualizar la disponibilidad: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?> 