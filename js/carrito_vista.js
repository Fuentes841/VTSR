function renderCarrito() {
  const contenedor = document.getElementById('carrito-contenedor');
  contenedor.innerHTML = '';
  let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
  if (!carrito.productos || carrito.productos.length === 0) {
    contenedor.innerHTML = '<p>El carrito está vacío.</p>';
    // Ocultar botones si el carrito está vacío
    document.getElementById('agregar-receta').style.display = 'none';
    document.getElementById('realizar-pedido').style.display = 'none';
    return;
  } else {
    // Mostrar botones si hay items
    document.getElementById('agregar-receta').style.display = 'inline-block';
    document.getElementById('realizar-pedido').style.display = 'inline-block';
  }

  // Controles de porciones para cada receta
  let controlesPorcionesHtml = '';
  carrito.recetas.forEach((receta, idx) => {
    controlesPorcionesHtml += `
      <div class=\"receta-header-carrito\">\n        <img src=\"${receta.receta_foto}\" alt=\"${receta.receta_nombre}\">\n        <div class=\"receta-header-info\">\n          <h3>${receta.receta_nombre}</h3>\n          <div class=\"porciones-control\">\n            <button type=\"button\" class=\"btn-porciones menos\" data-idx=\"${idx}\" ${receta.editable ? '' : 'disabled'}>-</button>\n            <input type=\"number\" min=\"1\" value=\"${receta.porciones}\" class=\"input-porciones\" data-idx=\"${idx}\" ${receta.editable ? '' : 'readonly'}>\n            <button type=\"button\" class=\"btn-porciones mas\" data-idx=\"${idx}\" ${receta.editable ? '' : 'disabled'}>+</button>\n          </div>\n        </div>\n        <button type=\"button\" class=\"btn-eliminar-receta\" data-idx=\"${idx}\" title=\"Eliminar receta\">&times;</button>\n      </div>\n    `;
  });
  if (controlesPorcionesHtml) {
    controlesPorcionesHtml = `<div class=\"recetas-header-grid\">${controlesPorcionesHtml}</div>`;
  }

  let granTotal = 0;
  let tableRowsHtml = '';

  carrito.productos.forEach((ing, idx) => {
    let precio = 0;
    let unidadesAComprar = 0;
    // Cálculo igual que antes, pero usando cantidad_total
    if (ing.peso_unidad === null || ing.peso_unidad === undefined || ing.peso_unidad === 0) {
      precio = ing.cantidad_total * ing.precio;
      unidadesAComprar = ing.cantidad_total;
    } else {
      unidadesAComprar = Math.ceil(ing.cantidad_total / ing.peso_unidad);
      precio = unidadesAComprar * ing.precio;
    }
    granTotal += precio;
    tableRowsHtml += `
      <tr>
        <td>${ing.nombre}</td>
        <td>${ing.marca ? ing.marca : '-'}</td>
        <td>${ing.cantidad_total.toFixed(2)} ${ing.unidad_medida || ''}</td>
        <td>${(ing.peso_unidad === null || ing.peso_unidad === undefined || ing.peso_unidad === 0) ? '(a granel)' : `${ing.peso_unidad} ${ing.unidad_medida || ''}`}</td>
        <td>${unidadesAComprar}</td>
        <td>${ing.precio ? ing.precio.toFixed(2) : '-'}</td>
        <td class="precio-ingrediente" data-i="${idx}">${precio.toFixed(2)}</td>
        <td><button type="button" class="btn-eliminar-producto" data-idx="${idx}" title="Eliminar producto">&times;</button></td>
      </tr>
    `;
    ing.unidades_a_comprar = unidadesAComprar;
  });

  const headerHtml = `
    <div class="carrito-main-header">
      <span class="tienda-info-header">Pedido a ${carrito.recetas[0]?.tienda_nombre || ''}</span>
      <span class="gran-total-header">Total del Pedido: ${granTotal.toFixed(2)}</span>
    </div>
  `;

  const tablaHtml = `
    <table class=\"carrito-productos-table\">\n      <thead>\n        <tr>\n          <th>Ingrediente</th>\n          <th>Marca</th>\n          <th>Cantidad total requerida</th>\n          <th>Cantidad por Unidad</th>\n          <th>Unidades/kg a comprar</th>\n          <th>Precio por kg/unidad</th>\n          <th>Total</th>\n          <th>Eliminar</th>\n        </tr>\n      </thead>\n      <tbody>\n      ${tableRowsHtml}\n      </tbody>\n    </table>\n  `;

  contenedor.innerHTML = headerHtml + controlesPorcionesHtml + tablaHtml;
}

