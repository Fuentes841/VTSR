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
        <!-- El título H1 será específico de cada página que incluya este header -->
    </div>
</header>
<script>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script> 