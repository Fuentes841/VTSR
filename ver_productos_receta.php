<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: perfil.php');
    exit();
}

$id_receta = (int)$_GET['id'];
$tienda_id = $_SESSION['usuario_id'];

try {
    $conn = getDBConnection();
    // Verificar que la receta esté en el menú de la tienda
    $query = "SELECT 1 FROM tienda_receta WHERE tienda_id = ? AND receta_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $tienda_id, $id_receta);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        throw new Exception('No tienes permiso para ver los productos de esta receta.');
    }
    // Obtener los ingredientes y productos asociados a la receta para esta tienda
    $sql = "SELECT i.nombre AS nombre_ingrediente, i.unidad_medida, p.marca, p.peso_unidad, p.precio, p.stock
            FROM ingrediente_por_receta ir
            JOIN ingrediente i ON ir.ingrediente_id = i.id_ingrediente
            LEFT JOIN producto p ON p.ingrediente_id = i.id_ingrediente AND p.tienda_id = ?
            WHERE ir.receta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tienda_id, $id_receta);
    $stmt->execute();
    $result = $stmt->get_result();
    $productos = $result->fetch_all(MYSQLI_ASSOC);
    // Obtener nombre de la receta
    $sql_receta = "SELECT nombre FROM receta WHERE id_receta = ?";
    $stmt = $conn->prepare($sql_receta);
    $stmt->bind_param("i", $id_receta);
    $stmt->execute();
    $stmt->bind_result($nombre_receta);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos de la Receta - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo filemtime('css/estilos.css'); ?>">
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
</head>
<body>
    <header class="header">
        <div class="top-bar">
            <nav class="nav">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="recetas.php">Recetas</a></li>
                    <li><a href="tiendas.php">Tiendas</a></li>
                    <li class="perfil-menu">
                        <i class="fa-solid fa-user perfil-icono"></i>
                        <div class="perfil-dropdown">
                            <a href="perfil.php">Mi Perfil</a>
                            <a href="logout.php">Cerrar Sesión</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Productos de la Receta</span></h1>
        </div>
    </header>
    <main class="contenido-principal">
        <div class="perfil-container">
            <h2>Productos asociados a la receta: <?php echo htmlspecialchars($nombre_receta ?? ''); ?></h2>
            <?php if (isset($error)): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="formulario-tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Marca</th>
                            <th>Peso por Unidad</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Unidad de Medida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['nombre_ingrediente']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['marca'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($prod['peso_unidad'] ?? '-'); ?></td>
                                    <td><?php echo isset($prod['precio']) ? '$' . number_format($prod['precio'], 2) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($prod['stock'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No hay productos asociados a esta receta.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="perfil.php" class="btn-submit" style="margin-top:20px;">Volver a mi perfil</a>
        </div>
    </main>
    <footer class="footer">
        <div class="footer-contenido">
            <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 