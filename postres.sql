-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-07-2025 a las 07:42:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `postres`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `passw` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admins`
--

INSERT INTO `admins` (`id`, `usuario`, `passw`) VALUES
(1, 'login@admin123', 'admin'),
(2, 'login@admin321', 'admin');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `postresitos`
--

CREATE TABLE `postresitos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `tamanio` varchar(5) DEFAULT NULL,
  `sabor` varchar(50) DEFAULT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `postresitos`
--

INSERT INTO `postresitos` (`id`, `titulo`, `descripcion`, `precio`, `categoria`, `tamanio`, `sabor`, `imagen_url`, `stock`) VALUES
(1, 'Pastel de tres leches de chocolate', 'Pastel de chocolate amargo con pan suave y humedecido con leche', 119.99, 'Pasteles y roscas', 'G', 'Chocolate Negro', 'https://www.recetasnestle.com.ve/sites/default/files/srh_recipes/72c1474b902d923d6a33f394e1d65b4b.jpg', 3),
(2, 'Galleta estilo nueva york ', 'Galleta grande estilo nueva york con chispas de tamaño extragrande', 29.99, 'Galletas', 'M', 'Chocolate', 'https://sarasellos.com/wp-content/uploads/2024/06/Galletas-New-York-con-oreos3.jpg', 10),
(3, 'Brownie de momia', 'Brownie de chocolate envuelto con cubierta de azúcar glass y ojitos hechos de luneta', 19.99, 'Brownies', 'S', 'Chocolate con azúcar glass y lunetas', 'https://www.bettycrocker.lat/mx/wp-content/uploads/sites/2/2020/12/BCmexico-recipe-brownies-de-momia.png', 12),
(4, 'Pay de fresa y queso', 'Pay con frutas frescas y mermelada frutal untada encima', 199.99, 'Pies', 'XL', 'Queso y fresa', 'https://www.olyesspastelerias.com/web/images/pay-y-flanes/pays/pay-de-queso-fresa-5.jpg', 2),
(5, 'Gelatina de frutas', 'Gelatina de leche con frutas', 14.99, 'Postres de frutas', 'P', 'Durazno, manzana, naranja, fresa, kiwi', 'https://www.recetasnestle.com.ec/sites/default/files/srh_recipes/65654717b2dffc651a16a8809c87acb8.jpg', 13),
(6, 'Flan de chocolate', 'Flan sabor chocolate con leche', 19.99, 'Flanes y pudines', 'P', 'Chocolate', 'https://cdn.elcocinerocasero.com/imagen/receta/1200/2023-07-13-12-34-07/flan-de-chocolate.jpeg', 19),
(7, 'Galleta glaseada', 'Galleta de chocolate con glaseado de sabor chocolate', 14.99, 'Chocolates y caramelos', 'P', 'Chocolate', 'https://www.clarin.com/img/2020/08/05/gSkfpC0jq_720x0__1.jpg', 4),
(8, 'Cupcake arcoiris', 'Cupcake sabor vainilla con merengue de arcoiris encima y decoraciones con lunetas', 29.99, 'Cupcakes', 'M', 'arcoiris y vainilla', 'https://editorialtelevisa.brightspotcdn.com/dims4/default/581c97f/2147483647/strip/true/crop/672x672+70+0/resize/1000x1000!/quality/90/?url=https%3A%2F%2Fk2-prod-editorial-televisa.s3.us-east-1.amazonaws.com%2Fbrightspot%2F5d%2F22%2F7b517e1242d78f28df597461df79%2Fcupcakes-de-unicornio-receta-facil-y-rapida.jpg', 12),
(9, 'Churros rellenos', 'Paquete de 6 churros rellenos de una variedad de sabores', 49.99, 'Postres fritos', 'G', 'Mermelada, crema pastelera, durazno, cajeta, choco', 'https://i.pinimg.com/736x/9e/fa/9d/9efa9db7d423fc8d970b660aaa9e8923.jpg', 20),
(10, 'Cuernito', 'Cuernito salado para combinar con aderezos y otros ingredientes', 9.99, 'Panaderia', 'M', 'Pan salado', 'https://cdn.milenio.com/uploads/media/2022/12/24/cuernitos-cena-navidad-ano-infaltables.jpg', 34);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `correo`, `pass`) VALUES
(1, 'user@gmail.com', '123456'),
(4, 'user@gmail.net', '54321');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `postresitos`
--
ALTER TABLE `postresitos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `postresitos`
--
ALTER TABLE `postresitos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
