<?php
include 'config.php';

// Obtener conexión
$conexion = getDBConnection();

// Crear tabla de calificaciones
$sql_calificaciones = "CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT,
    usuario_id INT,
    calificacion INT CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receta_id) REFERENCES receta(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
)";

// Crear tabla de detalles_pedido
$sql_detalles_pedido = "CREATE TABLE IF NOT EXISTS detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    receta_id INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (receta_id) REFERENCES receta(id) ON DELETE CASCADE
)";

// Crear tabla de contactos
$sql_contactos = "CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    localidad VARCHAR(100),
    comentario TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Ejecutar las consultas
if ($conexion->query($sql_calificaciones) === TRUE) {
    echo "Tabla 'calificaciones' creada correctamente<br>";
} else {
    echo "Error al crear tabla 'calificaciones': " . $conexion->error . "<br>";
}

if ($conexion->query($sql_detalles_pedido) === TRUE) {
    echo "Tabla 'detalles_pedido' creada correctamente<br>";
} else {
    echo "Error al crear tabla 'detalles_pedido': " . $conexion->error . "<br>";
}

if ($conexion->query($sql_contactos) === TRUE) {
    echo "Tabla 'contactos' creada correctamente<br>";
} else {
    echo "Error al crear tabla 'contactos': " . $conexion->error . "<br>";
}

$conexion->close();
echo "<br>Proceso completado.";
?> 