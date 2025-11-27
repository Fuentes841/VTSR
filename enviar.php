<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer-6.10.0/src/Exception.php';
require 'phpmailer/PHPMailer-6.10.0/src/PHPMailer.php';
require 'phpmailer/PHPMailer-6.10.0/src/SMTP.php';

// Capturar datos del formulario
$nombre     = $_POST['nombre'];
$email      = $_POST['email'];
$localidad  = $_POST['localidad'];
$comentario = $_POST["comentario"];

// Configuración de PHPMailer
$mail = new PHPMailer(true);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>
     <header class="header">
        <div class="top-bar">
            <nav class="nav">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="recetas.php">Recetas</a></li>
                    <li><a href="tiendas.php">Tiendas</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="contenido-principal">
<?php
try {
    // Servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lazzarinofacu@gmail.com';     // TU correo de Gmail
    $mail->Password   = 'wygv uzfn pafa fjsx';        // Contraseña de aplicación (no tu contraseña de Gmail)
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Desde y hacia (es válido enviarlo a vos mismo)
    $mail->setFrom('lazzarinofacu@gmail.com', 'Formulario Web');
    $mail->addAddress('lazzarinofacu@gmail.com'); // O cualquier otro destinatario

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje desde el formulario de contacto';
    $mail->Body    = "
        <strong>Nombre:</strong> $nombre<br>
        <strong>Email del usuario:</strong> $email<br>
        <strong>Localidad:</strong> $localidad<br>
        <strong>Comentario:</strong><br>$comentario
    ";

    $mail->send();
    echo "📨 El correo se envió correctamente.";
} catch (Exception $e) {
    echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
}

// Insertar en base de datos (esto no cambia)
$conexion = mysqli_connect("localhost", "root", "", "prueba2") or die("Error en la conexión");

$consulta = mysqli_query($conexion, "INSERT INTO contactos (nombre, email, localidad, comentario)
VALUES ('$nombre', '$email', '$localidad', '$comentario')") or die(mysqli_error($conexion));
?>
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