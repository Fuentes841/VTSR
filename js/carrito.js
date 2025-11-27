document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el carrito si no existe
    if (!localStorage.getItem('carrito')) {
        localStorage.setItem('carrito', JSON.stringify({ recetas: [], productos: [] }));
    }

    // Actualizar contador del carrito
    actualizarContadorCarrito();

    // Agregar event listeners a los botones de cantidad
    document.querySelectorAll('.btn-cantidad-tarjeta').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.input-cantidad-tarjeta');
            let valor = parseInt(input.value);

            if (this.classList.contains('menos')) {
                valor = Math.max(1, valor - 1);
            } else {
                valor = valor + 1;
            }

            input.value = valor;
        });
    });

    // Agregar event listeners a los botones de agregar al carrito
    document.querySelectorAll('.btn-agregar-tarjeta').forEach(btn => {
        btn.addEventListener('click', function() {
            const receta = this.closest('.receta-foto');
            const id = receta.dataset.kitId;
            const nombre = receta.querySelector('h3').textContent;
            const cantidad = parseInt(receta.querySelector('.input-cantidad-tarjeta').value);
            const precio = parseFloat(receta.querySelector('.valor-precio').textContent.replace('$', '').replace(',', ''));

            agregarAlCarrito({
                id: id,
                nombre: nombre,
                cantidad: cantidad,
                precio: precio
            });

            // Mostrar mensaje de éxito
            mostrarMensaje('Producto agregado al carrito');
        });
    });

    // Event listeners para el carrito
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            actualizarSubtotal();
        });
    });

    // Botón continuar del carrito
    const botonContinuar = document.querySelector('.boton-continuar');
    if (botonContinuar) {
        botonContinuar.addEventListener('click', function() {
            const itemsSeleccionados = document.querySelectorAll('.item-checkbox:checked');
            if (itemsSeleccionados.length === 0) {
                mostrarMensaje('Por favor selecciona al menos un producto', 'error');
                return;
            }
            window.location.href = 'checkout.php';
        });
    }

    document.querySelectorAll('.agregar-carrito').forEach(btn => {
        btn.addEventListener('click', function() {
            const datos = {
                tienda_id: this.dataset.tiendaId,
                tienda_nombre: this.dataset.tiendaNombre,
                receta_id: this.dataset.recetaId,
                receta_nombre: this.dataset.recetaNombre,
                receta_foto: this.dataset.recetaFoto,
                porciones: parseInt(this.dataset.porciones),
                ingredientes: JSON.parse(this.dataset.ingredientes),
                editable: true // Para saber si se puede editar
            };

            const agregado = agregarRecetaAlCarrito(datos);
            
            if (agregado) {
                window.location.href = 'carrito.php';
            }
        });
    });
});

// Función para agregar al carrito
function agregarAlCarrito(producto) {
    let carrito = JSON.parse(localStorage.getItem('carrito'));
    
    // Verificar si el producto ya está en el carrito
    const index = carrito.productos.findIndex(item => item.id === producto.id);
    
    if (index !== -1) {
        // Actualizar cantidad si ya existe
        carrito.productos[index].cantidad += producto.cantidad;
    } else {
        // Agregar nuevo producto
        carrito.productos.push(producto);
    }
    
    localStorage.setItem('carrito', JSON.stringify(carrito));
    actualizarContadorCarrito();
}

// Función para actualizar el contador del carrito
function actualizarContadorCarrito() {
    const carrito = JSON.parse(localStorage.getItem('carrito'));
    const contador = document.getElementById('contador-carrito-items');
    if (contador) {
        contador.textContent = carrito.productos.length;
    }
}

// Función para actualizar el subtotal
function actualizarSubtotal() {
    const itemsSeleccionados = document.querySelectorAll('.item-checkbox:checked');
    let subtotal = 0;

    itemsSeleccionados.forEach(checkbox => {
        const precio = parseFloat(checkbox.closest('.item-pedido').querySelector('.item-precio').textContent.replace('$', '').replace(',', ''));
        subtotal += precio;
    });

    const subtotalElement = document.querySelector('.subtotal-monto');
    if (subtotalElement) {
        subtotalElement.textContent = '$' + subtotal.toFixed(2);
    }
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo = 'success') {
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.textContent = mensaje;
    
    document.body.appendChild(mensajeDiv);
    
    // Animar entrada
    setTimeout(() => {
        mensajeDiv.style.opacity = '1';
        mensajeDiv.style.transform = 'translateY(0)';
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        mensajeDiv.style.opacity = '0';
        mensajeDiv.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            document.body.removeChild(mensajeDiv);
        }, 300);
    }, 3000);
}

// Agregar estilos para los mensajes
const style = document.createElement('style');
style.textContent = `
    .mensaje {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.3s ease;
    }
    
    .mensaje.success {
        background-color: #4CAF50;
    }
    
    .mensaje.error {
        background-color: #f44336;
    }
`;
document.head.appendChild(style);

// Función para agregar receta al carrito
function agregarRecetaAlCarrito(datos) {
    let carrito = JSON.parse(localStorage.getItem('carrito')) || { recetas: [], productos: [] };

    // Check 1: Different store
    if (carrito.recetas.length > 0 && carrito.recetas[0].tienda_id !== datos.tienda_id) {
        alert('Solo puedes agregar recetas de la misma tienda en un pedido.');
        return false; // No se agregó
    }

    // Check 2: Duplicate recipe
    const yaExiste = carrito.recetas.some(item => item.receta_id === datos.receta_id);
    if (yaExiste) {
        alert('La Receta seleccionada ya se encuentra agregada a su Carrito');
        return false; // No se agregó
    }

    // Agregar la receta
    carrito.recetas.push(datos);

    // Recalcular productos fusionados
    carrito.productos = fusionarIngredientes(carrito.recetas);

    localStorage.setItem('carrito', JSON.stringify(carrito));
    return true; // Se agregó correctamente
}

// Función para fusionar ingredientes de todas las recetas
function fusionarIngredientes(recetas) {
    const productosMap = {};
    recetas.forEach(receta => {
        receta.ingredientes.forEach(ing => {
            // Clave única por id_ingrediente y marca (si aplica)
            const clave = ing.id_ingrediente + '|' + (ing.marca || '');
            if (!productosMap[clave]) {
                productosMap[clave] = { ...ing };
                // Multiplicar cantidad por porciones de la receta
                productosMap[clave].cantidad_total = ing.cantidad * receta.porciones;
                productosMap[clave].porciones = receta.porciones;
            } else {
                // Sumar cantidades según porciones
                productosMap[clave].cantidad_total += ing.cantidad * receta.porciones;
            }
        });
    });
    // Convertir a array
    return Object.values(productosMap);
} 