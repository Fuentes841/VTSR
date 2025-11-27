<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono) || empty($direccion) || empty($localidad) || empty($password) || empty($confirm_password) || empty($tipo_usuario)) {
        $error = 'Por favor complete todos los campos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $conexion = getDBConnection();
            
            // Verificar si el email ya existe
            $query = "SELECT id FROM usuario WHERE email = ?";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'El correo electrónico ya está registrado';
            } else {
                // Iniciar transacción
                mysqli_begin_transaction($conexion);
                
                try {
                    // Crear hash de la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertar usuario
                    $query = "INSERT INTO usuario (nombre, apellido, email, telefono, direccion, localidad, password) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conexion, $query);
                    mysqli_stmt_bind_param($stmt, "sssssss", 
                        $nombre, 
                        $apellido, 
                        $email, 
                        $telefono,
                        $direccion,
                        $localidad,
                        $password_hash
                    );
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error al insertar usuario: " . mysqli_stmt_error($stmt));
                    }
                    
                    $usuario_id = mysqli_insert_id($conexion);
                    
                    // Insertar en la tabla correspondiente según el tipo de usuario
                    if ($tipo_usuario === 'cliente') {
                        $query = "INSERT INTO cliente (id_cliente) VALUES (?)";
                        $stmt = mysqli_prepare($conexion, $query);
                        mysqli_stmt_bind_param($stmt, "i", $usuario_id);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error al insertar cliente: " . mysqli_stmt_error($stmt));
                        }
                    } else {
                        $query = "INSERT INTO tienda (id_tienda, razon_social, descripcion, rubro) VALUES (?, ?, ?, ?)";
                        $razon_social = $nombre . ' ' . $apellido;
                        $descripcion = 'Nueva tienda registrada';
                        $rubro = 'General';
                        
                        $stmt = mysqli_prepare($conexion, $query);
                        mysqli_stmt_bind_param($stmt, "isss", $usuario_id, $razon_social, $descripcion, $rubro);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error al insertar tienda: " . mysqli_stmt_error($stmt));
                        }
                    }
                    
                    // Si todo salió bien, confirmar la transacción
                    mysqli_commit($conexion);
                    $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
                    
                } catch (Exception $e) {
                    // Si algo salió mal, revertir la transacción
                    mysqli_rollback($conexion);
                    throw $e;
                }
            }
            
            mysqli_close($conexion);
            
        } catch (Exception $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
            if (isset($conexion)) {
                mysqli_close($conexion);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registro - Vive tu Sabor</title>
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Registro</span></h1>
        </div>
    </header>

    <main class="contenido-principal">
        <div class="formulario-container">
            <h2>Registro de Usuario</h2>
            
            <?php if ($error): ?>
                <div class="mensaje error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mensaje success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($debug_info)): ?>
                <div class="debug-info" style="background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">
                    <h4>Información de depuración:</h4>
                    <pre><?php print_r($debug_info); ?></pre>
                </div>
            <?php endif; ?>

            <form class="formulario" method="POST" action="registro.php">
                <div class="campo-formulario">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="campo-formulario">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>

                <div class="campo-formulario">
                    <label for="email">Correo electrónico:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="campo-formulario">
                    <label for="telefono">Teléfono:</label>
                    <input type="tel" id="telefono" name="telefono" required>
                </div>

                <div class="campo-formulario">
                    <label for="direccion">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" required>
                </div>

                <div class="campo-formulario">
                    <label for="localidad">(Código Postal) - Provincia - Localidad: </label>
                    <select id="localidad" name="localidad" required>
                        <option value="">Seleccione un código postal</option>
                        <?php
                        try {
                            $conexion = getDBConnection();
                            $query = "SELECT id, id_postal, provincia, localidad FROM codigo_postal ORDER BY provincia ASC, localidad ASC";
                            $resultado = mysqli_query($conexion, $query);
                            if ($resultado) {
                                $provincia_actual = '';
                                while ($row = mysqli_fetch_assoc($resultado)) {
                                    if ($provincia_actual != $row['provincia']) {
                                        if ($provincia_actual != '') {
                                            echo "</optgroup>";
                                        }
                                        $provincia_actual = $row['provincia'];
                                        echo "<optgroup label='" . htmlspecialchars($provincia_actual) . "'>";
                                    }
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . 
                                         "(" . htmlspecialchars($row['id_postal']) . ") - " . 
                                         htmlspecialchars($row['provincia']) . " - " . 
                                         htmlspecialchars($row['localidad']) . 
                                         "</option>";
                                }
                                if ($provincia_actual != '') {
                                    echo "</optgroup>";
                                }
                            }
                        } catch (Exception $e) {
                            echo "<!-- Error al cargar códigos postales: " . htmlspecialchars($e->getMessage()) . " -->";
                        }
                        if (isset($conexion)) {
                            mysqli_close($conexion);
                        }
                        ?>
                    </select>
                </div>

                <div class="campo-formulario">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <div class="campo-formulario">
                    <label for="confirm_password">Confirmar contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <div class="campo-formulario">
                    <label for="tipo_usuario">Tipo de usuario:</label>
                    <select id="tipo_usuario" name="tipo_usuario" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="cliente">Cliente</option>
                        <option value="tienda">Tienda</option>
                    </select>
                </div>

                <div class="campo-formulario">
                    <button type="submit" class="btn-submit">Registrarse</button>
                </div>
            </form>

            <p class="registro-link">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
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