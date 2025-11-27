<?php
session_start();
require_once 'config.php';
$buscar = $_POST['buscar'];
$recetas_encontradas = getMultipleResults("SELECT r.*, tr.cantidad_ventas, tr.calificacion 
                                         FROM receta r 
                                         JOIN tienda_receta tr ON r.id_receta = tr.receta_id 
                                         WHERE r.nombre LIKE '%$buscar%'");

$productos_encontrados = getMultipleResults("SELECT p.*, i.nombre as nombre_ingrediente, i.unidad_medida 
                                           FROM producto p 
                                           JOIN ingrediente i ON p.ingrediente_id = i.id_ingrediente 
                                           WHERE i.nombre LIKE '%$buscar%' OR p.marca LIKE '%$buscar%'");

$tiendas_encontradas = getMultipleResults("SELECT * FROM tienda WHERE razon_social LIKE '%$buscar%'");

// Obtener recetas destacadas
$query = "SELECT r.*, t.razon_social, 
          r.veces_pedida,
          tr.cantidad_ventas,
          tr.calificacion
          FROM receta r
          JOIN tienda t ON r.tienda_id = t.id_tienda
          JOIN tienda_receta tr ON r.id_receta = tr.receta_id AND t.id_tienda = tr.tienda_id
          ORDER BY tr.cantidad_ventas DESC, tr.calificacion DESC
          LIMIT 4";

$recetasDestacadas = getMultipleResults($query);

// Obtener recetas populares
$recetasPopulares = getMultipleResults($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vive tu Sabor - Recetas</title>
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
                    <form action="resultados_buscar.php" method="post">
                        <input type="search" name="buscar" placeholder="Buscar receta, tienda o producto" />
                        <button type="submit" aria-label="Buscar" class="btn-lupa">🔍</button>
                    </label>
                </form>
                </ul>

            </nav>
        </div>
     
    </header>

    <?php if (isset($_SESSION['nombre'])): ?>
        <div class="mensaje-bienvenida">
            ¡Bienvenido <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
        </div>
    <?php endif; ?>

    <main class="contenido-principal">
        <div class="posicion">
            <section class="recetas">
                <h2>Resultados de su búsqueda</h2>
                <h3>Recetas</h3>
                 <?php if (empty($recetas_encontradas)): ?>
                        <p>No se encontraron recetas.</p>
                <?php else: ?>
                <div class="grid-busqueda">
                    <?php foreach ($recetas_encontradas as $receta): ?>
                    <article class="receta-foto" data-kit-id="<?php echo htmlspecialchars($receta['id_receta']); ?>">
                        <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($receta['nombre']); ?>">
                        <h3><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($receta['descripcion']); ?></p>
                        <a href="receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn">Ver receta</a>
                        
                        <div class="control-carrito-tarjeta">
                            <div class="info-precio-tarjeta">
                                <span class="etiqueta-precio">Precio Kit:</span>
                                <span class="valor-precio">
                                    <?php
                                    $query = "SELECT SUM(p.precio * ir.cantidad) as precio_total
                                             FROM ingrediente_por_receta ir
                                             JOIN ingrediente i ON ir.ingrediente_id = i.id_ingrediente
                                             JOIN producto p ON i.id_ingrediente = p.ingrediente_id
                                             WHERE ir.receta_id = ? AND p.tienda_id = ?";
                                    $precio = getSingleResult($query, [$receta['id_receta'], $receta['tienda_id']]);
                                    echo '$' . number_format($precio['precio_total'], 2);
                                    ?>
                                </span>
                            </div>
                            <div class="acciones-tarjeta">
                                <div class="control-cantidad-tarjeta">
                                    <button type="button" class="btn-cantidad-tarjeta menos" aria-label="Disminuir cantidad">-</button>
                                    <input type="number" class="input-cantidad-tarjeta" value="1" min="1" aria-label="Cantidad">
                                    <button type="button" class="btn-cantidad-tarjeta mas" aria-label="Aumentar cantidad">+</button>
                                </div>
                                <button type="button" class="btn-agregar-tarjeta">Comprar ingredientes</button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                    
                </div>


                    <br><br>
                    <h3>Tiendas</h3>
                    <?php if (empty($tiendas_encontradas)): ?>
                        <p>No se encontraron tiendas.</p>
                    <?php else: ?>
                <div class="grid-busqueda">
                    <?php foreach ($tiendas_encontradas as $tienda): ?>
                        <div class="tienda-card" data-categoria="todas">
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
                                <p class="tienda-direccion">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($tienda['direccion']); ?><br>
                                    <?php echo htmlspecialchars($tienda['nombre_localidad'] . ', ' . $tienda['provincia']); ?>
                                </p>
                                <div class="tienda-stats">
                                    <span><i class="fas fa-star"></i> <?php echo number_format($tienda['calificacion'], 1); ?></span>
                                    <span><i class="fas fa-shopping-cart"></i> <?php echo $tienda['cantidad_ventas']; ?> ventas</span>
                                </div>
                                <a href="tienda.php?id=<?php echo $tienda['id_tienda']; ?>" class="btn-ver-tienda">Ver Tienda</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                </div>
                    
                    <br><br>
                    <h3>Productos</h3>
                    <?php if (empty($productos_encontrados)): ?>
                        <p>No se encontraron productos.</p>
                    <?php else: ?>
                <div class="grid-busqueda">
                    <?php foreach ($productos_encontrados as $producto): ?>
                    <article class="receta-foto" data-kit-id="<?php echo htmlspecialchars($producto['id_producto']); ?>">
                        <img src="<?php echo htmlspecialchars($producto['ruta_imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre_ingrediente']); ?>">
                        <h3><?php echo htmlspecialchars($producto['nombre_ingrediente']); ?></h3>
                        <p><?php echo htmlspecialchars($producto['marca']); ?> - <?php echo htmlspecialchars($producto['peso_unidad']); ?> <?php echo htmlspecialchars($producto['unidad_medida']); ?></p>
                        
                        <div class="control-carrito-tarjeta">
                            <div class="info-precio-tarjeta">
                                <span class="etiqueta-precio">Precio:</span>
                                <span class="valor-precio">
                                    $<?php echo number_format($producto['precio'], 2); ?>
                                </span>
                            </div>
                            <div class="acciones-tarjeta">
                                <div class="control-cantidad-tarjeta">
                                    <button type="button" class="btn-cantidad-tarjeta menos" aria-label="Disminuir cantidad">-</button>
                                    <input type="number" class="input-cantidad-tarjeta" value="1" min="1" aria-label="Cantidad">
                                    <button type="button" class="btn-cantidad-tarjeta mas" aria-label="Aumentar cantidad">+</button>
                                </div>
                                <button type="button" class="btn-agregar-tarjeta">Comprar ingredientes</button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                
            </section>

           
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

    <script src="js/carrito.js"></script>
</body>
</html> 