<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío.']);
    exit();
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    $cliente_id = $_SESSION['usuario_id'];
    // Se asume que todos los productos son de la misma tienda
    $tienda_id = $data[0]['tienda_id'];
    $granTotal = 0;
    $productos_a_procesar = [];

    // Recorrer productos fusionados
    foreach ($data as $prod) {
        $ingrediente_id = $prod['id_ingrediente'];
        $cantidad = isset($prod['unidades_a_comprar']) ? $prod['unidades_a_comprar'] : $prod['cantidad_total'];
        $sql_prod = "SELECT stock, precio, marca, ruta_imagen, peso_unidad FROM producto WHERE tienda_id = ? AND ingrediente_id = ?";
        $stmt_prod = $conn->prepare($sql_prod);
        $stmt_prod->bind_param("ii", $tienda_id, $ingrediente_id);
        $stmt_prod->execute();
        $producto = $stmt_prod->get_result()->fetch_assoc();

        if (!$producto) throw new Exception("Producto no encontrado: ingrediente ID " . $ingrediente_id);

        if ($producto['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para el ingrediente ID " . $ingrediente_id);
        }

        $costo_producto = $cantidad * $producto['precio'];
        $granTotal += $costo_producto;
        $productos_a_procesar[] = [
            'ingrediente_id' => $ingrediente_id,
            'cantidad' => $cantidad,
            'precio_momento' => $producto['precio'],
            'marca_momento' => $producto['marca'],
            'ruta_imagen_momento' => $producto['ruta_imagen']
        ];
    }
    
    // 2. Insertar pedido
    $sql_pedido = "INSERT INTO pedido (cliente_id, tienda_id, total, estado) VALUES (?, ?, ?, 'Pendiente')";
    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("iid", $cliente_id, $tienda_id, $granTotal);
    $stmt_pedido->execute();
    $id_pedido = $conn->insert_id;

    // 3. Insertar productos del pedido y actualizar stock
    $sql_prod_pedido = "INSERT INTO producto_pedido (pedido_id, producto_tienda_id, producto_ingrediente_id, cantidad, precio_momento, marca_momento, ruta_imagen_momento) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_prod = $conn->prepare($sql_prod_pedido);

    $sql_update_stock = "UPDATE producto SET stock = stock - ? WHERE tienda_id = ? AND ingrediente_id = ?";
    $stmt_update = $conn->prepare($sql_update_stock);

    foreach ($productos_a_procesar as $p) {
        $stmt_prod->bind_param("iiiddss", $id_pedido, $tienda_id, $p['ingrediente_id'], $p['cantidad'], $p['precio_momento'], $p['marca_momento'], $p['ruta_imagen_momento']);
        $stmt_prod->execute();
        
        $stmt_update->bind_param("dii", $p['cantidad'], $tienda_id, $p['ingrediente_id']);
        $stmt_update->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Pedido creado con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close(); 