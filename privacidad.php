<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - Vive tu Sabor</title>
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
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu Sabor</span> <span class="bold">Privacidad</span></h1>
        </div>
    </header>
    <main class="contenido-principal">
        <div class="formulario-container" style="max-width: 800px; margin: 2em auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2em;">
            <h2 style="text-align:center; margin-bottom: 1em;">Política de Privacidad</h2>
            <p>En <strong>Vive tu Sabor</strong> nos comprometemos a proteger la privacidad de nuestros usuarios y a garantizar la seguridad de sus datos personales. Esta política explica cómo recopilamos, usamos y protegemos tu información.</p>
            <h3>1. Información que recopilamos</h3>
            <ul>
                <li><strong>Datos de registro:</strong> nombre, apellido, correo electrónico, teléfono, dirección y localidad.</li>
                <li><strong>Datos de uso:</strong> recetas creadas, productos agregados, compras y navegación en el sitio.</li>
                <li><strong>Información opcional:</strong> imagen de perfil, preferencias y comentarios.</li>
            </ul>
            <h3>2. Uso de la información</h3>
            <ul>
                <li>Gestionar tu cuenta y brindarte acceso a las funcionalidades del sitio.</li>
                <li>Mejorar la experiencia de usuario y personalizar el contenido.</li>
                <li>Procesar pedidos y facilitar la comunicación entre clientes y tiendas.</li>
                <li>Enviar notificaciones importantes sobre tu cuenta o servicios.</li>
            </ul>
            <h3>3. Protección de datos</h3>
            <ul>
                <li>Tus datos están protegidos mediante medidas de seguridad técnicas y organizativas.</li>
                <li>No compartimos tu información personal con terceros, salvo obligación legal o para la prestación de servicios esenciales (por ejemplo, procesadores de pago).</li>
            </ul>
            <h3>4. Derechos del usuario</h3>
            <ul>
                <li>Puedes acceder, modificar o eliminar tus datos personales desde tu perfil o contactándonos.</li>
                <li>Puedes solicitar la baja de tu cuenta en cualquier momento.</li>
            </ul>
            <h3>5. Cookies</h3>
            <p>Utilizamos cookies para mejorar la navegación y analizar el uso del sitio. Puedes gestionar tus preferencias de cookies desde la configuración de tu navegador.</p>
            <h3>6. Cambios en la política</h3>
            <p>Nos reservamos el derecho de actualizar esta política de privacidad. Notificaremos cualquier cambio relevante a través del sitio.</p>
            <p style="margin-top:2em;">Si tienes dudas o consultas sobre nuestra política de privacidad, puedes escribirnos a <a href="mailto:soporte@vivetusabor.com">soporte@vivetusabor.com</a>.</p>
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
</body>
</html> 