function eliminarReceta(idx) {
  let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  carrito.splice(idx, 1);
  localStorage.setItem('carrito', JSON.stringify(carrito));
  renderCarrito();
}

function actualizarPrecios(idx) {
  let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
  let receta = carrito[idx];
  let form = document.querySelector(`form[data-idx="${idx}"]`);
  let porciones = parseInt(form.querySelector('.input-porciones').value);
  receta.porciones = porciones;

  // Ingredientes normales y condimentos
  receta.ingredientes.forEach((ing, i) => {
    let precio = 0;
    let unidadesAComprar = 0;

    if (ing.condimento != 1) {
      if (ing.peso_unidad === null || ing.peso_unidad === undefined || ing.peso_unidad === 0) {
        precio = ing.cantidad * porciones * ing.precio;
        unidadesAComprar = (ing.cantidad * porciones).toFixed(2);
      } else {
        unidadesAComprar = Math.ceil((ing.cantidad * porciones) / ing.peso_unidad);
        precio = unidadesAComprar * ing.precio;
      }
      // Actualizar el precio en el DOM
      let td = form.querySelector(`.precio-ingrediente[data-i="${i}"][data-cond="0"]`);
      if (td) td.textContent = precio.toFixed(2);
    } else {
      let checkbox = form.querySelector(`.condimento-checkbox[data-i="${i}"]`);
      if (checkbox && checkbox.checked) {
        ing.agregado = true;
        let td = form.querySelector(`.precio-ingrediente[data-i="${i}"][data-cond="1"]`);
        if (td) td.textContent = ing.precio.toFixed(2);
      } else {
        ing.agregado = false;
        let td = form.querySelector(`.precio-ingrediente[data-i="${i}"][data-cond="1"]`);
        if (td) td.textContent = '0.00';
      }
    }
  });

  // Recalcular el total
  let total = 0;
  receta.ingredientes.forEach((ing) => {
    if (ing.condimento != 1) {
      if (ing.peso_unidad === null || ing.peso_unidad === undefined || ing.peso_unidad === 0) {
        total += ing.cantidad * porciones * ing.precio;
      } else {
        total += Math.ceil((ing.cantidad * porciones) / ing.peso_unidad) * ing.precio;
      }
    } else if (ing.agregado !== false) {
      total += ing.precio;
    }
  });
  form.querySelector('.total-precio').textContent = total.toFixed(2);
  carrito[idx] = receta;
  localStorage.setItem('carrito', JSON.stringify(carrito));
}

