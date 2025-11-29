-- phpMyAdmin SQL Dump
-- Estructura actual de las tablas de la base de datos prueba2
-- Extraído de prueba2.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `prueba2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `direccion` varchar(50) NOT NULL,
  `localidad` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  `suspendido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = activo, 1 = suspendido',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mail` (`email`),
  KEY `fk_usuario_codigo_postal` (`localidad`),
  CONSTRAINT `fk_usuario_codigo_postal` FOREIGN KEY (`localidad`) REFERENCES `codigo_postal` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  PRIMARY KEY (`id_cliente`),
  CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`id_cliente`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tienda`
--

CREATE TABLE `tienda` (
  `id_tienda` int(11) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `descripcion` varchar(2000) NOT NULL,
  `rubro` varchar(50) NOT NULL,
  `cantidad_ventas` int(11) DEFAULT 0,
  `calificacion` decimal(2,1) DEFAULT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  PRIMARY KEY (`id_tienda`),
  CONSTRAINT `fk_tienda_usuario` FOREIGN KEY (`id_tienda`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  PRIMARY KEY (`id_admin`),
  CONSTRAINT `fk_admin_usuario` FOREIGN KEY (`id_admin`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta`
--

CREATE TABLE `receta` (
  `id_receta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(2000) NOT NULL,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  `tienda_id` int(11) NOT NULL,
  `veces_pedida` int(11) DEFAULT 0,
  `pasos` varchar(10000) NOT NULL,
  PRIMARY KEY (`id_receta`),
  KEY `fk_receta_tienda` (`tienda_id`),
  CONSTRAINT `fk_receta_usuario` FOREIGN KEY (`tienda_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tienda_receta`
--

CREATE TABLE `tienda_receta` (
  `tienda_id` int(11) NOT NULL,
  `receta_id` int(11) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 0,
  `cantidad_ventas` int(11) DEFAULT 0,
  `calificacion` decimal(2,1) DEFAULT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  PRIMARY KEY (`tienda_id`,`receta_id`),
  KEY `fk_tienda_receta_receta` (`receta_id`),
  CONSTRAINT `fk_tienda_receta_receta` FOREIGN KEY (`receta_id`) REFERENCES `receta` (`id_receta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tienda_receta_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id_tienda`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingrediente`
--

CREATE TABLE `ingrediente` (
  `id_ingrediente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `unidad_medida` enum('kilo','litro','unidad') NOT NULL,
  `condimento` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingrediente_por_receta`
--

CREATE TABLE `ingrediente_por_receta` (
  `receta_id` int(11) NOT NULL,
  `ingrediente_id` int(11) NOT NULL,
  `cantidad` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`receta_id`,`ingrediente_id`),
  KEY `fk_ingrediente_por_receta_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_ingrediente_por_receta_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `ingrediente` (`id_ingrediente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `tienda_id` int(11) NOT NULL,
  `ingrediente_id` int(11) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `peso_unidad` decimal(5,2) DEFAULT NULL COMMENT 'Peso en kg/litro por pack - cantidad de unidades por pack',
  `precio` decimal(10,2) NOT NULL,
  `stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `ruta_imagen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`tienda_id`,`ingrediente_id`),
  KEY `fk_producto_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_producto_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `ingrediente` (`id_ingrediente`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id_tienda`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En preparacion','Enviado','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `total` decimal(10,2) NOT NULL,
  `calificacion_cliente` decimal(2,1) DEFAULT NULL CHECK (`calificacion_cliente` >= 1 and `calificacion_cliente` <= 5),
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_cliente` (`cliente_id`),
  KEY `fk_pedido_tienda` (`tienda_id`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id_tienda`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_pedido`
--

CREATE TABLE `producto_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_tienda_id` int(11) NOT NULL,
  `producto_ingrediente_id` int(11) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `precio_momento` decimal(10,2) NOT NULL,
  `marca_momento` varchar(50) DEFAULT NULL,
  `ruta_imagen_momento` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_producto_pedido_pedido` (`pedido_id`),
  KEY `fk_producto_pedido_producto` (`producto_tienda_id`,`producto_ingrediente_id`),
  CONSTRAINT `fk_producto_pedido_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_pedido_producto` FOREIGN KEY (`producto_tienda_id`,`producto_ingrediente_id`) REFERENCES `producto` (`tienda_id`, `ingrediente_id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigo_postal`
--

CREATE TABLE `codigo_postal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_postal` int(4) NOT NULL,
  `localidad` varchar(50) NOT NULL,
  `provincia` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos`
--

CREATE TABLE `contactos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `comentario` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */; 