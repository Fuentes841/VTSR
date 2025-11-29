<?php
session_start();
require_once 'config.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$conexion = getDBConnection();
$seccion = $_GET['seccion'] ?? 'usuarios';
$mensaje = '';
$tipo_mensaje = '';

// Manejar suspensión/activación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suspender'])) {
    $id = $_POST['id'] ?? '';
    $accion = $_POST['accion'] ?? ''; // 'suspender' o 'activar'
    
    try {
        $suspendido = ($accion === 'suspender') ? 1 : 0;
        $stmt = mysqli_prepare($conexion, "UPDATE usuario SET suspendido = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $suspendido, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $mensaje = $accion === 'suspender' ? 'Usuario suspendido correctamente.' : 'Usuario activado correctamente.';
        $tipo_mensaje = 'success';
    } catch (Exception $e) {
        $mensaje = 'Error al ' . ($accion === 'suspender' ? 'suspender' : 'activar') . ': ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Manejar creación y edición de ingredientes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['crear_ingrediente']) || isset($_POST['editar_ingrediente']))) {
    $nombre = trim($_POST['nombre'] ?? '');
    $unidad_medida = $_POST['unidad_medida'] ?? '';
    $condimento = isset($_POST['condimento']) ? 1 : 0;
    
    if (empty($nombre) || empty($unidad_medida)) {
        $mensaje = 'El nombre y la unidad de medida son obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        try {
            if (isset($_POST['editar_ingrediente'])) {
                // Editar ingrediente existente
                $id_ingrediente = $_POST['id_ingrediente'] ?? '';
                // Verificar si el nombre ya existe en otro ingrediente
                $query_check = "SELECT id_ingrediente FROM ingrediente WHERE nombre = ? AND id_ingrediente != ?";
                $stmt_check = mysqli_prepare($conexion, $query_check);
                mysqli_stmt_bind_param($stmt_check, "si", $nombre, $id_ingrediente);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                
                if (mysqli_num_rows($result_check) > 0) {
                    $mensaje = 'Ya existe otro ingrediente con ese nombre.';
                    $tipo_mensaje = 'error';
                } else {
                    $stmt = mysqli_prepare($conexion, "UPDATE ingrediente SET nombre = ?, unidad_medida = ?, condimento = ? WHERE id_ingrediente = ?");
                    mysqli_stmt_bind_param($stmt, "ssii", $nombre, $unidad_medida, $condimento, $id_ingrediente);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $mensaje = 'Ingrediente actualizado correctamente.';
                    $tipo_mensaje = 'success';
                }
            } else {
                // Crear nuevo ingrediente
                // Verificar si el ingrediente ya existe
                $query_check = "SELECT id_ingrediente FROM ingrediente WHERE nombre = ?";
                $stmt_check = mysqli_prepare($conexion, $query_check);
                mysqli_stmt_bind_param($stmt_check, "s", $nombre);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                
                if (mysqli_num_rows($result_check) > 0) {
                    $mensaje = 'Este ingrediente ya existe.';
                    $tipo_mensaje = 'error';
                } else {
                    $stmt = mysqli_prepare($conexion, "INSERT INTO ingrediente (nombre, unidad_medida, condimento) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "ssi", $nombre, $unidad_medida, $condimento);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $mensaje = 'Ingrediente creado correctamente.';
                    $tipo_mensaje = 'success';
                }
            }
        } catch (Exception $e) {
            $mensaje = 'Error al guardar el ingrediente: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Manejar eliminaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $tipo = $_POST['tipo'] ?? '';
    $id = $_POST['id'] ?? '';
    
    try {
        switch ($tipo) {
            case 'usuario':
                // Eliminar usuario (se eliminarán automáticamente cliente, tienda o admin por CASCADE)
                $stmt = mysqli_prepare($conexion, "DELETE FROM usuario WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje = 'Usuario eliminado correctamente.';
                $tipo_mensaje = 'success';
                break;
                
            case 'receta':
                $stmt = mysqli_prepare($conexion, "DELETE FROM receta WHERE id_receta = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje = 'Receta eliminada correctamente.';
                $tipo_mensaje = 'success';
                break;
                
            case 'tienda':
                // Eliminar tienda (se eliminará el usuario asociado por CASCADE)
                $stmt = mysqli_prepare($conexion, "DELETE FROM tienda WHERE id_tienda = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje = 'Tienda eliminada correctamente.';
                $tipo_mensaje = 'success';
                break;
                
            case 'ingrediente':
                $stmt = mysqli_prepare($conexion, "DELETE FROM ingrediente WHERE id_ingrediente = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje = 'Ingrediente eliminado correctamente.';
                $tipo_mensaje = 'success';
                break;
                
            case 'producto':
                $tienda_id = $_POST['tienda_id'] ?? '';
                $ingrediente_id = $_POST['ingrediente_id'] ?? '';
                $stmt = mysqli_prepare($conexion, "DELETE FROM producto WHERE tienda_id = ? AND ingrediente_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $tienda_id, $ingrediente_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje = 'Producto eliminado correctamente.';
                $tipo_mensaje = 'success';
                break;
        }
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener datos según la sección
$datos = [];
switch ($seccion) {
    case 'usuarios':
        $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.direccion, u.suspendido,
                         CASE 
                             WHEN a.id_admin IS NOT NULL THEN 'admin'
                             WHEN c.id_cliente IS NOT NULL THEN 'cliente'
                             WHEN t.id_tienda IS NOT NULL THEN 'tienda'
                             ELSE 'usuario'
                         END as tipo_usuario
                  FROM usuario u
                  LEFT JOIN admin a ON u.id = a.id_admin
                  LEFT JOIN cliente c ON u.id = c.id_cliente
                  LEFT JOIN tienda t ON u.id = t.id_tienda
                  ORDER BY u.id DESC";
        $result = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_all($result, MYSQLI_ASSOC);
        break;
        
    case 'recetas':
        $query = "SELECT id_receta, nombre, descripcion, veces_pedida, ruta_imagen 
                  FROM receta 
                  ORDER BY id_receta DESC";
        $result = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_all($result, MYSQLI_ASSOC);
        break;
        
    case 'tiendas':
        $query = "SELECT t.id_tienda, t.razon_social, t.descripcion, t.rubro, t.cantidad_ventas, t.calificacion,
                         u.nombre, u.apellido, u.email, u.suspendido
                  FROM tienda t
                  INNER JOIN usuario u ON t.id_tienda = u.id
                  ORDER BY t.id_tienda DESC";
        $result = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_all($result, MYSQLI_ASSOC);
        break;
        
    case 'ingredientes':
        $query = "SELECT id_ingrediente, nombre, unidad_medida, condimento 
                  FROM ingrediente 
                  ORDER BY id_ingrediente DESC";
        $result = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_all($result, MYSQLI_ASSOC);
        break;
        
    case 'productos':
        $query = "SELECT p.tienda_id, p.ingrediente_id, p.marca, p.peso_unidad, p.precio, p.stock,
                         t.razon_social, i.nombre as ingrediente_nombre
                  FROM producto p
                  INNER JOIN tienda t ON p.tienda_id = t.id_tienda
                  INNER JOIN ingrediente i ON p.ingrediente_id = i.id_ingrediente
                  ORDER BY p.tienda_id DESC, p.ingrediente_id DESC";
        $result = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_all($result, MYSQLI_ASSOC);
        break;
}

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Panel de Administración</span></h1>
        </div>
    </header>
    
    <main class="contenido-principal">
        <div class="admin-container">
            <h2 class="admin-title">Panel de Administración</h2>
            
            <?php if ($mensaje): ?>
                <div class="mensaje-admin <?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- Navegación por pestañas -->
            <div class="admin-tabs">
                <a href="?seccion=usuarios" class="admin-tab <?php echo $seccion === 'usuarios' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                <a href="?seccion=recetas" class="admin-tab <?php echo $seccion === 'recetas' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Recetas
                </a>
                <a href="?seccion=tiendas" class="admin-tab <?php echo $seccion === 'tiendas' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Tiendas
                </a>
                <a href="?seccion=ingredientes" class="admin-tab <?php echo $seccion === 'ingredientes' ? 'active' : ''; ?>">
                    <i class="fas fa-carrot"></i> Ingredientes
                </a>
                <a href="?seccion=productos" class="admin-tab <?php echo $seccion === 'productos' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Productos
                </a>
            </div>
            
            <!-- Contenido de la sección -->
            <div class="admin-content">
                <div class="admin-table-container">
                    <?php if ($seccion === 'usuarios'): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr class="<?php echo $fila['suspendido'] ? 'suspended-row' : ''; ?>">
                                        <td><?php echo htmlspecialchars($fila['id']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['email']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $fila['tipo_usuario']; ?>">
                                                <?php echo htmlspecialchars($fila['tipo_usuario']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($fila['suspendido']): ?>
                                                <span class="badge badge-suspended">Suspendido</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Activo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="admin-actions">
                                                <?php if ($fila['suspendido']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de activar este usuario?');">
                                                        <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                                        <input type="hidden" name="accion" value="activar">
                                                        <button type="submit" name="suspender" class="btn-activar">
                                                            <i class="fas fa-check"></i> Activar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de suspender este usuario? El usuario no podrá iniciar sesión hasta que sea activado nuevamente.');">
                                                        <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                                        <input type="hidden" name="accion" value="suspender">
                                                        <button type="submit" name="suspender" class="btn-suspender">
                                                            <i class="fas fa-ban"></i> Suspender
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline; margin-left: 0.5em;" onsubmit="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="tipo" value="usuario">
                                                    <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                                    <button type="submit" name="eliminar" class="btn-eliminar">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php elseif ($seccion === 'recetas'): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Veces Pedida</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fila['id_receta']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($fila['descripcion'], 0, 50)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($fila['veces_pedida']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar esta receta? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="tipo" value="receta">
                                                <input type="hidden" name="id" value="<?php echo $fila['id_receta']; ?>">
                                                <button type="submit" name="eliminar" class="btn-eliminar">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php elseif ($seccion === 'tiendas'): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Razón Social</th>
                                    <th>Email</th>
                                    <th>Rubro</th>
                                    <th>Ventas</th>
                                    <th>Calificación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr class="<?php echo $fila['suspendido'] ? 'suspended-row' : ''; ?>">
                                        <td><?php echo htmlspecialchars($fila['id_tienda']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['email']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['rubro']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['cantidad_ventas']); ?></td>
                                        <td><?php echo $fila['calificacion'] ? htmlspecialchars($fila['calificacion']) : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($fila['suspendido']): ?>
                                                <span class="badge badge-suspended">Suspendido</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Activo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="admin-actions">
                                                <?php if ($fila['suspendido']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de activar esta tienda?');">
                                                        <input type="hidden" name="id" value="<?php echo $fila['id_tienda']; ?>">
                                                        <input type="hidden" name="accion" value="activar">
                                                        <button type="submit" name="suspender" class="btn-activar">
                                                            <i class="fas fa-check"></i> Activar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de suspender esta tienda? La tienda no podrá iniciar sesión hasta que sea activada nuevamente.');">
                                                        <input type="hidden" name="id" value="<?php echo $fila['id_tienda']; ?>">
                                                        <input type="hidden" name="accion" value="suspender">
                                                        <button type="submit" name="suspender" class="btn-suspender">
                                                            <i class="fas fa-ban"></i> Suspender
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline; margin-left: 0.5em;" onsubmit="return confirm('¿Está seguro de eliminar esta tienda? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="tipo" value="tienda">
                                                    <input type="hidden" name="id" value="<?php echo $fila['id_tienda']; ?>">
                                                    <button type="submit" name="eliminar" class="btn-eliminar">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php elseif ($seccion === 'ingredientes'): ?>
                        <!-- Formulario para agregar nuevo ingrediente -->
                        <div class="admin-form-container">
                            <h3>Agregar Nuevo Ingrediente</h3>
                            <form method="POST" class="admin-form">
                                <div class="form-group">
                                    <label for="nombre_ingrediente">Nombre:</label>
                                    <input type="text" id="nombre_ingrediente" name="nombre" required maxlength="100">
                                </div>
                                <div class="form-group">
                                    <label for="unidad_medida_nuevo">Unidad de Medida:</label>
                                    <select id="unidad_medida_nuevo" name="unidad_medida" required>
                                        <option value="">Seleccione...</option>
                                        <option value="kilo">Kilo</option>
                                        <option value="litro">Litro</option>
                                        <option value="unidad">Unidad</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="condimento" value="1">
                                        ¿Es un condimento?
                                    </label>
                                </div>
                                <button type="submit" name="crear_ingrediente" class="btn-crear">
                                    <i class="fas fa-plus"></i> Crear Ingrediente
                                </button>
                            </form>
                        </div>
                        
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Unidad de Medida</th>
                                    <th>Condimento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fila['id_ingrediente']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['unidad_medida']); ?></td>
                                        <td><?php echo $fila['condimento'] ? 'Sí' : 'No'; ?></td>
                                        <td>
                                            <div class="admin-actions">
                                                <button type="button" class="btn-editar" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($fila)); ?>)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este ingrediente? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="tipo" value="ingrediente">
                                                    <input type="hidden" name="id" value="<?php echo $fila['id_ingrediente']; ?>">
                                                    <button type="submit" name="eliminar" class="btn-eliminar">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Modal para editar ingrediente -->
                        <div id="modal-editar-ingrediente" class="admin-modal" style="display: none;">
                            <div class="admin-modal-content">
                                <span class="admin-modal-close" onclick="cerrarModalEditar()">&times;</span>
                                <h3>Editar Ingrediente</h3>
                                <form method="POST" class="admin-form">
                                    <input type="hidden" name="id_ingrediente" id="edit_id_ingrediente">
                                    <div class="form-group">
                                        <label for="edit_nombre">Nombre:</label>
                                        <input type="text" id="edit_nombre" name="nombre" required maxlength="100">
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_unidad_medida">Unidad de Medida:</label>
                                        <select id="edit_unidad_medida" name="unidad_medida" required>
                                            <option value="kilo">Kilo</option>
                                            <option value="litro">Litro</option>
                                            <option value="unidad">Unidad</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="edit_condimento" name="condimento" value="1">
                                            ¿Es un condimento?
                                        </label>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="editar_ingrediente" class="btn-guardar">
                                            <i class="fas fa-save"></i> Guardar Cambios
                                        </button>
                                        <button type="button" onclick="cerrarModalEditar()" class="btn-cancelar">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <script>
                        function abrirModalEditar(ingrediente) {
                            document.getElementById('edit_id_ingrediente').value = ingrediente.id_ingrediente;
                            document.getElementById('edit_nombre').value = ingrediente.nombre;
                            document.getElementById('edit_unidad_medida').value = ingrediente.unidad_medida;
                            document.getElementById('edit_condimento').checked = ingrediente.condimento == 1;
                            document.getElementById('modal-editar-ingrediente').style.display = 'block';
                        }
                        
                        function cerrarModalEditar() {
                            document.getElementById('modal-editar-ingrediente').style.display = 'none';
                        }
                        
                        // Cerrar modal al hacer clic fuera de él
                        window.onclick = function(event) {
                            const modal = document.getElementById('modal-editar-ingrediente');
                            if (event.target == modal) {
                                cerrarModalEditar();
                            }
                        }
                        </script>
                        
                    <?php elseif ($seccion === 'productos'): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tienda</th>
                                    <th>Ingrediente</th>
                                    <th>Marca</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $fila): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fila['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['ingrediente_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['marca'] ?? 'N/A'); ?></td>
                                        <td>$<?php echo number_format($fila['precio'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($fila['stock']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este producto? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="tipo" value="producto">
                                                <input type="hidden" name="tienda_id" value="<?php echo $fila['tienda_id']; ?>">
                                                <input type="hidden" name="ingrediente_id" value="<?php echo $fila['ingrediente_id']; ?>">
                                                <button type="submit" name="eliminar" class="btn-eliminar">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
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
</body>
</html>

