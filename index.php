<?php
session_start();
require_once 'config.php';

// Obtener recetas destacadas
$query = "SELECT r.*, t.razon_social, 
          r.veces_pedida,
          tr.cantidad_ventas,
          tr.calificacion
          FROM receta r
          LEFT JOIN tienda t ON r.tienda_id = t.id_tienda
          LEFT JOIN tienda_receta tr ON r.id_receta = tr.receta_id AND t.id_tienda = tr.tienda_id
          ORDER BY r.veces_pedida DESC
          LIMIT 4";

$recetasDestacadas = getMultipleResults($query);

// Obtener tiendas destacadas
$queryTiendas = "SELECT t.*, 
                 COUNT(DISTINCT r.id_receta) as total_recetas
                 FROM tienda t
                 LEFT JOIN receta r ON t.id_tienda = r.tienda_id
                 GROUP BY t.id_tienda
                 ORDER BY t.cantidad_ventas DESC
                 LIMIT 4";

$tiendasDestacadas = getMultipleResults($queryTiendas);

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

    <main class="contenido-principal presentacion-vive-tu-sabor">
        <section class="bienvenida-marca">
            <h2>Bienvenido a Vive tu Sabor - Recetas</h2>
            <p>
                <strong>Vive tu Sabor</strong> es la plataforma donde podés descubrir, comprar y preparar recetas deliciosas con ingredientes frescos de tiendas locales. Nuestra misión es acercarte a la mejor experiencia culinaria, conectando cocineros, comercios y amantes de la buena comida.
            </p>
        </section>

        <section class="funcionalidades">
            <h3>¿Qué podés hacer en Vive tu Sabor?</h3>
            <div class="funcionalidades-grid">
                <div class="funcionalidades-col card-flip" tabindex="0">
                    <div class="card-flip-inner">
                        <div class="card-flip-front">
                            <img src="img/cliente2.jpg" alt="Cliente" class="card-flip-img">
                            <div class="card-flip-label">Cliente</div>
                        </div>
                        <div class="card-flip-back">
                            <h4>Como Cliente</h4>
                            <ul>
                                <li>Buscar recetas, productos y tiendas locales.</li>
                                <li>Ver recetas detalladas con fotos, ingredientes y pasos.</li>
                                <li>Agregar productos al carrito y realizar pedidos a tiendas cercanas.</li>
                                <li>Recibir notificaciones sobre el estado de tus pedidos.</li>
                                <li>Calificar y dejar comentarios sobre tus experiencias.</li>
                            </ul>
                        </div>
                            </div>
                                </div>
                <div class="funcionalidades-col card-flip" tabindex="0">
                    <div class="card-flip-inner">
                        <div class="card-flip-front">
                            <img src="img/tienda2.jpg" alt="Tienda" class="card-flip-img">
                            <div class="card-flip-label">Tienda</div>
                            </div>
                        <div class="card-flip-back">
                            <h4>Como Tienda</h4>
                            <ul>
                                <li>Administrar tu catálogo de productos e ingredientes.</li>
                                <li>Crear y publicar recetas para que los clientes las descubran.</li>
                                <li>Recibir y gestionar pedidos de los clientes.</li>
                                <li>Actualizar el estado de los pedidos y gestionar el stock.</li>
                                <li>Recibir calificaciones y comentarios para mejorar tu reputación.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                </div>
            </section>

        <section class="como-funciona">
            <h3>¿Cómo funciona el sitio?</h3>
            <div class="como-funciona-grid">
                <div class="como-funciona-col card-flip" tabindex="0">
                    <div class="card-flip-inner">
                        <div class="card-flip-front">
                            <img src="img/cliente1.jpg" alt="Cliente" class="card-flip-img">
                            <div class="card-flip-label">Cliente</div>
                        </div>
                        <div class="card-flip-back">
                            <h4>Como Cliente</h4>
                            <ol>
                                <li><strong>Explorá recetas:</strong> Navegá por nuestro catálogo de recetas destacadas y populares. Cada receta incluye ingredientes, pasos detallados y la tienda que la ofrece.</li>
                                <li><strong>Agregá productos al carrito:</strong> Desde cada receta, podés añadir los ingredientes necesarios a tu carrito de compras, eligiendo la cantidad y la tienda de tu preferencia.</li>
                                <li><strong>Comprá fácil y seguro:</strong> Cuando tu carrito esté listo, hacé tu pedido y pagá de forma segura a través de Mercado Pago.</li>
                                <li><strong>Gestioná tu perfil:</strong> Podés ver tus pedidos y recetas favoritas, y calificar tus experiencias.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="como-funciona-col card-flip" tabindex="0">
                    <div class="card-flip-inner">
                        <div class="card-flip-front">
                            <img src="img/tienda1.jpg" alt="Tienda" class="card-flip-img">
                            <div class="card-flip-label">Tienda</div>
                        </div>
                        <div class="card-flip-back">
                            <h4>Como Tienda</h4>
                            <ol>
                                <li><strong>Registrate como tienda:</strong> Creá tu cuenta de tienda para empezar a ofrecer tus productos y recetas.</li>
                                <li><strong>Publicá productos y recetas:</strong> Administrá tu catálogo de productos e ingredientes, y creá recetas para que los clientes las descubran.</li>
                                <li><strong>Recibí y gestioná pedidos:</strong> Los clientes podrán comprar tus productos y recetas. Gestioná los pedidos y actualizá el estado de cada uno.</li>
                                <li><strong>Mejorá tu reputación:</strong> Recibí calificaciones y comentarios de los clientes para crecer en la comunidad.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                    </div>
                </section>
        <script>
        document.querySelectorAll('.card-flip').forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('flipped');
            });
            card.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    this.classList.toggle('flipped');
                }
            });
        });
        </script>

        <section class="llamado-accion">
            <h3>¡Empezá a vivir tu sabor!</h3>
            <p>
                Registrate gratis, explorá nuevas recetas y disfrutá de la mejor experiencia gastronómica local. <a href="registro.php" class="btn">Crear cuenta</a>
                    </p>
                </section>
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