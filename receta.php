<?php
session_start();
require_once 'config.php';
$ID_RECETA = $_GET["id"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recetas - Vive tu Sabor</title>
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
                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda'): ?>
                    <a href="mis_productos.php">Mis Productos</a>
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
      <h1><span class="light">Vive tu sabor</span> <span class="bold">Recetas</span></h1>
    </div>
  </header>
<?php
  // Obtener la información de la receta
  $receta = getMultipleResults("SELECT * FROM receta WHERE id_receta = $ID_RECETA");
  
  if($receta && count($receta) > 0) {
    $receta_actual = $receta[0];
    
    // Obtener los ingredientes asociados a la receta
    $resultado = getMultipleResults("SELECT 
      r.id_receta,
      r.nombre AS nombre_receta,
      r.descripcion,
      r.ruta_imagen,
      r.veces_pedida,
      r.pasos,
      i.id_ingrediente,
      i.nombre AS nombre_ingrediente,
      i.unidad_medida,
      ir.cantidad
      FROM receta r
      LEFT JOIN ingrediente_por_receta ir ON r.id_receta = ir.receta_id
      LEFT JOIN ingrediente i ON ir.ingrediente_id = i.id_ingrediente
      WHERE r.id_receta = $ID_RECETA");

    if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda') {
        // Verificar si la receta ya está en el menú de la tienda
        $conexion = getDBConnection();
        $stmt_check = $conexion->prepare("SELECT 1 FROM tienda_receta WHERE tienda_id = ? AND receta_id = ?");
        $stmt_check->bind_param("ii", $_SESSION['usuario_id'], $ID_RECETA);
        $stmt_check->execute();
        $stmt_check->store_result();
        $ya_en_menu = $stmt_check->num_rows > 0;
        $stmt_check->close();
        $conexion->close();
    }
?>

  <main class="contenido-principal">
    <div class="posicion">
      <section class="recetas">
        <h2><?php echo htmlspecialchars($receta_actual['nombre']); ?></h2>

        <article class="receta-foto">
          <div class="imagen_en_receta">
            <img class="imagen_en_receta" src="<?php echo htmlspecialchars($receta_actual['ruta_imagen']); ?>" alt="Imagen de receta">
          </div>

          <h3>DESCRIPCIÓN</h3>
          <p><?php echo htmlspecialchars($receta_actual['descripcion']); ?></p>

          <h3>INGREDIENTES</h3>
          <ul>
            <?php 
            if($resultado) {
              foreach ($resultado as $fila) {
                if($fila['nombre_ingrediente']) {
                  echo "<li>" . htmlspecialchars($fila['nombre_ingrediente']);
                  if($fila['cantidad']) {
                    echo " - " . htmlspecialchars($fila['cantidad']) . " " . htmlspecialchars($fila['unidad_medida']);
                  }
                  echo "</li>";
                }
              }
            }
            ?>
          </ul>

          <h3>PASOS</h3>
          <?php
          if($receta_actual['pasos']) {
            $pasos = explode('||', $receta_actual['pasos']);
            foreach($pasos as $index => $paso) {
              echo "<h4>Paso " . ($index + 1) . "</h4>";
              echo "<p>" . nl2br(htmlspecialchars($paso)) . "</p>";
            }
          }
          ?>

          <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'tienda'): ?>
              <?php if (!empty($ya_en_menu)): ?>
                  <div class="mensaje error" style="margin: 1em 0;">La Receta seleccionada ya se encuentra en su Menú</div>
              <?php else: ?>
              <a href="agregar_receta.php?id=<?php echo $ID_RECETA; ?>" class="btn">Agregar a mi Menú</a>
              <?php endif; ?>
          <?php endif; ?>
        </article>
      </section>

      <aside class="barra-derecha">
        <?php
          // Obtener localidad del usuario si está logueado
          $localidad_usuario = null;
          if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'cliente') {
            $conexion = getDBConnection();
            $stmt_usuario = $conexion->prepare("SELECT localidad FROM usuario WHERE id = ?");
            $stmt_usuario->bind_param("i", $_SESSION['usuario_id']);
            $stmt_usuario->execute();
            $stmt_usuario->bind_result($localidad_usuario);
            $stmt_usuario->fetch();
            $stmt_usuario->close();
          } else {
            $conexion = getDBConnection();
          }

          // Obtener tiendas asociadas a la receta
          $query_tiendas = "SELECT t.id_tienda, t.razon_social, t.descripcion, t.rubro, t.cantidad_ventas, t.calificacion, u.direccion, u.localidad, u.ruta_imagen as ruta_imagen_usuario, cp.provincia, cp.localidad as nombre_localidad, cp.id as id_postal FROM tienda t JOIN usuario u ON t.id_tienda = u.id LEFT JOIN codigo_postal cp ON u.localidad = cp.id JOIN tienda_receta tr ON t.id_tienda = tr.tienda_id WHERE tr.receta_id = ? AND tr.disponible = 1";
          $stmt_tiendas = $conexion->prepare($query_tiendas);
          $stmt_tiendas->bind_param("i", $ID_RECETA);
          $stmt_tiendas->execute();
          $result_tiendas = $stmt_tiendas->get_result();
          $tiendas_receta = $result_tiendas->fetch_all(MYSQLI_ASSOC);
          $stmt_tiendas->close();
          $conexion->close();
        ?>
        <section class="tiendas-asociadas-receta">
          <h3>Tiendas que ofrecen esta receta</h3>
          <div class="carousel">
            <div class="carousel-item active" data-orden="cercanas">Tiendas Cercanas</div>
            <div class="carousel-item" data-orden="mejor-calificadas">Mejor Calificadas</div>
            <div class="carousel-item" data-orden="record-ventas">Record en Ventas</div>
          </div>
          <div class="grid-recetas" id="tiendas-receta-grid">
            <?php foreach ($tiendas_receta as $tienda): ?>
              <div class="receta-foto tienda-card-grid" data-localidad="<?php echo $tienda['id_postal']; ?>" data-calificacion="<?php echo $tienda['calificacion']; ?>" data-ventas="<?php echo $tienda['cantidad_ventas']; ?>">
                <?php $ruta_imagen = !empty($tienda['ruta_imagen_usuario']) ? $tienda['ruta_imagen_usuario'] : 'img/default-store.png'; ?>
                <img src="<?php echo htmlspecialchars($ruta_imagen); ?>" alt="Logo de la tienda" class="tienda-imagen" onerror="this.src='img/default-store.png'">
                <div class="tienda-info">
                  <h3><?php echo htmlspecialchars($tienda['razon_social']); ?></h3>
                  <p class="tienda-rubro"><?php echo htmlspecialchars($tienda['rubro']); ?></p>
                  <div class="tienda-stats">
                    <span><i class="fas fa-star"></i> <?php echo number_format($tienda['calificacion'], 1); ?></span>
                    <span><i class="fas fa-shopping-cart"></i> <?php echo $tienda['cantidad_ventas']; ?></span>
                  </div>
                  <p class="tienda-direccion">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($tienda['direccion']); ?><br>
                    <?php echo htmlspecialchars($tienda['nombre_localidad'] . ', ' . $tienda['provincia']); ?>
                  </p>
                </div>
                <div class="acciones-tarjeta">
                  <a href="tienda.php?id=<?php echo $tienda['id_tienda']; ?>" class="btn-agregar-tarjeta">Ver Tienda</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const carouselItems = document.querySelectorAll('.carousel-item');
              const grid = document.getElementById('tiendas-receta-grid');
              const cards = Array.from(grid.querySelectorAll('.tienda-card-grid'));
              const localidadUsuario = <?php echo json_encode($localidad_usuario); ?>;
              carouselItems.forEach(item => {
                item.addEventListener('click', function() {
                  carouselItems.forEach(i => i.classList.remove('active'));
                  this.classList.add('active');
                  let orden = this.getAttribute('data-orden');
                  let sorted = [...cards];
                  if (orden === 'cercanas' && localidadUsuario) {
                    sorted.sort((a, b) => Math.abs(a.dataset.localidad - localidadUsuario) - Math.abs(b.dataset.localidad - localidadUsuario));
                  } else if (orden === 'mejor-calificadas') {
                    sorted.sort((a, b) => b.dataset.calificacion - a.dataset.calificacion);
                  } else if (orden === 'record-ventas') {
                    sorted.sort((a, b) => b.dataset.ventas - a.dataset.ventas);
                  }
                  grid.innerHTML = '';
                  sorted.forEach(card => grid.appendChild(card));
                });
              });
            });
          </script>
        </section>
      </aside>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-contenido">
      <div class="footer-enlaces">
        <a href="#">Inicio</a>
        <a href="#">Recetas</a>
        <a href="#">Contacto</a>
        <a href="#">Privacidad</a>
      </div>
      <p>&copy; 2025 Vive tu Sabor. Todos los derechos reservados.</p>
    </div>
  </footer>
<?php
  } else {
    echo "<p>Receta no encontrada</p>";
  }
?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const perfilMenu = document.querySelector('.perfil-menu');
        const perfil = document.querySelector('.perfil');

        perfil.addEventListener('mouseenter', function() {
            perfilMenu.querySelector('.perfil-dropdown').style.display = 'block';
        });

        perfil.addEventListener('mouseleave', function() {
            perfilMenu.querySelector('.perfil-dropdown').style.display = 'none';
        });
    });
  </script>
</body>
</html>
