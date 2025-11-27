<?php
session_start();
require_once 'config.php';

// Redirigir si no está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$ingredientes = [];

// Obtener ingredientes de la base de datos
try {
    $conexion = getDBConnection();
    $query_ingredientes = "SELECT id_ingrediente, nombre, unidad_medida FROM ingrediente ORDER BY nombre ASC";
    $result_ingredientes = mysqli_query($conexion, $query_ingredientes);
    while ($row = mysqli_fetch_assoc($result_ingredientes)) {
        $ingredientes[] = $row;
    }
} catch (Exception $e) {
    $error = 'Error al cargar los ingredientes: ' . $e->getMessage();
}

// Procesar el formulario de creación de receta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_receta'])) {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $pasos = isset($_POST['pasos']) ? implode('||', $_POST['pasos']) : '';
    $ingredientes_receta = $_POST['ingredientes'] ?? [];
    
    // Validación básica
    if (empty($nombre) || empty($descripcion) || empty($pasos) || empty($ingredientes_receta)) {
        $error = 'Todos los campos son obligatorios, incluyendo al menos un paso y un ingrediente.';
    } else {
        mysqli_begin_transaction($conexion);

        try {
            // Manejo de la imagen
            $ruta_imagen = 'receta/default.jpg'; // Imagen por defecto
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

            // Insertar en la tabla 'receta'
            $id_tienda = $_SESSION['usuario_id'];
            $query_receta = "INSERT INTO receta (nombre, descripcion, pasos, ruta_imagen, tienda_id) VALUES (?, ?, ?, ?, ?)";
            $stmt_receta = mysqli_prepare($conexion, $query_receta);
            mysqli_stmt_bind_param($stmt_receta, "ssssi", $nombre, $descripcion, $pasos, $ruta_imagen, $id_tienda);
            mysqli_stmt_execute($stmt_receta);
            $id_receta = mysqli_insert_id($conexion);

            // Insertar en 'ingrediente_por_receta'
            $query_ing_rec = "INSERT INTO ingrediente_por_receta (receta_id, ingrediente_id, cantidad) VALUES (?, ?, ?)";
            $stmt_ing_rec = mysqli_prepare($conexion, $query_ing_rec);
            
            foreach ($ingredientes_receta as $ing) {
                $id_ingrediente = $ing['id'];
                $cantidad = $ing['cantidad'];
                mysqli_stmt_bind_param($stmt_ing_rec, "iid", $id_receta, $id_ingrediente, $cantidad);
                mysqli_stmt_execute($stmt_ing_rec);
            }

            // Solo asociar en tienda_receta si es tienda
            if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda') {
            $query_tienda_receta = "INSERT INTO tienda_receta (tienda_id, receta_id, disponible) VALUES (?, ?, 1)";
            $stmt_tienda_receta = mysqli_prepare($conexion, $query_tienda_receta);
            mysqli_stmt_bind_param($stmt_tienda_receta, "ii", $id_tienda, $id_receta);
            mysqli_stmt_execute($stmt_tienda_receta);
            }

            mysqli_commit($conexion);
            
            // Redireccionar a agregar_receta.php con un mensaje de éxito
            if ($_SESSION['tipo_usuario'] === 'tienda') {
                $_SESSION['success_message'] = '¡Receta creada con éxito! Ahora puedes configurar los productos para tu menú.';
                header('Location: agregar_receta.php?id=' . $id_receta);
                exit();
            }

            $success = '¡Receta creada con éxito!';

        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $error = 'Error al crear la receta: ' . $e->getMessage();
        }
    }
}

// Cerrar conexión si sigue abierta
if (isset($conexion) && $conexion->ping()) {
    mysqli_close($conexion);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Receta - Vive tu Sabor</title>
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

        /* Estilos Generales del Formulario */
        .formulario-perfil {
            background-color: #fdfdff;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #e6e6e6;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Separación de Secciones */
        .campo-formulario {
            margin-bottom: 25px;
            padding-top: 25px;
            border-top: 1px solid #f0f0f0;
        }
        .campo-formulario:first-child {
            border-top: none;
            padding-top: 0;
        }

        /* Etiquetas y Títulos */
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

        /* Campos de Entrada, Selección y Texto */
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

        /* Botones */
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
        button[name="crear_receta"] {
            width: 100%;
            font-size: 18px;
            padding: 15px;
        }
        
        /* Contenedores Dinámicos (Pasos e Ingredientes) */
        .ingrediente-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .paso-container {
            margin-bottom: 20px;
        }

        .paso-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .paso-header label {
            margin-bottom: 0;
            font-weight: bold;
        }

        .paso-container textarea {
            min-height: 80px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .btn-remover {
            background-color: #dc3545;
            padding: 8px 12px;
            font-size: 1em;
            margin-top: 0;
        }
        .btn-remover:hover {
            background-color: #c82333;
        }

        /* Sección de Añadir Ingredientes */
        .ingrediente-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ingrediente-container select { flex: 3; min-width: 200px; }
        .ingrediente-container input[type="number"] { flex: 2; min-width: 150px; }
        .ingrediente-container input[readonly] {
            flex: 1;
            min-width: 80px;
            border: none;
            background-color: #f0f0f0;
            text-align: center;
        }
        .ingrediente-container .btn-submit { flex: 1; margin-top: 0; }

        /* Formulario de Nuevo Ingrediente */
        #form-nuevo-ingrediente {
            display: none;
            border: 1px solid #e0e0e0;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            background-color: #f5f5f5;
        }
        #form-nuevo-ingrediente input, #form-nuevo-ingrediente select {
            margin-bottom: 10px;
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
            <h1><span class="light">Vive tu sabor</span> <span class="bold">Crear Receta</span></h1>
        </div>
    </header>

    <main class="contenido-principal">
        <div class="perfil-container">
            <h2>Crear Nueva Receta</h2>

            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mensaje success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form action="crear_receta.php" method="POST" enctype="multipart/form-data" class="formulario-perfil">
                <div class="campo-formulario">
                    <label for="nombre">Nombre de la Receta:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="campo-formulario">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required class="comentario-textarea"></textarea>
                </div>

                <div class="campo-formulario">
                    <label for="imagen">Imagen de la Receta:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                </div>

                <div class="campo-formulario">
                    <h3>Pasos de la Receta</h3>
                    <div id="pasos-wrapper">
                        <div class="paso-container">
                            <div class="paso-header">
                                <label>Paso 1:</label>
                            </div>
                            <textarea name="pasos[]" rows="2" required></textarea>
                        </div>
                    </div>
                    <button type="button" id="agregar-paso" class="btn-submit">Agregar Paso</button>
                </div>

                <div class="campo-formulario">
                    <h3>Ingredientes</h3>
                    <div id="ingredientes-receta-wrapper">
                        <!-- Los ingredientes seleccionados aparecerán aquí -->
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

                <button type="submit" name="crear_receta" class="btn-submit">Crear Receta</button>
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
        let pasoCount = 1;

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
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar el nuevo ingrediente al selector
                    const newOption = document.createElement('option');
                    newOption.value = data.ingrediente.id;
                    newOption.text = data.ingrediente.nombre;
                    newOption.setAttribute('data-unidad', data.ingrediente.unidad_medida);
                    selectorIngredientes.add(newOption);
                    // Seleccionar el nuevo ingrediente
                    selectorIngredientes.value = data.ingrediente.id;
                    // Mostrar la unidad de medida automáticamente
                    unidadMedidaDisplay.value = data.ingrediente.unidad_medida;
                    alert('Ingrediente agregado con éxito.');
                    // Ocultar y limpiar el formulario
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