<?php
session_start();
require_once 'config.php';

// Verificar que el usuario esté logueado
echo '';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar que se haya pasado un id de receta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: perfil.php');
    exit();
}

$id_receta = (int)$_GET['id'];
$error = '';
$success = '';
$ingredientes = [];

try {
    $conexion = getDBConnection();
    // Verificar que la receta pertenezca al usuario
    $query = "SELECT * FROM receta WHERE id_receta = ? AND tienda_id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_receta, $_SESSION['usuario_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $receta = mysqli_fetch_assoc($result);
    if (!$receta) {
        throw new Exception('No tienes permiso para editar esta receta.');
    }

    // Obtener ingredientes de la base de datos
    $query_ingredientes = "SELECT id_ingrediente, nombre, unidad_medida FROM ingrediente ORDER BY nombre ASC";
    $result_ingredientes = mysqli_query($conexion, $query_ingredientes);
    while ($row = mysqli_fetch_assoc($result_ingredientes)) {
        $ingredientes[] = $row;
    }

    // Obtener ingredientes asociados a la receta
    $ingredientes_receta = [];
    $query_ir = "SELECT ingrediente_id, cantidad FROM ingrediente_por_receta WHERE receta_id = ?";
    $stmt_ir = mysqli_prepare($conexion, $query_ir);
    mysqli_stmt_bind_param($stmt_ir, "i", $id_receta);
    mysqli_stmt_execute($stmt_ir);
    $result_ir = mysqli_stmt_get_result($stmt_ir);
    while ($row = mysqli_fetch_assoc($result_ir)) {
        $ingredientes_receta[$row['ingrediente_id']] = $row['cantidad'];
    }

    // Procesar el formulario de edición
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_receta'])) {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $pasos = isset($_POST['pasos']) ? implode('||', $_POST['pasos']) : '';
        $ingredientes_form = $_POST['ingredientes'] ?? [];

        if (empty($nombre) || empty($descripcion) || empty($pasos) || empty($ingredientes_form)) {
            $error = 'Todos los campos son obligatorios, incluyendo al menos un paso y un ingrediente.';
        } else {
            mysqli_begin_transaction($conexion);
            try {
                // Manejo de la imagen
                $ruta_imagen = $receta['ruta_imagen'];
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $imagen = $_FILES['imagen'];
                    $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
                    if (in_array($extension, $extensiones_permitidas)) {
                        if (!file_exists('receta')) {
                            mkdir('receta', 0777, true);
                        }
                        $nombre_archivo = uniqid() . '.' . $extension;
                        $ruta_imagen = 'receta/' . $nombre_archivo;
                        if (!move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
                            throw new Exception('Error al guardar la imagen de la receta.');
                        }
                    } else {
                        throw new Exception('Formato de imagen no permitido.');
                    }
                }
                // Actualizar la receta
                $query_update = "UPDATE receta SET nombre = ?, descripcion = ?, pasos = ?, ruta_imagen = ? WHERE id_receta = ? AND tienda_id = ?";
                $stmt_update = mysqli_prepare($conexion, $query_update);
                mysqli_stmt_bind_param($stmt_update, "ssssii", $nombre, $descripcion, $pasos, $ruta_imagen, $id_receta, $_SESSION['usuario_id']);
                mysqli_stmt_execute($stmt_update);

                // Actualizar ingredientes: eliminar los actuales y agregar los nuevos
                $query_delete_ir = "DELETE FROM ingrediente_por_receta WHERE receta_id = ?";
                $stmt_delete_ir = mysqli_prepare($conexion, $query_delete_ir);
                mysqli_stmt_bind_param($stmt_delete_ir, "i", $id_receta);
                mysqli_stmt_execute($stmt_delete_ir);

                $query_insert_ir = "INSERT INTO ingrediente_por_receta (receta_id, ingrediente_id, cantidad) VALUES (?, ?, ?)";
                $stmt_insert_ir = mysqli_prepare($conexion, $query_insert_ir);
                foreach ($ingredientes_form as $ing) {
                    $id_ingrediente = $ing['id'];
                    $cantidad = $ing['cantidad'];
                    mysqli_stmt_bind_param($stmt_insert_ir, "iid", $id_receta, $id_ingrediente, $cantidad);
                    mysqli_stmt_execute($stmt_insert_ir);
                }
                mysqli_commit($conexion);
                $success = '¡Receta actualizada con éxito!';
                // Recargar datos actualizados
                header('Location: perfil.php');
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $error = 'Error al actualizar la receta: ' . $e->getMessage();
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Receta - Vive tu Sabor</title>
    <link rel="stylesheet" href="css/estilos.css?v=<?php echo filemtime('css/estilos.css'); ?>">
    <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
    <style>
        .perfil-container {
            max-width: 900px;
            margin: 20px auto;
        }
        .formulario-perfil, 
        .campo-formulario {
            width: 100%;
            box-sizing: border-box;
        }
        .formulario-perfil {
            background-color: #fdfdff;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #e6e6e6;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .campo-formulario {
            margin-bottom: 25px;
            padding-top: 25px;
            border-top: 1px solid #f0f0f0;
        }
        .campo-formulario:first-child {
            border-top: none;
            padding-top: 0;
        }
        .campo-formulario label, .campo-formulario h3, .campo-formulario h4, .campo-formulario h5 {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #444;
            font-size: 1.1em;
        }
        h3 {
            color: #0056b3;
        }
        .formulario-perfil input[type="text"],
        .formulario-perfil input[type="number"],
        .formulario-perfil input[type="file"],
        .formulario-perfil select,
        .formulario-perfil textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .formulario-perfil input:focus, .formulario-perfil select:focus, .formulario-perfil textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.25);
            outline: none;
        }
        .formulario-perfil textarea {
            resize: vertical;
            min-height: 120px;
        }
        .btn-submit, .btn-remover {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            text-align: center;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        button[name="crear_receta"], button[name="editar_receta"] {
            width: 100%;
            font-size: 18px;
            padding: 15px;
        }
        .ingrediente-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .paso-container {
            margin-bottom: 20px;
        }
    </style>
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
                </ul>
            </nav>
        </div>
        <div class="fondito">
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Editar Receta</span></h1>
        </div>
    </header>
    <main class="contenido-principal">
        <div class="perfil-container">
            <h2>Editar Receta</h2>
            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mensaje success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form action="editar_receta.php?id=<?php echo $id_receta; ?>" method="POST" enctype="multipart/form-data" class="formulario-perfil">
                <div class="campo-formulario">
                    <label for="nombre">Nombre de la Receta:</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($receta['nombre']); ?>">
                </div>
                <div class="campo-formulario">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required class="comentario-textarea"><?php echo htmlspecialchars($receta['descripcion']); ?></textarea>
                </div>
                <div class="campo-formulario">
                    <label for="imagen">Imagen de la Receta:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <?php if (!empty($receta['ruta_imagen'])): ?>
                        <img src="<?php echo htmlspecialchars($receta['ruta_imagen']); ?>" alt="Imagen actual" style="max-width:200px;display:block;margin-top:10px;">
                    <?php endif; ?>
                </div>
                <div class="campo-formulario">
                    <h3>Pasos de la Receta</h3>
                    <div id="pasos-wrapper">
                        <?php 
                        $pasos = explode('||', $receta['pasos']);
                        foreach ($pasos as $i => $paso): ?>
                            <div class="paso-container">
                                <div class="paso-header">
                                    <label>Paso <?php echo ($i+1); ?>:</label>
                                    <?php if ($i > 0): ?><button type="button" class="btn-remover" onclick="this.closest('.paso-container').remove()">X</button><?php endif; ?>
                                </div>
                                <textarea name="pasos[]" rows="2" required><?php echo htmlspecialchars($paso); ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="agregar-paso" class="btn-submit">Agregar Paso</button>
                </div>
                <div class="campo-formulario">
                    <h3>Ingredientes</h3>
                    <div id="ingredientes-receta-wrapper">
                        <?php foreach ($ingredientes_receta as $id_ing => $cantidad): ?>
                            <?php 
                            $ing = array_filter($ingredientes, function($i) use ($id_ing) { return $i['id_ingrediente'] == $id_ing; });
                            $ing = reset($ing);
                            ?>
                            <div class="ingrediente-item">
                                <span><?php echo htmlspecialchars($ing['nombre']); ?> - <?php echo htmlspecialchars($cantidad); ?> <?php echo htmlspecialchars($ing['unidad_medida']); ?></span>
                                <input type="hidden" name="ingredientes[<?php echo $id_ing; ?>][id]" value="<?php echo $id_ing; ?>">
                                <input type="hidden" name="ingredientes[<?php echo $id_ing; ?>][cantidad]" value="<?php echo $cantidad; ?>">
                                <button type="button" class="btn-remover" onclick="this.parentElement.remove()">X</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <h4>Añadir Ingrediente a la Receta</h4>
                    <div class="ingrediente-container">
                        <select id="ingrediente-selector">
                            <option value="" data-unidad="">Seleccione un ingrediente</option>
                            <?php foreach ($ingredientes as $ing): ?>
                                <option value="<?php echo $ing['id_ingrediente']; ?>" data-unidad="<?php echo htmlspecialchars($ing['unidad_medida']); ?>"><?php echo htmlspecialchars($ing['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" id="ingrediente-cantidad" placeholder="Cantidad por Porcion" step="0.01">
                        <input type="text" id="unidad-medida-display" readonly>
                        <button type="button" id="agregar-ingrediente-receta" class="btn-submit">Añadir</button>
                    </div>
                    <h4>¿No encuentras un ingrediente?</h4>
                    <button type="button" id="btn-mostrar-form-ingrediente" class="btn-submit">Agregar Nuevo Ingrediente</button>
                    <div id="form-nuevo-ingrediente">
                        <h5>Crear Nuevo Ingrediente</h5>
                        <input type="text" id="nuevo-ing-nombre" placeholder="Nombre del ingrediente">
                        <select id="nuevo-ing-unidad">
                            <option value="kilo">Kilo</option>
                            <option value="litro">Litro</option>
                            <option value="unidad">Unidad</option>
                        </select>
                        <label><input type="checkbox" id="nuevo-ing-condimento"> ¿Es un condimento?</label>
                        <button type="button" id="guardar-nuevo-ingrediente" class="btn-submit">Guardar Ingrediente</button>
                    </div>
                </div>
                <button type="submit" name="editar_receta" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </main>
    <footer class="footer">
        <div class="footer-contenido">
            <p>&copy; <?php echo date('Y'); ?> Vive tu Sabor. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Lógica para agregar pasos ---
        const btnAgregarPaso = document.getElementById('agregar-paso');
        const pasosWrapper = document.getElementById('pasos-wrapper');
        let pasoCount = pasosWrapper.querySelectorAll('.paso-container').length;
        btnAgregarPaso.addEventListener('click', () => {
            pasoCount++;
            const nuevoPaso = document.createElement('div');
            nuevoPaso.className = 'paso-container';
            nuevoPaso.innerHTML = `
                <div class="paso-header">
                    <label>Paso ${pasoCount}:</label>
                    <button type="button" class="btn-remover" onclick="this.closest('.paso-container').remove()">X</button>
                </div>
                <textarea name="pasos[]" rows="2" required></textarea>
            `;
            pasosWrapper.appendChild(nuevoPaso);
        });
        // --- Lógica para agregar ingredientes a la receta ---
        const btnAgregarIngReceta = document.getElementById('agregar-ingrediente-receta');
        const ingredientesWrapper = document.getElementById('ingredientes-receta-wrapper');
        const selectorIngredientes = document.getElementById('ingrediente-selector');
        const unidadMedidaDisplay = document.getElementById('unidad-medida-display');
        selectorIngredientes.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            unidadMedidaDisplay.value = selectedOption.dataset.unidad || '';
        });
        btnAgregarIngReceta.addEventListener('click', () => {
            const selectedOption = selectorIngredientes.options[selectorIngredientes.selectedIndex];
            const ingredienteId = selectedOption.value;
            const ingredienteNombre = selectedOption.text;
            const cantidad = document.getElementById('ingrediente-cantidad').value;
            if (ingredienteId && cantidad) {
                const ingredienteItem = document.createElement('div');
                ingredienteItem.className = 'ingrediente-item';
                ingredienteItem.innerHTML = `
                    <span>${ingredienteNombre} - ${cantidad} ${unidadMedidaDisplay.value}</span>
                    <input type="hidden" name="ingredientes[${ingredienteId}][id]" value="${ingredienteId}">
                    <input type="hidden" name="ingredientes[${ingredienteId}][cantidad]" value="${cantidad}">
                    <button type="button" class="btn-remover" onclick="this.parentElement.remove()">X</button>
                `;
                ingredientesWrapper.appendChild(ingredienteItem);
                // Limpiar campos
                selectorIngredientes.value = '';
                document.getElementById('ingrediente-cantidad').value = '';
                unidadMedidaDisplay.value = '';
            } else {
                alert('Por favor, seleccione un ingrediente y especifique la cantidad.');
            }
        });
        // --- Lógica para mostrar/ocultar formulario de nuevo ingrediente ---
        const btnMostrarForm = document.getElementById('btn-mostrar-form-ingrediente');
        const formNuevoIngrediente = document.getElementById('form-nuevo-ingrediente');
        btnMostrarForm.addEventListener('click', () => {
            formNuevoIngrediente.style.display = formNuevoIngrediente.style.display === 'none' ? 'block' : 'none';
        });
        // --- Lógica para guardar nuevo ingrediente con AJAX ---
        const btnGuardarNuevoIng = document.getElementById('guardar-nuevo-ingrediente');
        btnGuardarNuevoIng.addEventListener('click', () => {
            const nombre = document.getElementById('nuevo-ing-nombre').value;
            const unidad = document.getElementById('nuevo-ing-unidad').value;
            const condimento = document.getElementById('nuevo-ing-condimento').checked ? 1 : 0;
            if (!nombre || !unidad) {
                alert('El nombre y la unidad de medida son obligatorios.');
                return;
            }
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('unidad_medida', unidad);
            formData.append('condimento', condimento);
            fetch('agregar_ingrediente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newOption = document.createElement('option');
                    newOption.value = data.ingrediente.id;
                    newOption.text = data.ingrediente.nombre;
                    newOption.setAttribute('data-unidad', unidad);
                    selectorIngredientes.add(newOption);
                    selectorIngredientes.value = data.ingrediente.id;
                    unidadMedidaDisplay.value = unidad;
                    alert('Ingrediente agregado con éxito.');
                    formNuevoIngrediente.style.display = 'none';
                    document.getElementById('nuevo-ing-nombre').value = '';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error de conexión al guardar el ingrediente.');
            });
        });
    });
    </script>
</body>
</html> 