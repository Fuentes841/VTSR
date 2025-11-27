<?php
require_once 'config.php';
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $unidad_medida = $_POST['unidad_medida'] ?? '';

    if (!empty($nombre) && !empty($unidad_medida)) {
        try {
            $conexion = getDBConnection();
            
            // Verificar si el ingrediente ya existe
            $query_check = "SELECT id_ingrediente, nombre, unidad_medida FROM ingrediente WHERE nombre = ?";
            $stmt_check = mysqli_prepare($conexion, $query_check);
            mysqli_stmt_bind_param($stmt_check, "s", $nombre);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($result_check) > 0) {
                $row = mysqli_fetch_assoc($result_check);
                $error = 'Este ingrediente ya existe.';
                $ingrediente_existente = $row;
            } else {
                $query = "INSERT INTO ingrediente (nombre, unidad_medida) VALUES (?, ?)";
                $stmt = mysqli_prepare($conexion, $query);
                mysqli_stmt_bind_param($stmt, "ss", $nombre, $unidad_medida);
                
                if (mysqli_stmt_execute($stmt)) {
                    $id = mysqli_insert_id($conexion);
                    $mensaje = 'Ingrediente agregado con éxito. Ahora puedes seleccionarlo en la lista de ingredientes.';
                    $ingrediente_nuevo = [
                        'id' => $id,
                        'nombre' => $nombre,
                        'unidad_medida' => $unidad_medida
                    ];
                } else {
                    $error = 'Error al guardar el ingrediente en la base de datos.';
                }
            }
            mysqli_close($conexion);
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    } else {
        $error = 'El nombre y la unidad de medida son obligatorios.';
    }

    // Si la petición es AJAX (fetch), devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        if (isset($ingrediente_nuevo)) {
            echo json_encode(['success' => true, 'ingrediente' => $ingrediente_nuevo]);
        } elseif (isset($ingrediente_existente)) {
            echo json_encode(['success' => false, 'message' => $error, 'ingrediente' => $ingrediente_existente]);
        } else {
            echo json_encode(['success' => false, 'message' => $error]);
        }
        exit();
    }
}

// Redirigir de vuelta a mis_productos.php con mensaje
$redirect_url = 'mis_productos.php';
if ($mensaje) {
    $redirect_url .= '?mensaje=' . urlencode($mensaje);
}
if ($error) {
    $redirect_url .= '?error=' . urlencode($error);
}

header('Location: ' . $redirect_url);
exit();
?> 