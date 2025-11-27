<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'tienda') {
    header('Location: login.php');
    exit();
}

$tienda_id = $_SESSION['usuario_id'];
$error = '';
$mensaje = '';

// Manejar mensajes de agregar_ingrediente.php
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

try {
    $conn = getDBConnection();
    // Procesar el formulario para actualizar/agregar productos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingrediente_id'])) {
        for ($i = 0; $i < count($_POST['ingrediente_id']); $i++) {
            $ingrediente_id = $_POST['ingrediente_id'][$i];
            $marca = $_POST['marca'][$i];
            $peso_unidad = !empty($_POST['peso_unidad'][$i]) ? $_POST['peso_unidad'][$i] : null;
            $precio = $_POST['precio'][$i];
            $stock = $_POST['stock'][$i];
            $agregar_stock = isset($_POST['agregar_stock'][$i]) ? (int)$_POST['agregar_stock'][$i] : 0;
            $nuevo_stock = $stock + $agregar_stock;
            // Verificar si el producto ya existe
            $sql_check = "SELECT 1 FROM producto WHERE tienda_id = ? AND ingrediente_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $tienda_id, $ingrediente_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                // Actualizar producto existente
                $sql = "UPDATE producto SET marca = ?, peso_unidad = ?, precio = ?, stock = ? WHERE tienda_id = ? AND ingrediente_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sddiii", $marca, $peso_unidad, $precio, $nuevo_stock, $tienda_id, $ingrediente_id);
                $stmt->execute();
                // Si el stock es 0, poner recetas asociadas como no disponibles
                if ($nuevo_stock == 0) {
                    $sql_recetas = "UPDATE tienda_receta tr
                                    JOIN ingrediente_por_receta ir ON tr.receta_id = ir.receta_id
                                    SET tr.disponible = 0
                                    WHERE tr.tienda_id = ? AND ir.ingrediente_id = ?";
                    $stmt_recetas = $conn->prepare($sql_recetas);
                    $stmt_recetas->bind_param("ii", $tienda_id, $ingrediente_id);
                    $stmt_recetas->execute();
                }
            } else if ($ingrediente_id) {
                // Insertar nuevo producto
                $sql = "INSERT INTO producto (tienda_id, ingrediente_id, marca, peso_unidad, precio, stock) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisdii", $tienda_id, $ingrediente_id, $marca, $peso_unidad, $precio, $nuevo_stock);
                $stmt->execute();
            }
        }
        $mensaje = 'Productos actualizados correctamente.';
    }
    // Obtener todos los ingredientes
    $sql_ingredientes = "SELECT id_ingrediente, nombre, unidad_medida FROM ingrediente ORDER BY nombre ASC";
    $result_ingredientes = $conn->query($sql_ingredientes);
    $ingredientes = $result_ingredientes->fetch_all(MYSQLI_ASSOC);
    // Obtener todos los productos de la tienda
    $sql_productos = "SELECT p.ingrediente_id, i.nombre AS nombre_ingrediente, i.unidad_medida, p.marca, p.peso_unidad, p.precio, p.stock
                      FROM producto p
                      JOIN ingrediente i ON p.ingrediente_id = i.id_ingrediente
                      WHERE p.tienda_id = ?";
    $stmt = $conn->prepare($sql_productos);
    $stmt->bind_param("i", $tienda_id);
    $stmt->execute();
    $result_productos = $stmt->get_result();
    $productos = $result_productos->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Productos - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo filemtime('css/estilos.css'); ?>">
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
                    <li class="perfil-menu">
                        <i class="fa-solid fa-user perfil-icono"></i>
                        <div class="perfil-dropdown">
                            <a href="perfil.php">Mi Perfil</a>
                            <a href="mis_productos.php">Mis productos</a>
                            <a href="pedidos.php">Mis Pedidos</a>
                            <a href="logout.php">Cerrar Sesión</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Mis Productos</span></h1>
        </div>
    </header>
    <main class="contenido-principal">
        <div class="perfil-container">
            <h2>Gestión de Productos de mi Tienda</h2>
            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($mensaje): ?>
                <div class="mensaje success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            <form method="POST" class="formulario-tabla" id="form-productos">
                <table id="tabla-productos">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Marca</th>
                            <th>Peso por Unidad</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Agregar al stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $i => $prod): ?>
                                <tr class="fila-producto-existente" data-ingrediente-id="<?php echo $prod['ingrediente_id']; ?>">
                                    <td>
                                        <input type="hidden" name="ingrediente_id[]" value="<?php echo $prod['ingrediente_id']; ?>">
                                        <?php echo htmlspecialchars($prod['nombre_ingrediente']); ?> (<?php echo htmlspecialchars($prod['unidad_medida']); ?>)
                                    </td>
                                    <td><input type="text" name="marca[]" value="<?php echo htmlspecialchars($prod['marca']); ?>" required></td>
                                    <td><input type="number" name="peso_unidad[]" step="0.01" value="<?php echo htmlspecialchars($prod['peso_unidad']); ?>"></td>
                                    <td><input type="number" name="precio[]" step="0.01" value="<?php echo htmlspecialchars($prod['precio']); ?>" required></td>
                                    <td><input type="number" name="stock[]" value="<?php echo htmlspecialchars($prod['stock']); ?>"></td>
                                    <td><input type="number" name="agregar_stock[]" value="0" min="0"></td>
                                    <td>
                                        <button type="button" class="btn-eliminar-producto" style="background: #f44336; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <!-- Fila para agregar nuevo producto -->
                        <tr id="fila-agregar">
                            <td>
                                <select name="ingrediente_id[]" required>
                                    <option value="">Seleccionar ingrediente</option>
                                    <?php foreach ($ingredientes as $ing): ?>
                                        <option value="<?php echo $ing['id_ingrediente']; ?>"><?php echo htmlspecialchars($ing['nombre']) . ' (' . htmlspecialchars($ing['unidad_medida']) . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="marca[]" value=""></td>
                            <td><input type="number" name="peso_unidad[]" step="0.01"></td>
                            <td><input type="number" name="precio[]" step="0.01"></td>
                            <td><input type="number" name="stock[]" value="0"></td>
                            <td><input type="number" name="agregar_stock[]" value="0" min="0"></td>
                            <td>
                                <button type="button" class="btn-eliminar-fila" style="background: #f44336; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;align-items:center;">
                <button type="button" id="btn-agregar-fila" class="btn-submit" style="background:#1976d2;">Agregar producto</button>
                <button type="submit" form="form-productos" class="btn-submit">Guardar Cambios</button>
                <button type="button" id="btn-mostrar-ingrediente" class="btn-submit" style="background:#ff9800;">Agregar ingrediente</button>
                <a href="perfil.php" class="btn-submit">Volver a mi perfil</a>
            </div>

            <!-- Formulario para agregar ingrediente -->
            <div id="formulario-ingrediente" style="display:none; margin-top:30px; padding:20px; background:#f9f9f9; border-radius:8px; border:1px solid #e0e0e0;">
                <h3 style="margin-bottom:20px; color:#2e7d32;">Agregar Nuevo Ingrediente</h3>
                <form method="POST" action="agregar_ingrediente.php" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:15px; align-items:end;">
                    <div>
                        <label for="nombre_ingrediente" style="display:block; margin-bottom:5px; font-weight:500;">Nombre del ingrediente:</label>
                        <input type="text" id="nombre_ingrediente" name="nombre" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div>
                        <label for="unidad_medida" style="display:block; margin-bottom:5px; font-weight:500;">Unidad de medida:</label>
                        <select id="unidad_medida" name="unidad_medida" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            <option value="">Seleccionar unidad</option>
                            <option value="kilo">Kilo</option>
                            <option value="litro">Litro</option>
                            <option value="unidad">Unidad</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-submit" style="background:#4caf50; margin:0;">Crear Ingrediente</button>
                    </div>
                </form>
            </div>

            <script>
            document.getElementById('btn-agregar-fila').addEventListener('click', function() {
                var tabla = document.getElementById('tabla-productos').getElementsByTagName('tbody')[0];
                var nuevaFila = document.createElement('tr');
                nuevaFila.className = 'fila-nueva';
                nuevaFila.innerHTML = `
                    <td>
                        <select name="ingrediente_id[]" required>
                            <option value="">Seleccionar ingrediente</option>
                            <?php foreach ($ingredientes as $ing): ?>
                                <option value="<?php echo $ing['id_ingrediente']; ?>"><?php echo htmlspecialchars($ing['nombre']) . ' ('. htmlspecialchars($ing['unidad_medida']) . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="marca[]" value=""></td>
                    <td><input type="number" name="peso_unidad[]" step="0.01"></td>
                    <td><input type="number" name="precio[]" step="0.01"></td>
                    <td><input type="number" name="stock[]" value="0"></td>
                    <td><input type="number" name="agregar_stock[]" value="0" min="0"></td>
                    <td>
                        <button type="button" class="btn-eliminar-fila" style="background: #f44336; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;">×</button>
                    </td>
                `;
                tabla.appendChild(nuevaFila);
            });

            // Evento delegado para eliminar filas
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-eliminar-fila')) {
                    e.target.closest('tr').remove();
                }
                
                if (e.target.classList.contains('btn-eliminar-producto')) {
                    var fila = e.target.closest('tr');
                    var ingredienteId = fila.getAttribute('data-ingrediente-id');
                    
                    // Verificar si el producto está asociado a recetas o pedidos
                    fetch('verificar_producto_recetas.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ingrediente_id=' + ingredienteId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.tiene_recetas) {
                            // Mostrar mensaje de error con las recetas
                            var mensaje = 'El producto que intenta eliminar forma parte de las recetas:\n';
                            data.recetas.forEach(function(receta) {
                                mensaje += '- ' + receta + '\n';
                            });
                            mensaje += '\nElimine las recetas de su menú para proceder.';
                            alert(mensaje);
                        } else if (data.tiene_pedidos) {
                            // Mostrar mensaje de error por pedidos
                            alert('El producto está siendo usado en pedidos y no puede ser eliminado.');
                        } else {
                            // Confirmar eliminación
                            if (confirm('¿Está seguro de que desea eliminar este producto?')) {
                                // Eliminar el producto
                                fetch('eliminar_producto.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'ingrediente_id=' + ingredienteId
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        fila.remove();
                                        alert('Producto eliminado correctamente.');
                                    } else {
                                        alert('Error al eliminar el producto: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error al eliminar el producto.');
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al verificar las recetas asociadas.');
                    });
                }
            });

            // Mostrar/ocultar formulario de ingrediente
            document.getElementById('btn-mostrar-ingrediente').addEventListener('click', function() {
                var formulario = document.getElementById('formulario-ingrediente');
                if (formulario.style.display === 'none') {
                    formulario.style.display = 'block';
                    this.textContent = 'Ocultar formulario';
                    this.style.background = '#666';
                } else {
                    formulario.style.display = 'none';
                    this.textContent = 'Agregar ingrediente';
                    this.style.background = '#ff9800';
                }
            });
            </script>
        </div>
    </main>
    <footer class="footer">
        <div class="footer-contenido">
            <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 