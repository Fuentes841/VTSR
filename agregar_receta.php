<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está logueado y es una tienda
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    header('Location: login.php');
    exit();
}

// Verificar si se proporcionó un ID de receta
if (!isset($_GET['id'])) {
    header('Location: recetas.php');
    exit();
}

$ID_RECETA = $_GET['id'];
$tienda_id = $_SESSION['usuario_id'];

// Obtener conexión a la base de datos
$conn = getDBConnection();

// Obtener los ingredientes de la receta
$sql_ingredientes = "SELECT i.id_ingrediente, i.nombre 
                     FROM ingrediente i 
                     INNER JOIN ingrediente_por_receta ri ON i.id_ingrediente = ri.ingrediente_id 
                     WHERE ri.receta_id = ?";
$stmt_ingredientes = $conn->prepare($sql_ingredientes);
$stmt_ingredientes->bind_param("i", $ID_RECETA);
$stmt_ingredientes->execute();
$result_ingredientes = $stmt_ingredientes->get_result();
$ingredientes = $result_ingredientes->fetch_all(MYSQLI_ASSOC);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = "";
    $error = "";
    
    // Insertar en tienda_receta si no existe
    $sql_tienda_receta = "INSERT IGNORE INTO tienda_receta (tienda_id, receta_id, disponible, cantidad_ventas, calificacion) VALUES (?, ?, 0, 0, NULL)";
    $stmt_tienda_receta = $conn->prepare($sql_tienda_receta);
    $stmt_tienda_receta->bind_param("ii", $tienda_id, $ID_RECETA);
    if (!$stmt_tienda_receta->execute()) {
        $error .= "Error al agregar la receta al menú de la tienda: " . $conn->error . "<br>";
    }
    
    // Procesar cada producto
    for ($i = 0; $i < count($_POST['ingrediente_id']); $i++) {
        $ingrediente_id = $_POST['ingrediente_id'][$i];
        $marca = $_POST['marca'][$i];
        $peso_unidad = !empty($_POST['peso_unidad'][$i]) ? $_POST['peso_unidad'][$i] : null;
        $precio = $_POST['precio'][$i];
        $stock = $_POST['stock'][$i];
        
        // Verificar si el producto ya existe
        $sql_check = "SELECT tienda_id FROM producto WHERE tienda_id = ? AND ingrediente_id = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ii", $tienda_id, $ingrediente_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Actualizar producto existente
            $sql = "UPDATE producto SET marca = ?, peso_unidad = ?, precio = ?, stock = ? 
                    WHERE tienda_id = ? AND ingrediente_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddiii", $marca, $peso_unidad, $precio, $stock, $tienda_id, $ingrediente_id);
        } else {
            // Insertar nuevo producto
            $sql = "INSERT INTO producto (tienda_id, ingrediente_id, marca, peso_unidad, precio, stock) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisdii", $tienda_id, $ingrediente_id, $marca, $peso_unidad, $precio, $stock);
        }
        
        if (!$stmt->execute()) {
            $error .= "Error al procesar el producto " . ($i + 1) . ": " . $conn->error . "<br>";
        }
        
        // Procesar imagen si se subió una nueva
        /*
        if (isset($_FILES['ruta_imagen']['name'][$i]) && $_FILES['ruta_imagen']['error'][$i] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['ruta_imagen']['tmp_name'][$i];
            $nombre_imagen = time() . '_' . $_FILES['ruta_imagen']['name'][$i];
            $ruta_destino = 'img/productos/' . $nombre_imagen;
            
            if (move_uploaded_file($imagen, $ruta_destino)) {
                $sql_imagen = "UPDATE producto SET ruta_imagen = ? WHERE tienda_id = ? AND ingrediente_id = ?";
                $stmt = $conn->prepare($sql_imagen);
                $stmt->bind_param("sii", $ruta_destino, $tienda_id, $ingrediente_id);
                $stmt->execute();
            } else {
                $error .= "Error al subir la imagen del producto " . ($i + 1) . "<br>";
            }
        }
        */
    }
    
    if (empty($error)) {
        $mensaje = "Productos actualizados exitosamente";
    }
}

$es_cliente = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'cliente';

$success_message_from_session = '';
if (isset($_SESSION['success_message'])) {
    $success_message_from_session = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agregar a mi Menú - Vive tu Sabor</title>
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
                    <li>
                        <form action="resultados_buscar.php" method="post">
                            <input type="search" name="buscar" placeholder="Buscar receta, tienda o producto" />
                            <button type="submit" aria-label="Buscar" class="btn-lupa">🔍</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Agregar a mi Menú</span></h1>
        </div>
    </header>

    <main>
        <section class="formulario-seccion">
            <div class="formulario-container">
                <h2>Agregar Productos a mi Menú</h2>
                
                <?php if ($success_message_from_session): ?>
                    <div class="mensaje success">
                        <?php echo htmlspecialchars($success_message_from_session); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($mensaje)): ?>
                    <div class="mensaje success">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="mensaje error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="formulario-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Marca</th>
                                <th>Peso por Unidad</th>
                                <th>Precio</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingredientes as $ingrediente): 
                                // Verificar si ya existe un producto para este ingrediente
                                $sql_producto = "SELECT * FROM producto 
                                               WHERE tienda_id = ? AND ingrediente_id = ?";
                                $stmt = $conn->prepare($sql_producto);
                                $stmt->bind_param("ii", $tienda_id, $ingrediente['id_ingrediente']);
                                $stmt->execute();
                                $producto_existente = $stmt->get_result()->fetch_assoc();
                            ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($ingrediente['nombre']); ?>
                                        <input type="hidden" name="ingrediente_id[]" value="<?php echo $ingrediente['id_ingrediente']; ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="marca[]" 
                                               value="<?php echo $producto_existente ? htmlspecialchars($producto_existente['marca']) : ''; ?>" 
                                               required>
                                    </td>
                                    <td>
                                        <input type="number" name="peso_unidad[]" step="0.01" min="0"
                                               value="<?php echo $producto_existente ? htmlspecialchars($producto_existente['peso_unidad']) : ''; ?>" 
                                               >
                                    </td>
                                    <td>
                                        <input type="number" name="precio[]" step="0.01" min="0"
                                               value="<?php echo $producto_existente ? htmlspecialchars($producto_existente['precio']) : ''; ?>" 
                                               required>
                                    </td>
                                    <td>
                                        <input type="number" name="stock[]" min="0" step="0.001"
                                               value="<?php echo $producto_existente ? htmlspecialchars($producto_existente['stock']) : ''; ?>" 
                                               required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" class="btn-submit">Guardar Productos</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-contenido">
            <div class="footer-enlaces">
                <a href="index.php">Inicio</a>
                <a href="recetas.php">Recetas</a>
                <a href="tiendas.php">Tiendas</a>
                <a href="contacto.php">Contacto</a>
            </div>
            <p>&copy; 2024 Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 