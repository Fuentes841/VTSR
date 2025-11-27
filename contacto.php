<?php
session_start();
require_once 'config.php';

// Código PHP al principio del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer-6.10.0/src/Exception.php';
require 'phpmailer/PHPMailer-6.10.0/src/PHPMailer.php';
require 'phpmailer/PHPMailer-6.10.0/src/SMTP.php';

$mensajeEnvio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $comentario = $_POST['comentario'] ?? '';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lazzarinofacu@gmail.com';  // tu cuenta
        $mail->Password = 'wygv uzfn pafa fjsx';          // tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('tu_correo@gmail.com', 'Tu sitio');
        $mail->addAddress('lazzarinofacu@gmail.com');  // destinatario final
        $mail->addReplyTo($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = "Contacto desde el sitio: $nombre";
        $mail->Body = "
            <strong>Nombre:</strong> $nombre<br>
            <strong>Email:</strong> $email<br>
            <strong>Localidad:</strong> $localidad<br>
            <strong>Comentario:</strong><br>" . nl2br(htmlspecialchars($comentario));

        $mail->send();

        $conexion = mysqli_connect("localhost", "root", "", "prueba2") or die('No se pudo conectar al servidor');
        $consulta = mysqli_query($conexion, "INSERT INTO contactos (nombre,email,localidad,comentario) VALUES ('$nombre','$email','$localidad','$comentario')");

        $mensajeEnvio = '<div class="alert alert-success">El correo se envió correctamente. Gracias por tu comentario.</div>';
    } catch (Exception $e) {
        $mensajeEnvio = '<div class="alert alert-error">Error al enviar el correo: ' . $mail->ErrorInfo . '</div>';
    }
}
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
                 <?php if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] === 'cliente'): ?>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="recetas.php">Recetas</a></li>
                    <li><a href="tiendas.php">Tiendas</a></li>
                <?php else: ?>
                    <li><a href="tienda.php">Mi tienda</a></li>
                    <li><a href="pedidos.php">Pedidos</a></li>
                <?php endif; ?>
                <?php if (estaLogueado()): ?>
                    <li class="perfil-menu">
                        <i class="fa-solid fa-user perfil-icono"></i>
                        <div class="perfil-dropdown">
                            <a href="perfil.php">Mi Perfil</a>
                            <a href="pedidos.php">Mis pedidos</a>
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
</header>

    <main class="contenido-principal">
        <div class="formulario-container">
           
	<form class = "formulario" id="formcontacto" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="campo-formulario">
        <label>Nombre
            <input type="text" name="nombre" required/>	
        </label>
         </div>
         <div class="campo-formulario">
        <label>Email
            <input type="email" name="email"required />
        </label>
         </div>
         <div class="campo-formulario">
        <label>Localidad
            <input type="text" name="localidad" />
        </label>
         </div>
         <div class="campo-formulario">
        <label>Comentario
            <textarea name="comentario" class = "comentario-textarea"></textarea>
        </label>
         </div>
        <div class="campo-formulario">
        <input type="submit" class="btn-submit" value="Enviar" />
        </div>
         <h2 class = "mensaje.success"><?php
                if (isset($mensajeEnvio)) {
                echo $mensajeEnvio;
                }
            ?></h2>
    </form>	
         </div>
    </main>

 <footer class="footer">
        
        <div class="footer-contenido">
            <?php if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] === 'cliente'): ?>
            <div class="footer-enlaces">
                <a href="index.php">Inicio</a>
                <a href="recetas.php">Recetas</a>
                <a href="contacto.php">Contacto</a>
                <a href="privacidad.php">Privacidad</a>
            </div>
            <?php else: ?>
            <div class="footer-enlaces">
                <a href="tienda.php">Mi tienda</a>
                <a href="recetas.php">Pedidos</a>
                <a href="contacto.php">Contacto</a>
                <a href="privacidad.php">Privacidad</a>
            </div>
            <?php endif; ?>
            <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
</footer>

</body>
</html>