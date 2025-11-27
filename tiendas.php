<?php
session_start();
require_once 'config.php';

try {
    $conexion = getDBConnection();
    
    // Obtener la localidad del usuario si está logueado
    $localidad_usuario = null;
    if (isset($_SESSION['usuario_id'])) {
        $query_usuario = "SELECT localidad FROM usuario WHERE id = ?";
        $stmt_usuario = mysqli_prepare($conexion, $query_usuario);
        mysqli_stmt_bind_param($stmt_usuario, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt_usuario);
        $result_usuario = mysqli_stmt_get_result($stmt_usuario);
        if ($row_usuario = mysqli_fetch_assoc($result_usuario)) {
            $localidad_usuario = $row_usuario['localidad'];
        }
    }

    // Obtener parámetros de GET para búsqueda y filtrado
    $busqueda = $_GET['busqueda'] ?? '';
    // Si el usuario está logueado, la vista por defecto son las tiendas cercanas
    $categoria = $_GET['categoria'] ?? ($localidad_usuario ? 'cercanas' : 'todas');

    // --- Construcción de la consulta ---
    $query = "SELECT t.id_tienda, t.razon_social, t.descripcion, t.rubro, t.cantidad_ventas, t.calificacion,
                     u.direccion, u.localidad, u.ruta_imagen as ruta_imagen_usuario,
                     cp.provincia, cp.localidad as nombre_localidad, cp.id as id_postal
              FROM tienda t
              JOIN usuario u ON t.id_tienda = u.id
              LEFT JOIN codigo_postal cp ON u.localidad = cp.id";

    $where_clauses = [];
    $params = [];
    $types = '';

    // Añadir filtro de búsqueda
    if (!empty($busqueda)) {
        $where_clauses[] = "(t.razon_social LIKE ? OR t.descripcion LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $types .= "ss";
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(' AND ', $where_clauses);
    }

    // Añadir ordenamiento basado en la categoría
    $order_by_clause = " ORDER BY ";
    switch ($categoria) {
        case 'cercanas':
            if ($localidad_usuario) {
                // El parámetro para la cercanía se añade al final
                $order_by_clause .= "ABS(cp.id - ?)";
            } else {
                $order_by_clause .= "t.calificacion DESC";
            }
            break;
        case 'mejor-calificadas':
            $order_by_clause .= "t.calificacion DESC";
            break;
        case 'record-ventas':
            $order_by_clause .= "t.cantidad_ventas DESC";
            break;
        default: // 'todas'
            $order_by_clause .= "t.id_tienda ASC";
            break;
    }
    
    $query .= $order_by_clause;
    
    // Añadir el parámetro de localidad si es necesario
    if ($categoria === 'cercanas' && $localidad_usuario) {
        $params[] = $localidad_usuario;
        $types .= "i";
    }

    // Ejecutar la consulta
    $stmt = mysqli_prepare($conexion, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . mysqli_error($conexion));
    }
    
    $tiendas = mysqli_fetch_all($result, MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error en tiendas.php: " . $e->getMessage());
    $error = "Error al cargar las tiendas";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tiendas - Vive tu Sabor</title>
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Tiendas</span></h1>
        </div>
    </header>

    <?php if (isset($_SESSION['nombre'])): ?>
        <div class="mensaje-bienvenida">
            ¡Bienvenido <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
        </div>
    <?php endif; ?>

    <main class="contenido-principal">
        <div class="tiendas-container">
            <div class="carousel">
                <form action="tiendas.php" method="GET" class="carousel-item search-form">
                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
                    <input type="text" name="busqueda" placeholder="Buscar tiendas..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <?php if (!empty($busqueda)): ?>
                        <button type="button" class="btn-limpiar" onclick="limpiarBusquedaTiendas()">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    <?php endif; ?>
                    <button type="submit" class="btn-buscar"><i class="fas fa-search"></i></button>
                </form>
                <a href="?categoria=todas&busqueda=<?php echo urlencode($busqueda); ?>" class="carousel-item <?php if($categoria == 'todas') echo 'active'; ?>">Todas las Tiendas</a>
                <a href="?categoria=cercanas&busqueda=<?php echo urlencode($busqueda); ?>" class="carousel-item <?php if($categoria == 'cercanas') echo 'active'; ?>">Tiendas Cercanas</a>
                <a href="?categoria=mejor-calificadas&busqueda=<?php echo urlencode($busqueda); ?>" class="carousel-item <?php if($categoria == 'mejor-calificadas') echo 'active'; ?>">Mejor Calificadas</a>
                <a href="?categoria=record-ventas&busqueda=<?php echo urlencode($busqueda); ?>" class="carousel-item <?php if($categoria == 'record-ventas') echo 'active'; ?>">Record en Ventas</a>
            </div>

            <div class="grid-recetas" id="tiendas-grid">
                <?php if (isset($error)): ?>
                    <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (empty($tiendas)): ?>
                    <div class="no-resultados">No se encontraron tiendas que coincidan con tu búsqueda.</div>
                <?php else: ?>
                    <?php foreach ($tiendas as $tienda): ?>
                        <div class="receta-foto tienda-card-grid">
                            <?php 
                            $ruta_imagen = !empty($tienda['ruta_imagen_usuario']) ? $tienda['ruta_imagen_usuario'] : 'img/default-store.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($ruta_imagen); ?>" 
                                 alt="Logo de la tienda" 
                                 class="tienda-imagen"
                                 onerror="this.src='img/default-store.png'">
                            <div class="tienda-info">
                                <h3><?php echo htmlspecialchars($tienda['razon_social']); ?></h3>
                                <p class="tienda-rubro"><?php echo htmlspecialchars($tienda['rubro']); ?></p>
                                <div class="tienda-stats">
                                    <span><i class="fas fa-star"></i> <?php echo number_format($tienda['calificacion'] ?? 0, 1); ?></span>
                                    <span><i class="fas fa-shopping-cart"></i> <?php echo $tienda['cantidad_ventas'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="acciones-tarjeta">
                                <a href="tienda.php?id=<?php echo $tienda['id_tienda']; ?>" class="btn-agregar-tarjeta">Ver Tienda</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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

    <div class="login-widget-overlay">
        <div class="login-widget">
            <h3>Inicia sesión para ver las tiendas más cercanas a ti.</h3>
            <form class="formulario" method="POST" action="login.php?redirect=tiendas.php?categoria=cercanas">
                <input type="email" name="email" placeholder="Correo electrónico" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Iniciar sesión</button>
            </form>
            <p class="registro-link">
                ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const linkCercanas = document.querySelector('a.carousel-item[href*="categoria=cercanas"]');
            const loginWidget = document.querySelector('.login-widget-overlay');

            <?php if (!isset($_SESSION['usuario_id'])): ?>
            if(linkCercanas) {
                linkCercanas.addEventListener('click', function(e) {
                    // Si el usuario no está logueado, previene la navegación y muestra el popup
                    e.preventDefault();
                    loginWidget.style.display = 'flex';
                });
            }
            <?php endif; ?>

            // Cerrar el widget al hacer clic fuera de él
            if(loginWidget) {
                loginWidget.addEventListener('click', function(e) {
                    if (e.target === loginWidget) {
                        loginWidget.style.display = 'none';
                    }
                });
            }
        });

        function limpiarBusquedaTiendas() {
            window.location.href = 'tiendas.php?categoria=<?php echo urlencode($categoria); ?>';
        }
    </script>
</body>
</html> 