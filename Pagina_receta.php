<!DOCTYPE html>
<html lang="es">
<?php
  include('conexion.php');
  $ID_RECETA = $_GET["id"];
?>
<head>
  <script src="https://kit.fontawesome.com/826120ea46.js" crossorigin="anonymous"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recetas - Vive tu Sabor</title>
  <link rel="stylesheet" href="estilos.css" />
</head>
<body>
  <header class="header">
    <div class="top-bar">
      <nav class="nav">
        <ul>
          <li><a href="">Inicio</a></li>
          <li><a href="index.html">Recetas</a></li>
          <li><a href="#">Consejos</a></li>
      
            <li> <a href="carrito.html" id="carrito-nav" title="Ver Carrito de Compras">
              <i class="fa-solid fa-cart-plus"></i>
           <span class="contador-carrito" id="contador-carrito-items">0</span> </a>
          </li>
        </ul>
      </nav>
    </div>
    <div class="fondito">
      <h1><span class="light">Vive tu sabor</span> <span class="bold">Recetas</span></h1>
    </div>
  </header>
<?php

    SELECT producto *, receta_producto.producto_id
    FROM receta_producto
    JOIN producto ON receta_producto.producto_id = producto.id_producto;

  $receta = mysqli_query($conexion, "SELECT * FROM receta WHERE id_receta = $ID_RECETA");
  $productos = mysqli_query($conexion, "SELECT * FROM receta_producto WHERE receta_id = $ID_RECETA");


    if($receta && $productos){
        $datos = mysqli_fetch_assoc($receta);

        SELECT producto*, receta_producto.producto_id
        FROM receta_producto
        JOIN producto ON     
?>


  <main class="contenido-principal">
    <div class="posicion">
      <section class="recetas">
        <h2>Spaghetti al pesto</h2>

<!--
  <div class="control-carrito-tarjeta">
    <div class="info-precio-tarjeta">
      <span class="etiqueta-precio">Precio Kit:</span>
      <span class="valor-precio">$1500</span> </div>
    <div class="acciones-tarjeta">
      <div class="control-cantidad-tarjeta">
        <button type="button" class="btn-cantidad-tarjeta menos" aria-label="Disminuir cantidad">-</button>
        <input type="number" class="input-cantidad-tarjeta" value="1" min="1" aria-label="Cantidad">
        <button type="button" class="btn-cantidad-tarjeta mas" aria-label="Aumentar cantidad">+</button>
      </div>
      <button type="button" class="btn-agregar-tarjeta">Agregar</button>
    </div>
  </div>
  </article>

    <div class="info-precio-tarjeta">
      <span class="etiqueta-precio">Precio Kit:</span>
      <span class="valor-precio">$1500</span> </div>
    <div class="acciones-tarjeta">
      <div class="control-cantidad-tarjeta">
        <button type="button" class="btn-cantidad-tarjeta menos" aria-label="Disminuir cantidad">-</button>
        <input type="number" class="input-cantidad-tarjeta" value="1" min="1" aria-label="Cantidad">
        <button type="button" class="btn-cantidad-tarjeta mas" aria-label="Aumentar cantidad">+</button>
      </div>
      <button type="button" class="btn-agregar-tarjeta">Agregar</button>
    </div>
  </div>
-->
<article class = "receta-foto">
    <div class = "imagen_en_receta">
            <img src="<?php echo ($datos['ruta_imagen']); ?>" alt="Imagen de receta 2">
    </div>

            <h3>INGREDIENTES</h3>
            <p><?php echo ?> </p>
            <h3>PASO 1</h3>
            <p>Poner a hervir el agua</p>
            <h3>Paso 2</h3>
            <p>Poner los fiedos en el agua hirviendo</p>
    <div class ="contenedor_productos">
        <div class = "producto">
            <img src="receta1.jpg" alt="ajo">
            <span>ajo</span>
        </div>
        <div class = "producto">
            <img src="receta2.jpg" alt="fideos">
            <span>fideos</span>
        </div>
        <div class = "producto">
            <img src="receta3.jpg" alt="albahaca">
            <span>albahaca</span>
        </div>
    </div>

            

            <a href="#" class="btn">agregar productos</a>
            <div class="control-carrito-tarjeta">
          </article>
<?php
         }
?>

      <aside class="barra-derecha">

        <section>

 <!--          <div class="mi-pedido-card">

  <div class="pedido-header">

    <h2 class="titulo-pedido">Mi pedido</h2>

    <button type="button" class="boton-aceptar">Editar</button>

  </div>



  <div class="pedido-items">

    <div class="item-pedido">

      <div class="item-info">

        <input type="checkbox" id="item1" name="item1" class="item-checkbox">

        <label for="item1" class="item-descripcion">Facturas Surtidas Por Docena</label>

      </div>

      <span class="item-precio">$9.500</span>

    </div>

    </div>



  <div class="pedido-subtotal">

    <span>Subtotal</span>

    <span class="subtotal-monto">$9.500</span>

  </div>



  <button type="button" class="boton-continuar">Continuar</button>

</div>



        </section>

-->

        <section class="populares">
          <h3>Otras recetas</h3>
          <ul class="lista-recetas">
            <li>
              <img src="popular1.jpg" alt="Receta popular 1">
              <span>Arroz con vegetales</span>
            </li>
            <li>
              <img src="popular2.jpg" alt="Receta popular 2">
              <span>Lasaña vegetariana</span>
            </li>
            <li>
              <img src="popular3.jpg" alt="Receta popular 3">
              <span>Wok vegetariano</span>
               </li>
               <li>
              <img src="popular4.jpg" alt="Receta popular 4">
              <span>Ratatouille</span>
            </li>
           
          </ul>
        </section>



  <footer class="footer">
    <div class="footer-contenido">
      <div class="footer-enlaces">
        <a href="#">Inicio</a>
        <a href="#">Recetas</a>
        <a href="#">Contacto</a>
        <a href="#">Privacidad</a>
      </div>
      
<!--el &copy; hace la C con circulo -->

      <p>&copy; 2025 Vive tu Sabor. Todos los derechos reservados.</p>
    </div>
  </footer>
</body>
</html>
