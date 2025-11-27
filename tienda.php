<?php
session_start();
require_once 'config.php';

// Validar que se haya proporcionado un ID de tienda
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header('Location: tiendas.php');
    exit();
}

$ID_TIENDA = $_GET["id"];

try {
    $conn = getDBConnection();

    // Obtener información completa de la tienda
    $query_tienda = "SELECT t.*, u.direccion, u.ruta_imagen, cp.localidad as nombre_localidad, cp.provincia 
                     FROM tienda t
                     JOIN usuario u ON t.id_tienda = u.id
                     LEFT JOIN codigo_postal cp ON u.localidad = cp.id
                     WHERE t.id_tienda = ?";
    $stmt_tienda = $conn->prepare($query_tienda);
    $stmt_tienda->bind_param("i", $ID_TIENDA);
    $stmt_tienda->execute();
    $result_tienda = $stmt_tienda->get_result();
    $tienda = $result_tienda->fetch_assoc();

    if (!$tienda) {
        // Si no se encuentra la tienda, redirigir
        header('Location: tiendas.php');
        exit();
    }

    // Obtener las recetas DISPONIBLES de la tienda
    $query_recetas = "SELECT r.* 
                      FROM receta r
                      JOIN tienda_receta tr ON r.id_receta = tr.receta_id
                      WHERE tr.tienda_id = ? AND tr.disponible = 1";
    $stmt_recetas = $conn->prepare($query_recetas);
    $stmt_recetas->bind_param("i", $ID_TIENDA);
    $stmt_recetas->execute();
    $result_recetas = $stmt_recetas->get_result();
    $recetas_disponibles = $result_recetas->fetch_all(MYSQLI_ASSOC);

    $conn->close();

} catch (Exception $e) {
    error_log("Error en tienda.php: " . $e->getMessage());
    $error = "Ocurrió un error al cargar la información de la tienda.";
    // Opcional: inicializar variables para evitar errores en el HTML
    $tienda = [];
    $recetas_disponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($tienda['razon_social'] ?? 'Tienda'); ?> - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css" />
</head>
<body>
    <header class="header">
        <div class="top-bar">
            <nav class="nav">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="recetas.php">Recetas</a></li>
                    <li><a href="tiendas.php">Tiendas</a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="perfil-menu">
                            <i class="fa-solid fa-user perfil-icono"></i>
                            <div class="perfil-dropdown">
                                <a href="perfil.php">Mi Perfil</a>
                                <a href="logout.php">Cerrar Sesión</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="registro.php">Registrarse</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="carrito.php" id="carrito-nav" title="Ver Carrito de Compras">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span class="contador-carrito" id="contador-carrito-items">
                                <?php echo isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu sabor</span> <span class="bold"><?php echo htmlspecialchars($tienda['razon_social'] ?? 'Tienda'); ?></span></h1>
        </div>
    </header>

    <?php if (isset($_SESSION['nombre'])): ?>
        <div class="mensaje-bienvenida">
            ¡Bienvenido <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
        </div>
    <?php endif; ?>

    <main class="contenido-principal">
        <?php if (isset($error)): ?>
            <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="perfil-container">
                <div id="info-display">
                    <div class="perfil-header">
                        <img src="<?php echo htmlspecialchars($tienda['ruta_imagen'] ?? 'img/default-store.png'); ?>" 
                             alt="Logo de <?php echo htmlspecialchars($tienda['razon_social']); ?>" class="perfil-imagen">
                    </div>
                    <div class="info-no-editable">
                        <h3>Información de la Tienda</h3>
                        <p><strong>Razón Social:</strong> <?php echo htmlspecialchars($tienda['razon_social']); ?></p>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($tienda['descripcion']); ?></p>
                        <p><strong>Rubro:</strong> <?php echo htmlspecialchars($tienda['rubro']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($tienda['direccion'] ?? ''); ?>, <?php echo htmlspecialchars($tienda['nombre_localidad'] ?? ''); ?>, <?php echo htmlspecialchars($tienda['provincia'] ?? ''); ?></p>
                    </div>
                     <div class="info-no-editable">
                        <h3>Estadísticas</h3>
                        <p><strong>Cantidad de ventas:</strong> <?php echo $tienda['cantidad_ventas'] ?? 0; ?></p>
                        <p><strong>Calificación:</strong> <?php echo number_format($tienda['calificacion'] ?? 0, 1); ?> <i class="fas fa-star" style="color: #ffc107;"></i></p>
                    </div>
                </div>

                <div class="recetas-tienda-container">
                    <h2>Menú Disponible</h2>
                    <?php if (empty($recetas_disponibles)): ?>
                        <p style="text-align: center;">Esta tienda no tiene recetas disponibles en su menú por el momento.</p>
                    <?php else: ?>
                        <div class="grid-recetas">
                            <?php foreach ($recetas_disponibles as $receta): ?>
                                <?php
                                // Obtener ingredientes y productos de la tienda para esta receta
                                $conn = getDBConnection();
                                $sql = "SELECT i.id_ingrediente, i.nombre, i.condimento, i.unidad_medida, ir.cantidad, p.peso_unidad, p.precio, p.marca
                                        FROM ingrediente_por_receta ir
                                        JOIN ingrediente i ON ir.ingrediente_id = i.id_ingrediente
                                        LEFT JOIN producto p ON p.ingrediente_id = i.id_ingrediente AND p.tienda_id = ?
                                        WHERE ir.receta_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ii", $ID_TIENDA, $receta['id_receta']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $ingredientes = [];
                                while ($row = $result->fetch_assoc()) {
                                    $ingredientes[] = [
                                        'id_ingrediente' => $row['id_ingrediente'],
                                        'nombre' => $row['nombre'],
                                        'condimento' => (int)$row['condimento'],
                                        'unidad_medida' => $row['unidad_medida'],
                                        'cantidad' => (float)$row['cantidad'],
                                        'peso_unidad' => $row['peso_unidad'] !== null ? (float)$row['peso_unidad'] : null,
                                        'precio' => $row['precio'] !== null ? (float)$row['precio'] : 0,
                                        'marca' => isset($row['marca']) ? $row['marca'] : null
                                    ];
                                }
                                $stmt->close();
                                $conn->close();
                                ?>
                                <div class="receta-foto">
                                    <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>">
                                    <h3><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($receta['descripcion'], 0, 100)) . '...'; ?></p>
                                    <div class="acciones-tarjeta">
                                        <a href="receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn-agregar-tarjeta">Ver Receta</a>
                                    </div>
                                    <?php if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] === 'cliente'): ?>
                                    <div class="agregar-carrito-container" style="margin-top:10px;">
                                        <label style="display:block; margin-bottom:5px;">Cantidad de porciones:
                                            <input type="number" class="input-porciones-carrito" min="1" value="1" style="width:100px; font-size:1.2em; margin-left:5px;">
                                        </label>
                                        <button type="button" class="agregar-carrito"
                                            data-tienda-id="<?php echo $ID_TIENDA; ?>"
                                            data-tienda-nombre="<?php echo htmlspecialchars($tienda['razon_social']); ?>"
                                            data-receta-id="<?php echo $receta['id_receta']; ?>"
                                            data-receta-nombre="<?php echo htmlspecialchars($receta['nombre']); ?>"
                                            data-receta-foto="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>"
                                            data-ingredientes='<?php echo json_encode($ingredientes, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                            data-porciones="1"
                                        >Agregar al carrito</button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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

    <script src="js/carrito.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.agregar-carrito-container').forEach(function(container) {
            var input = container.querySelector('.input-porciones-carrito');
            var btn = container.querySelector('.agregar-carrito');
            input.addEventListener('input', function() {
                btn.dataset.porciones = input.value;
            });
        });
    });
    </script>
</body>
</html> 