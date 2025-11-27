<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Obtener datos del usuario
try {
    $conexion = getDBConnection();
    
    // Obtener datos básicos del usuario
    $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.direccion, 
                     u.localidad, u.ruta_imagen, cp.provincia, cp.localidad as nombre_localidad 
              FROM usuario u 
              LEFT JOIN codigo_postal cp ON u.localidad = cp.id 
              WHERE u.id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
    
    if (!$usuario) {
        throw new Exception("No se encontró el usuario");
    }
    
    // Obtener lista de códigos postales
    $query_cp = "SELECT id, id_postal, localidad, provincia FROM codigo_postal ORDER BY provincia, localidad";
    $resultado_cp = mysqli_query($conexion, $query_cp);
    if (!$resultado_cp) {
        error_log("Error en la consulta de códigos postales: " . mysqli_error($conexion));
        $codigos_postales = [];
    } else {
        $codigos_postales = mysqli_fetch_all($resultado_cp, MYSQLI_ASSOC);
    }
    
    // Si es tienda, obtener datos adicionales
    if ($_SESSION['tipo_usuario'] === 'tienda') {
        $query = "SELECT * FROM tienda WHERE id_tienda = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tienda = mysqli_fetch_assoc($result);
        error_log("Datos de la tienda: " . print_r($tienda, true));

        // Obtener recetas de la tienda
        $query_recetas = "SELECT r.id_receta, r.nombre, r.ruta_imagen, tr.disponible 
                          FROM receta r 
                          JOIN tienda_receta tr ON r.id_receta = tr.receta_id 
                          WHERE tr.tienda_id = ?";
        $stmt_recetas = mysqli_prepare($conexion, $query_recetas);
        mysqli_stmt_bind_param($stmt_recetas, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt_recetas);
        $result_recetas = mysqli_stmt_get_result($stmt_recetas);
        $recetas_tienda = mysqli_fetch_all($result_recetas, MYSQLI_ASSOC);
    }
    
    // Si es cliente, obtener recetas creadas por él
    if ($_SESSION['tipo_usuario'] === 'cliente') {
        $query_recetas_cliente = "SELECT id_receta, nombre, ruta_imagen, descripcion, veces_pedida FROM receta WHERE tienda_id = ?";
        $stmt_recetas_cliente = mysqli_prepare($conexion, $query_recetas_cliente);
        mysqli_stmt_bind_param($stmt_recetas_cliente, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt_recetas_cliente);
        $result_recetas_cliente = mysqli_stmt_get_result($stmt_recetas_cliente);
        $recetas_cliente = mysqli_fetch_all($result_recetas_cliente, MYSQLI_ASSOC);

        // Antes del foreach de recetas_cliente, obtener los ids de recetas que están en tienda_receta
        $query_recetas_en_menu = "SELECT receta_id FROM tienda_receta WHERE receta_id IN (SELECT id_receta FROM receta WHERE tienda_id = ?)";
        $stmt_recetas_en_menu = mysqli_prepare($conexion, $query_recetas_en_menu);
        mysqli_stmt_bind_param($stmt_recetas_en_menu, "i", $_SESSION['usuario_id']);
        mysqli_stmt_execute($stmt_recetas_en_menu);
        $result_recetas_en_menu = mysqli_stmt_get_result($stmt_recetas_en_menu);
        $recetas_en_menu = array_column(mysqli_fetch_all($result_recetas_en_menu, MYSQLI_ASSOC), 'receta_id');
    }
    
    // Procesar el formulario si se envió
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telefono'])) { // Asegurarse de que no sea el POST de AJAX
        // Procesar imagen de perfil
        if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen_perfil'];
            $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
            $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
            
            if (in_array($extension, $extensiones_permitidas)) {
                // Crear directorio si no existe
                if (!file_exists('imagenes_perfil')) {
                    mkdir('imagenes_perfil', 0777, true);
                }
                
                $nombre_archivo = uniqid() . '.' . $extension;
                $ruta_destino = 'imagenes_perfil/' . $nombre_archivo;
                
                if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                    // Actualizar ruta de imagen en la base de datos
                    $query = "UPDATE usuario SET ruta_imagen = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conexion, $query);
                    mysqli_stmt_bind_param($stmt, "si", $ruta_destino, $_SESSION['usuario_id']);
                    mysqli_stmt_execute($stmt);
                    
                    $_SESSION['ruta_imagen'] = $ruta_destino;
                    $success = 'Imagen de perfil actualizada correctamente';
                } else {
                    $error = 'Error al guardar la imagen';
                }
            } else {
                $error = 'Formato de imagen no permitido';
            }
        }
        
        // Actualizar datos básicos
        $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
        $direccion = mysqli_real_escape_string($conexion, $_POST['direccion']);
        $localidad = mysqli_real_escape_string($conexion, $_POST['localidad']);
        
        $query_parts = ["telefono = ?", "direccion = ?", "localidad = ?"];
        $params = [$telefono, $direccion, $localidad];
        $types = "ssi";
        
        // Procesar cambio de contraseña
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                $error .= ' La nueva contraseña debe tener al menos 6 caracteres.';
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $error .= ' Las contraseñas no coinciden.';
            } else {
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $params[] = $password_hash;
                $types .= "s";
            }
        }
        
        if (isset($ruta_destino)) {
            $query_parts[] = "ruta_imagen = ?";
            $params[] = $ruta_destino;
            $types .= "s";
        }
        
        if (empty($error)) {
            $query = "UPDATE usuario SET " . implode(', ', $query_parts) . " WHERE id = ?";
            $params[] = $_SESSION['usuario_id'];
            $types .= "i";
            
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Datos actualizados correctamente';
                // Actualizar los datos en la sesión
                $usuario['telefono'] = $telefono;
                $usuario['direccion'] = $direccion;
                $usuario['localidad'] = $localidad;
            } else {
                $error = 'Error al actualizar los datos';
            }
        }
        
        // Si es tienda, actualizar datos adicionales
        if ($_SESSION['tipo_usuario'] === 'tienda' && empty($error)) {
            $razon_social = $_POST['razon_social'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $rubro = $_POST['rubro'] ?? '';
            
            $query = "UPDATE tienda SET razon_social = ?, descripcion = ?, rubro = ? WHERE id_tienda = ?";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "sssi", $razon_social, $descripcion, $rubro, $_SESSION['usuario_id']);
            
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Error al actualizar los datos de la tienda';
            } else {
                // Actualizar los datos en la variable de la tienda para reflejarlos inmediatamente
                $tienda['razon_social'] = $razon_social;
                $tienda['descripcion'] = $descripcion;
                $tienda['rubro'] = $rubro;
            }
        }
    }
    
    mysqli_close($conexion);
} catch (Exception $e) {
    $error = 'Error en el sistema: ' . $e->getMessage();
    error_log("Error en perfil.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mi Perfil - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo filemtime('css/estilos.css'); ?>" />
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Mi Perfil</span></h1>
        </div>
    </header>

    <main class="contenido-principal">
        <div class="perfil-container">
            <h2>Mi Perfil</h2>
            
            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mensaje success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Div de visualización de información (no editable) -->
            <div id="info-display">
                <div class="perfil-header">
                    <img src="<?php echo htmlspecialchars($usuario['ruta_imagen'] ?? 'img/default-profile.png'); ?>" 
                         alt="Foto de perfil" class="perfil-imagen">
                </div>
                <div class="info-no-editable">
                    <h3>Información Personal</h3>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?></p>
                    <p><strong>Apellido:</strong> <?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email'] ?? 'No disponible'); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></p>
                    <p><strong>Localidad:</strong> <?php echo htmlspecialchars($usuario['nombre_localidad'] ?? ''); ?> (<?php echo htmlspecialchars($usuario['provincia'] ?? ''); ?>)</p>
                </div>

                <?php if ($_SESSION['tipo_usuario'] === 'tienda' && isset($tienda)): ?>
                <div class="info-no-editable">
                    <h3>Información de la Tienda</h3>
                    <p><strong>Razón Social:</strong> <?php echo htmlspecialchars($tienda['razon_social'] ?? ''); ?></p>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($tienda['descripcion'] ?? ''); ?></p>
                    <p><strong>Rubro:</strong> <?php echo htmlspecialchars($tienda['rubro'] ?? ''); ?></p>
                    <p><strong>Cantidad de ventas:</strong> <?php echo $tienda['cantidad_ventas'] ?? 0; ?></p>
                    <p><strong>Calificación:</strong> <?php echo $tienda['calificacion'] ?? 'Sin calificaciones'; ?></p>
                </div>
                <?php endif; ?>
                <button id="btn-editar" class="btn-submit">Editar Información</button>
            </div>

            <!-- Formulario de edición (oculto por defecto) -->
            <form id="edit-form" class="formulario-perfil" method="POST" action="perfil.php" enctype="multipart/form-data" style="display:none;">
                <div class="perfil-header">
                    <img src="<?php echo htmlspecialchars($usuario['ruta_imagen'] ?? 'img/default-profile.png'); ?>" 
                         alt="Foto de perfil" class="perfil-imagen">
                    <div class="campo-formulario">
                        <label for="imagen_perfil">Cambiar foto de perfil:</label>
                        <input type="file" id="imagen_perfil" name="imagen_perfil" accept="image/*">
                    </div>
                </div>

                <div class="info-editable">
                    <h3>Información Personal</h3>
                    <div class="campo-formulario">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                    </div>
                    <div class="campo-formulario">
                        <label for="direccion">Dirección:</label>
                        <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>">
                    </div>
                    <div class="campo-formulario">
                        <label for="localidad">Localidad:</label>
                        <select id="localidad" name="localidad" required>
                            <option value="">Seleccione una localidad</option>
                            <?php
                                $provincia_actual = '';
                                foreach ($codigos_postales as $cp) {
                                    if ($provincia_actual !== $cp['provincia']) {
                                        if ($provincia_actual !== '') {
                                            echo '</optgroup>';
                                        }
                                        $provincia_actual = $cp['provincia'];
                                        echo '<optgroup label="' . htmlspecialchars($provincia_actual) . '">';
                                    }
                                    $selected = (isset($usuario['localidad']) && $usuario['localidad'] == $cp['id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($cp['id']) . '" ' . $selected . '>' .
                                         '(' . htmlspecialchars($cp['id_postal']) . ') - ' .
                                         htmlspecialchars($cp['provincia']) . ' - ' .
                                         htmlspecialchars($cp['localidad']) .
                                         '</option>';
                                }
                                if ($provincia_actual !== '') {
                                    echo '</optgroup>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="info-editable">
                    <h3>Cambiar Contraseña</h3>
                    <div class="campo-formulario">
                        <label for="password">Nueva contraseña (dejar en blanco para no cambiar):</label>
                        <input type="password" id="password" name="password" minlength="6">
                    </div>
                    <div class="campo-formulario">
                        <label for="confirm_password">Confirmar nueva contraseña:</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                    </div>
                </div>

                <?php if ($_SESSION['tipo_usuario'] === 'tienda'): ?>
                <div class="info-editable">
                    <h3>Información de la Tienda</h3>
                    <div class="campo-formulario">
                        <label for="razon_social">Razón Social:</label>
                        <input type="text" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($tienda['razon_social'] ?? ''); ?>">
                    </div>
                    <div class="campo-formulario">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" class="comentario-textarea"><?php echo htmlspecialchars($tienda['descripcion'] ?? ''); ?></textarea>
                    </div>
                    <div class="campo-formulario">
                        <label for="rubro">Rubro:</label>
                        <input type="text" id="rubro" name="rubro" value="<?php echo htmlspecialchars($tienda['rubro'] ?? ''); ?>">
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
                <button type="button" id="btn-cancelar" class="btn-cancel">Cancelar</button>
            </form>

            <?php if ($_SESSION['tipo_usuario'] === 'tienda' && !empty($recetas_tienda)): ?>
            <div class="recetas-tienda-container">
                <h2>Mis Recetas en Menú</h2>
                <div class="grid-recetas">
                    <?php foreach ($recetas_tienda as $receta): ?>
                        <div class="receta-foto">
                            <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>">
                            <h3><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                            <div class="disponibilidad-switch">
                                <label class="switch">
                                    <input type="checkbox" class="disponibilidad-checkbox" data-receta-id="<?php echo $receta['id_receta']; ?>" <?php echo $receta['disponible'] ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="disponibilidad-texto"><?php echo $receta['disponible'] ? 'Disponible' : 'No Disponible'; ?></span>
                            </div>
                            <form action="eliminar_receta.php" method="POST" style="margin-top:10px;display:inline-block;">
                                <input type="hidden" name="id_receta" value="<?php echo $receta['id_receta']; ?>">
                                <button type="submit" class="btn-agregar-tarjeta" style="background:#e74c3c;color:#fff;" onclick="return confirm('¿Seguro que deseas eliminar esta receta?');">Eliminar del Menú</button>
                            </form>
                            <a href="ver_productos_receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn-agregar-tarjeta" style="background:#007bff;color:#fff;display:inline-block;margin-top:10px;">Ver productos</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($_SESSION['tipo_usuario'] === 'cliente' && !empty($recetas_cliente)): ?>
            <div class="recetas-tienda-container">
                <h2>Mis Recetas Creadas</h2>
                <div class="grid-recetas">
                    <?php foreach ($recetas_cliente as $receta): ?>
                        <div class="receta-foto">
                            <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>">
                            <h3><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($receta['descripcion']); ?></p>
                            <div class="info-receta">
                                <div class="ventas">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span><?php echo isset($receta['veces_pedida']) ? $receta['veces_pedida'] : 0; ?> pedidos</span>
                                </div>
                            </div>
                            <div class="control-carrito-tarjeta">
                                <div class="acciones-tarjeta">
                                    <a href="receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn-agregar-tarjeta">Ver receta</a>
                                    <?php if (!in_array($receta['id_receta'], $recetas_en_menu)): ?>
                                        <a href="editar_receta.php?id=<?php echo $receta['id_receta']; ?>" class="btn-agregar-tarjeta" style="background:#ffc107;color:#333;">Editar</a>
                                        <form action="eliminar_receta.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id_receta" value="<?php echo $receta['id_receta']; ?>">
                                            <button type="submit" class="btn-agregar-tarjeta" style="background:#e74c3c;color:#fff;" onclick="return confirm('¿Seguro que deseas eliminar esta receta?');">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($_SESSION['tipo_usuario'] === 'tienda' || $_SESSION['tipo_usuario'] === 'cliente'): ?>
        <div class="acciones-perfil-tienda">
            <a href="crear_receta.php" class="btn-submit">Crear receta</a>
        </div>
        <?php endif; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const displayDiv = document.getElementById('info-display');
        const editForm = document.getElementById('edit-form');
        const editButton = document.getElementById('btn-editar');
        const cancelButton = document.getElementById('btn-cancelar');

        editButton.addEventListener('click', () => {
            displayDiv.style.display = 'none';
            editForm.style.display = 'block';
        });

        cancelButton.addEventListener('click', () => {
            displayDiv.style.display = 'block';
            editForm.style.display = 'none';
        });

        // Código de los checkboxes de disponibilidad (si existe)
        const checkboxes = document.querySelectorAll('.disponibilidad-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const recetaId = this.dataset.recetaId;
                const isChecked = this.checked;
                const textoDisponibilidad = this.closest('.disponibilidad-switch').querySelector('.disponibilidad-texto');

                fetch('actualizar_disponibilidad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        receta_id: recetaId,
                        disponible: isChecked
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textoDisponibilidad.textContent = isChecked ? 'Disponible' : 'No Disponible';
                    } else {
                        // Revertir el checkbox si hay un error
                        this.checked = !isChecked;
                        alert('Error al actualizar: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud:', error);
                    this.checked = !isChecked;
                    alert('Error de conexión al intentar actualizar.');
                });
            });
        });
    });
    </script>
</body>
</html> 