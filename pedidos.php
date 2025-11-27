<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$tipo_usuario = $_SESSION['tipo_usuario'];
$usuario_id = $_SESSION['usuario_id'];

// Lógica para actualizar estado o calificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_estado']) && $tipo_usuario === 'tienda') {
        $pedido_id = $_POST['pedido_id'];
        $nuevo_estado = $_POST['estado'];
        $sql = "UPDATE pedido SET estado = ? WHERE id_pedido = ? AND tienda_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $nuevo_estado, $pedido_id, $usuario_id);
        $stmt->execute();
    } elseif (isset($_POST['actualizar_calificacion']) && $tipo_usuario === 'cliente') {
        $pedido_id = $_POST['pedido_id'];
        $calificacion = $_POST['calificacion'];
        $sql = "UPDATE pedido SET calificacion_cliente = ? WHERE id_pedido = ? AND cliente_id = ? AND estado = 'Entregado'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dii", $calificacion, $pedido_id, $usuario_id);
        $stmt->execute();
    }
    // Redirigir para evitar reenvío del formulario
    $query_params = http_build_query($_GET);
    header("Location: pedidos.php?" . $query_params);
    exit();
}

// Valores de filtro y orden
$filtro_estado = $_GET['estado'] ?? 'todos';
$orden = $_GET['orden'] ?? 'fecha_desc';

// Construir la consulta de pedidos
$params = [$usuario_id];
$types = "i";

$sql_base = "SELECT p.*, u.nombre, u.apellido, u.direccion, u.telefono, t.razon_social
             FROM pedido p ";

if ($tipo_usuario === 'tienda') {
    $sql_base .= "JOIN usuario u ON p.cliente_id = u.id LEFT JOIN tienda t ON p.tienda_id = t.id_tienda WHERE p.tienda_id = ?";
} else { // cliente
    $sql_base .= "JOIN tienda t ON p.tienda_id = t.id_tienda LEFT JOIN usuario u ON t.id_tienda = u.id WHERE p.cliente_id = ?";
}

