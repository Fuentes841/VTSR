<?php
session_start();
include 'config.php';

// Obtener el criterio de ordenamiento
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'alfabetico';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

try {
    // Si el filtro es 'sin_tienda', mostrar solo recetas que no están en el menú de ninguna tienda
    if ($orden === 'sin_tienda') {
        $sql = "SELECT id_receta, nombre, descripcion, ruta_imagen, veces_pedida FROM receta WHERE id_receta NOT IN (SELECT receta_id FROM tienda_receta)";
        if (!empty($busqueda)) {
            $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
            $busqueda_param = "%$busqueda%";
            $params = [$busqueda_param, $busqueda_param];
        } else {
            $params = [];
        }
        $resultado = getMultipleResults($sql, $params);
        if ($resultado === false) {
            throw new Exception("Error al obtener las recetas");
        }
    } else {
        // Construir la consulta base
        $sql = "SELECT id_receta, nombre, descripcion, ruta_imagen, veces_pedida FROM receta";

        // Agregar búsqueda si existe
        if (!empty($busqueda)) {
            $sql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
            $busqueda_param = "%$busqueda%";
        }

        // Agregar ordenamiento
        switch ($orden) {
            case 'alfabetico':
                $sql .= " ORDER BY nombre ASC";
                break;
            case 'pedidas':
                $sql .= " ORDER BY veces_pedida DESC";
                break;
            default:
                $sql .= " ORDER BY nombre ASC";
        }

        // Ejecutar la consulta usando las funciones de config.php
        $params = [];
        if (!empty($busqueda)) {
            $params = [$busqueda_param, $busqueda_param];
        }
        $resultado = getMultipleResults($sql, $params);
        if ($resultado === false) {
            throw new Exception("Error al obtener las recetas");
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $resultado = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recetas - Vive tu Sabor</title>
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
                                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
                                    <a href="panel_admin.php">Panel de Administración</a>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda'): ?>
                                    <a href="mis_productos.php">Mis Productos</a>
                                <?php endif; ?>
                                <a href="pedidos.php">Mis Pedidos</a>
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Recetas</span></h1>
        </div>
    </header>

    <?php if (isset($_SESSION['nombre'])): ?>
        <div class="mensaje-bienvenida">
            ¡Bienvenido <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
        </div>
    <?php endif; ?>

    <div class="contenido-principal">
        <div class="carousel-container">
            <div class="carousel-wrapper">
                <div class="carousel">
                    <form action="" method="GET" class="carousel-item search-form">
                        <input type="text" name="busqueda" placeholder="Buscar recetas..." value="<?php echo htmlspecialchars($busqueda); ?>">
                        <?php if (!empty($busqueda)): ?>
                            <button type="button" class="btn-limpiar" onclick="limpiarBusqueda()">
                                <i class="fa-solid fa-circle-xmark fa-2x"></i>
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn-buscar">
                            <i class="fa-solid fa-magnifying-glass fa-2x"></i>
                        </button>
                    </form>
                    <a href="?orden=alfabetico<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="carousel-item <?php echo $orden === 'alfabetico' ? 'active' : ''; ?>">
                        <i class="fas fa-sort-alpha-down"></i>
                        <span>Todas las recetas</span>
                    </a>
                    <a href="?orden=pedidas<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="carousel-item <?php echo $orden === 'pedidas' ? 'active' : ''; ?>">
                        <i class="fas fa-fire"></i>
                        <span>Más pedidas</span>
                    </a>
                    <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda'): ?>
                    <a href="?orden=sin_tienda<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="carousel-item <?php echo $orden === 'sin_tienda' ? 'active' : ''; ?>">
                        <i class="fas fa-eye-slash"></i>
                        <span>Propuestas de los clientes</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-mensaje">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid-recetas">
            <?php if (empty($resultado)): ?>
                <div class="no-resultados">
                    No se encontraron recetas.
                </div>
            <?php else: ?>
                <?php foreach ($resultado as $receta): ?>
                    <div class="receta-foto">
                        <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>">
                        <h3><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($receta['descripcion']); ?></p>
                        <div class="info-receta">
                            <div class="ventas">
                                <i class="fas fa-shopping-cart"></i>
                                <span><?php echo $receta['veces_pedida']; ?> pedidos</span>
                            </div>
                        </div>
                        <div class="control-carrito-tarjeta">
                            <div class="acciones-tarjeta">
                                <a href="receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn-agregar-tarjeta">
                                    Ver receta
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

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
        // Funcionalidad para el menú de perfil
        document.addEventListener('DOMContentLoaded', function() {
            const perfilMenu = document.querySelector('.perfil-menu');
            const perfil = document.querySelector('.perfil');

            perfil.addEventListener('mouseenter', function() {
                perfilMenu.querySelector('.perfil-dropdown').style.display = 'block';
            });

            perfil.addEventListener('mouseleave', function() {
                perfilMenu.querySelector('.perfil-dropdown').style.display = 'none';
            });
        });

        // Función para limpiar la búsqueda
        function limpiarBusqueda() {
            const urlParams = new URLSearchParams(window.location.search);
            const orden = urlParams.get('orden') || 'alfabetico';
            window.location.href = '?orden=' + orden;
        }
    </script>
</body>
</html> 