document.addEventListener('DOMContentLoaded', function() {
  renderCarrito();

  document.getElementById('carrito-contenedor').addEventListener('click', function(e) {
    // Eliminar receta
    if (e.target.classList.contains('btn-eliminar-receta')) {
      let idx = e.target.dataset.idx;
      let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
      carrito.recetas.splice(idx, 1);
      // Recalcular productos fusionados
      carrito.productos = fusionarIngredientes(carrito.recetas);
      localStorage.setItem('carrito', JSON.stringify(carrito));
      renderCarrito();
    }
    // Botones de porciones
    if (e.target.classList.contains('btn-porciones')) {
      const idx = e.target.dataset.idx;
      let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
      let receta = carrito.recetas[idx];
      let currentValue = parseInt(receta.porciones);
      if (e.target.classList.contains('mas')) {
        currentValue++;
      } else if (e.target.classList.contains('menos') && currentValue > 1) {
        currentValue--;
      }
      receta.porciones = currentValue;
      // Recalcular productos fusionados
      carrito.productos = fusionarIngredientes(carrito.recetas);
      localStorage.setItem('carrito', JSON.stringify(carrito));
      renderCarrito();
    }
    // Eliminar producto fusionado
    if (e.target.classList.contains('btn-eliminar-producto')) {
      let idx = e.target.dataset.idx;
      let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
      carrito.productos.splice(idx, 1);
      localStorage.setItem('carrito', JSON.stringify(carrito));
      renderCarrito();
    }
  });

  document.getElementById('carrito-contenedor').addEventListener('change', function(e) {
    if (e.target.classList.contains('input-porciones')) {
      const idx = e.target.dataset.idx;
      let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
      let receta = carrito.recetas[idx];
      let value = parseInt(e.target.value);
      if (value < 1) value = 1;
      receta.porciones = value;
      carrito.productos = fusionarIngredientes(carrito.recetas);
      localStorage.setItem('carrito', JSON.stringify(carrito));
      renderCarrito();
    }
  });

  // Botón agregar receta
  document.getElementById('agregar-receta').addEventListener('click', function() {
    let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
    if (carrito.recetas && carrito.recetas.length > 0) {
      window.location.href = 'tienda.php?id=' + carrito.recetas[0].tienda_id;
    } else {
      window.location.href = 'tiendas.php';
    }
  });

  // Botón realizar pedido
  const btnRealizarPedido = document.getElementById('realizar-pedido');
  btnRealizarPedido.addEventListener('click', function(event) {
    event.preventDefault();

    // Verificar si el usuario está logueado (por variable global PHP inyectada)
    const usuarioLogueado = window.usuarioLogueado || false;
    if (!usuarioLogueado) {
      // Mostrar modal login-widget
      let modal = document.createElement('div');
      modal.id = 'modal-login-widget';
      modal.innerHTML = `
        <div class="mp-modal-overlay"></div>
        <div class="mp-modal-content">
          <h2 style="text-align:center; margin-bottom:10px;">Iniciar Sesión</h2>
          <form id="form-login-widget" class="formulario" method="POST" action="login.php">
            <div class="campo-formulario">
              <label for="login-email">Correo electrónico:</label>
              <input type="email" id="login-email" name="email" required>
            </div>
            <div class="campo-formulario">
              <label for="login-password">Contraseña:</label>
              <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
          </form>
          <p class="registro-link" style="text-align:center; margin-top:10px;">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
          <button id="btn-cerrar-login-widget" style="width:100%; background:#eee; color:#333; border:none; padding:10px; border-radius:6px; font-size:1em; margin-top:10px; cursor:pointer;">Cancelar</button>
        </div>
      `;
      document.body.appendChild(modal);
      // Estilos básicos para el modal
      const style = document.createElement('style');
      style.innerHTML = `
        #modal-login-widget { position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; display:flex; align-items:center; justify-content:center; }
        .mp-modal-overlay { position:absolute; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); }
        .mp-modal-content { position:relative; background:white; border-radius:12px; padding:32px 24px 24px 24px; box-shadow:0 8px 32px rgba(0,0,0,0.18); min-width:320px; max-width:90vw; z-index:2; }
      `;
      document.head.appendChild(style);
      document.getElementById('btn-cerrar-login-widget').onclick = function() {
        document.body.removeChild(modal);
        document.head.removeChild(style);
      };
      // Si se envía el formulario, dejar que el submit normal actúe (irá a login.php)
      return;
    }

    let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };
    if (!carrito.productos || carrito.productos.length === 0) {
      alert('Tu carrito está vacío.');
      return;
    }

    // Obtener datos para el modal
    const tiendaNombre = carrito.recetas[0]?.tienda_nombre || '';
    let granTotal = 0;
    carrito.productos.forEach((ing) => {
      let precio = 0;
      let unidadesAComprar = 0;
      if (ing.peso_unidad === null || ing.peso_unidad === undefined || ing.peso_unidad === 0) {
        precio = ing.cantidad_total * ing.precio;
        unidadesAComprar = ing.cantidad_total;
      } else {
        unidadesAComprar = Math.ceil(ing.cantidad_total / ing.peso_unidad);
        precio = unidadesAComprar * ing.precio;
      }
      granTotal += precio;
      ing.unidades_a_comprar = unidadesAComprar;
    });

    // Crear modal Mercado Pago
    let modal = document.createElement('div');
    modal.id = 'modal-mercadopago';
    modal.innerHTML = `
      <div class=\"mp-modal-overlay\"></div>
      <div class=\"mp-modal-content\">
        <img src=\"img/mp.png\" alt=\"Mercado Pago\" class=\"mp-logo\" style=\"max-width:180px; display:block; margin:0 auto 20px;\">
        <h2 style=\"text-align:center; margin-bottom:10px;\">Pago con Mercado Pago</h2>
        <p style=\"text-align:center; font-size:1.1em; margin-bottom:8px;\"><strong>Tienda:</strong> ${tiendaNombre}</p>
        <p style=\"text-align:center; font-size:1.2em; margin-bottom:20px;\"><strong>Total:</strong> $${granTotal.toFixed(2)}</p>
        <button id=\"btn-pagar-mp\" style=\"width:100%; background:#009ee3; color:white; border:none; padding:12px; border-radius:6px; font-size:1.1em; font-weight:bold; cursor:pointer;\">Pagar</button>
        <button id=\"btn-cerrar-mp\" style=\"width:100%; background:#eee; color:#333; border:none; padding:10px; border-radius:6px; font-size:1em; margin-top:10px; cursor:pointer;\">Cancelar</button>
      </div>
    `;
    document.body.appendChild(modal);

    // Estilos básicos para el modal
    const style = document.createElement('style');
    style.innerHTML = `
      #modal-mercadopago { position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; display:flex; align-items:center; justify-content:center; }
      .mp-modal-overlay { position:absolute; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); }
      .mp-modal-content { position:relative; background:white; border-radius:12px; padding:32px 24px 24px 24px; box-shadow:0 8px 32px rgba(0,0,0,0.18); min-width:320px; max-width:90vw; z-index:2; }
      .mp-logo { margin-bottom: 10px; }
    `;
    document.head.appendChild(style);

    document.getElementById('btn-cerrar-mp').onclick = function() {
      document.body.removeChild(modal);
      document.head.removeChild(style);
    };

    document.getElementById('btn-pagar-mp').onclick = function() {
      document.body.removeChild(modal);
      document.head.removeChild(style);
      // Continuar con la función original de realizar pedido
      btnRealizarPedido.disabled = true;
      btnRealizarPedido.textContent = 'Procesando...';

      // Repetir la lógica original
    let tienda_id = carrito.recetas[0]?.tienda_id;
    let productos = carrito.productos.map(p => ({ ...p, tienda_id, unidades_a_comprar: p.unidades_a_comprar }));
    fetch('procesar_pedido.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(productos)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('¡Pedido realizado con éxito!');
        localStorage.removeItem('carrito');
        window.location.href = 'pedidos.php';
      } else {
        alert('Error al procesar el pedido: ' + data.message);
          btnRealizarPedido.disabled = false;
          btnRealizarPedido.textContent = 'Realizar Pedido';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Hubo un error de conexión al realizar el pedido.');
        btnRealizarPedido.disabled = false;
        btnRealizarPedido.textContent = 'Realizar Pedido';
    });
    };
  });
}); 