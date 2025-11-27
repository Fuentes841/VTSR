<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        try {
            $conexion = getDBConnection();
            
            // Verificar credenciales y determinar tipo de usuario
            $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.password, u.ruta_imagen,
                     CASE 
                         WHEN c.id_cliente IS NOT NULL THEN 'cliente'
                         WHEN t.id_tienda IS NOT NULL THEN 'tienda'
                         ELSE NULL 
                     END as tipo_usuario
                     FROM usuario u
                     LEFT JOIN cliente c ON u.id = c.id_cliente
                     LEFT JOIN tienda t ON u.id = t.id_tienda
                     WHERE u.email = ?";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if ($usuario = mysqli_fetch_assoc($resultado)) {
                // Verificar la contraseña
                if (password_verify($password, $usuario['password'])) {
                    // Iniciar sesión
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['apellido'] = $usuario['apellido'];
                    $_SESSION['email'] = $usuario['email'];
                    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                    $_SESSION['ruta_imagen'] = $usuario['ruta_imagen'];
                    
                    // Redirigir según el tipo de usuario
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Credenciales inválidas';
                }
            } else {
                $error = 'Credenciales inválidas';
            }
            
            mysqli_close($conexion);
        } catch (Exception $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión - Vive tu Sabor</title>
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Iniciar Sesión</span></h1>
        </div>
    </header>

    <main class="contenido-principal">
        <div class="formulario-container">
            <h2>Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="formulario" method="POST" action="login.php">
                <div class="campo-formulario">
                    <label for="email">Correo electrónico:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="campo-formulario">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit">Iniciar Sesión</button>
            </form>

            <p class="registro-link">
                ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
            </p>
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