if ($filtro_estado !== 'todos') {
    $sql_base .= " AND p.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

$order_by_clause = " ORDER BY ";
switch ($orden) {
    case 'fecha_asc':
        $order_by_clause .= "p.fecha ASC";
        break;
    case 'pedido_asc':
        $order_by_clause .= "p.id_pedido ASC";
        break;
    case 'pedido_desc':
        $order_by_clause .= "p.id_pedido DESC";
        break;
    default: // fecha_desc
        $order_by_clause .= "p.fecha DESC";
        break;
}

$sql = $sql_base . $order_by_clause;
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $sql_productos = "SELECT pp.*, i.nombre as ingrediente_nombre 
                      FROM producto_pedido pp
                      JOIN ingrediente i ON pp.producto_ingrediente_id = i.id_ingrediente
                      WHERE pp.pedido_id = ?";
    $stmt_prods = $conn->prepare($sql_productos);
    $stmt_prods->bind_param("i", $row['id_pedido']);
    $stmt_prods->execute();
    $result_prods = $stmt_prods->get_result();
    $productos_pedido = [];
    while($prod = $result_prods->fetch_assoc()){
        $productos_pedido[] = $prod;
    }
    $row['productos'] = $productos_pedido;
    $pedidos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="fondito">
        <h1><span class="light">Vive tu sabor</span> <span class="bold">Mis Pedidos</span></h1>
    </div>

    <main class="contenido-principal">
        <div class="pedidos-container">
            <h1>Mis Pedidos</h1>

            <!-- Formulario de Filtros y Orden -->
            <form method="GET" action="pedidos.php" class="filtros-pedidos">
                <div class="filtro-item">
                    <label for="estado">Filtrar por estado:</label>
                    <select name="estado" id="estado" onchange="this.form.submit()">
                        <option value="todos" <?php echo ($filtro_estado === 'todos') ? 'selected' : ''; ?>>Todos</option>
                        <option value="Pendiente" <?php echo ($filtro_estado === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="En preparacion" <?php echo ($filtro_estado === 'En preparacion') ? 'selected' : ''; ?>>En preparación</option>
                        <option value="Enviado" <?php echo ($filtro_estado === 'Enviado') ? 'selected' : ''; ?>>Enviado</option>
                        <option value="Entregado" <?php echo ($filtro_estado === 'Entregado') ? 'selected' : ''; ?>>Entregado</option>
                        <option value="Cancelado" <?php echo ($filtro_estado === 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="filtro-item">
                    <label for="orden">Ordenar por:</label>
                    <select name="orden" id="orden" onchange="this.form.submit()">
                        <option value="fecha_desc" <?php echo ($orden === 'fecha_desc') ? 'selected' : ''; ?>>Fecha (Más recientes primero)</option>
                        <option value="fecha_asc" <?php echo ($orden === 'fecha_asc') ? 'selected' : ''; ?>>Fecha (Más antiguos primero)</option>
                        <option value="pedido_desc" <?php echo ($orden === 'pedido_desc') ? 'selected' : ''; ?>>Nº Pedido (Mayor a menor)</option>
                        <option value="pedido_asc" <?php echo ($orden === 'pedido_asc') ? 'selected' : ''; ?>>Nº Pedido (Menor a mayor)</option>
                    </select>
                </div>
            </form>

            <?php if (empty($pedidos)): ?>
                <p>No tienes pedidos que coincidan con los filtros seleccionados.</p>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="pedido-card">
                        <div class="pedido-header">
                            <div>
                                <h3>Pedido #<?php echo $pedido['id_pedido']; ?></h3>
                                <small>Fecha: <?php echo date("d/m/Y H:i", strtotime($pedido['fecha'])); ?></small>
                            </div>
                            <div>
                                <strong>Total: $<?php echo number_format($pedido['total'], 2); ?></strong>
                            </div>
                        </div>
                        <div class="pedido-info-adicional">
                            <?php if ($tipo_usuario === 'tienda'): ?>
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']); ?> (<?php echo htmlspecialchars($pedido['direccion']); ?> | <?php echo htmlspecialchars($pedido['telefono']); ?>)</p>
                            <?php else: ?>
                                <p><strong>Tienda:</strong> <?php echo htmlspecialchars($pedido['razon_social']); ?> (<?php echo htmlspecialchars($pedido['direccion']); ?> | <?php echo htmlspecialchars($pedido['telefono']); ?>)</p>
                            <?php endif; ?>
                        </div>
                        <div class="pedido-body">
                            <!-- Desglose de Productos -->
                            <h4 class="titulo-seccion-pedido">Productos del Pedido:</h4>
                            <table class="tabla-productos-pedido">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pedido['productos'] as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['ingrediente_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['marca_momento']); ?></td>
                                        <td><?php echo rtrim(rtrim(number_format($producto['cantidad'], 3, ',', '.'), '0'), ','); ?></td>
                                        <td>$<?php echo number_format($producto['precio_momento'], 2, ',', '.'); ?></td>
                                        <td>$<?php echo number_format($producto['cantidad'] * $producto['precio_momento'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- Formularios de Acción -->
                            <div class="acciones-pedido">
                                <?php if ($tipo_usuario === 'tienda'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id_pedido']; ?>">
                                        <input type="hidden" name="actualizar_estado" value="1">
                                        <label><strong>Estado:</strong></label>
                                        <select name="estado" class="selector-estado" onchange="this.form.submit()">
                                            <option value="Pendiente" <?php echo $pedido['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="En preparacion" <?php echo $pedido['estado'] === 'En preparacion' ? 'selected' : ''; ?>>En preparación</option>
                                            <option value="Enviado" <?php echo $pedido['estado'] === 'Enviado' ? 'selected' : ''; ?>>Enviado</option>
                                            <option value="Entregado" <?php echo $pedido['estado'] === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                                            <option value="Cancelado" <?php echo $pedido['estado'] === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                    </form>
                                    <p><strong>Calificación Cliente:</strong> <?php echo $pedido['calificacion_cliente'] ? $pedido['calificacion_cliente'] . ' ★' : 'N/A'; ?></p>
                                <?php else: // Cliente ?>
                                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($pedido['estado']); ?></p>
                                    <?php if ($pedido['estado'] === 'Entregado'): ?>
                                        <?php if (!$pedido['calificacion_cliente']): ?>
                                            <form method="POST">
                                                <input type="hidden" name="pedido_id" value="<?php echo $pedido['id_pedido']; ?>">
                                                <label><strong>Calificar:</strong></label>
                                                <input type="number" name="calificacion" min="1" max="5" step="0.5" value="<?php echo $pedido['calificacion_cliente']; ?>" required class="input-calificacion">
                                                <button type="submit" name="actualizar_calificacion">Enviar</button>
                                            </form>
                                        <?php else: ?>
                                            <p><strong>Mi Calificación:</strong> <?php echo $pedido['calificacion_cliente'] . ' ★'; ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p><strong>Mi Calificación:</strong> <?php echo $pedido['calificacion_cliente'] ? $pedido['calificacion_cliente'] . ' ★' : 'Aún no disponible'; ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-contenido">
            <div class="footer-enlaces">
                <a href="index.php">Inicio</a>
                <a href="recetas.php">Recetas</a>
                <a href="contacto.php">Contacto</a>
                <a href="privacidad.php">Privacidad</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <script>
    // Script para el menú desplegable que ya está en el header copiado
    document.addEventListener('DOMContentLoaded', function() {
        const perfilMenu = document.querySelector('.perfil-menu');
        if (perfilMenu) {
            const dropdown = perfilMenu.querySelector('.perfil-dropdown');
            perfilMenu.addEventListener('click', function(event) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                event.stopPropagation();
            });
            document.addEventListener('click', function(event) {
                if (!perfilMenu.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html> 