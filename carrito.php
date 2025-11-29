<?php
// carrito.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de Compras</title>
  <link rel="stylesheet" href="css/estilos.css">
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
          <?php if (isset($_SESSION['usuario_id'])): ?>
            <li class="perfil-menu">
              <i class="fa-solid fa-user perfil-icono"></i>
              <div class="perfil-dropdown">
                <a href="perfil.php">Mi Perfil</a>
                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
                  <a href="panel_admin.php">Panel de Administración</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda'): ?>
                  <a href="mis_productos.php">Mis productos</a>
                  <a href="pedidos.php">Mis pedidos</a>
                <?php elseif (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'cliente'): ?>
                  <a href="pedidos.php">Mis pedidos</a>
                <?php endif; ?>
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
      <h1><span class="light">Vive tu sabor</span> <span class="bold">Carrito</span></h1>
    </div>
  </header>
  <main>
    <div id="carrito-contenedor"></div>
    <div class="acciones-carrito-container">
      <button id="agregar-receta" class="btn-accion-carrito">Agregar receta</button>
      <button id="realizar-pedido" class="btn-accion-carrito">Realizar pedido</button>
    </div>
  </main>
  <footer class="footer">
    <div class="footer-contenido">
      <div class="footer-enlaces">
        <a href="index.php">Inicio</a>
        <a href="recetas.php">Recetas</a>
        <a href="tiendas.php">Tiendas</a>
        <a href="contacto.php">Contacto</a>
      </div>
      <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
    </div>
  </footer>
  <script src="js/carrito.js"></script>
  <script>
    window.usuarioLogueado = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>
  <script src="js/carrito_vista.js"></script>
</body>
</html> 