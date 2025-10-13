-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-10-2025 a las 02:01:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `promanage`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `miembro` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('pendiente','completada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id`, `proyecto_id`, `nombre`, `miembro`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(20, 4, 'WVS (Jorge Adan)', 'JORGE ADAN', '2025-10-02', '2025-10-02', 'pendiente'),
(21, 4, 'UI/UX (Plablo Joel)', 'Pablo Joel', '2025-10-01', '2025-10-01', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

CREATE TABLE `archivos` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos`
--

INSERT INTO `archivos` (`id`, `proyecto_id`, `nombre_archivo`, `ruta`, `fecha_subida`) VALUES
(5, 4, 'Sadie_perfil.html', '../uploads/21/4/Sadie_perfil.html', '2025-10-06 15:01:13'),
(6, 4, 'Canelo_perfil.html', '../uploads/21/4/Canelo_perfil.html', '2025-10-06 15:54:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `integrantes`
--

CREATE TABLE `integrantes` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `integrantes`
--

INSERT INTO `integrantes` (`id`, `proyecto_id`, `usuario_id`, `fecha_agregado`) VALUES
(1, 7, 7, '2025-10-07 16:14:06'),
(2, 7, 5, '2025-10-07 16:14:06'),
(3, 7, 20, '2025-10-07 16:14:06'),
(4, 7, 21, '2025-10-07 16:14:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maestros`
--

CREATE TABLE `maestros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `maestros`
--

INSERT INTO `maestros` (`id`, `nombre`, `correo`) VALUES
(1, 'Yolanda', 'yolanda@teschi.edu.mx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultimo_commit` datetime DEFAULT current_timestamp(),
  `maestro_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id`, `usuario_id`, `nombre`, `descripcion`, `fecha_creacion`, `ultimo_commit`, `maestro_id`) VALUES
(4, 21, 'CONTROL ESCOLAR', 'SISTEMA EN LINEA PARA ESTUDIANTES Y PROFESORES DE EL TESCHI', '2025-10-06 15:00:18', '2025-10-06 15:54:11', NULL),
(5, 21, 'MANUAL SUPREMOS DEL NENE PROGRAMADOR', 'IMPLEMENTACION DE MANUELES TECNICOS Y DE USUARIOS DE UN SISTEMA ESCOLARIZADO DEL CECYTEM', '2025-10-06 15:55:15', '2025-10-06 15:55:15', NULL),
(7, 7, 'Promanamen', 'Gestro de proyectos', '2025-10-07 10:14:06', '2025-10-07 10:14:06', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `theme` varchar(20) NOT NULL DEFAULT 'dark',
  `custom_color` varchar(7) NOT NULL DEFAULT '#238636',
  `bg_url` varchar(255) DEFAULT NULL,
  `btn_color` varchar(7) DEFAULT '#238636',
  `header_color` varchar(7) DEFAULT '#161b22',
  `text_color` varchar(7) DEFAULT '#c9d1d9'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `correo`, `contrasena`, `nombre`, `telefono`, `ubicacion`, `bio`, `theme`, `custom_color`, `bg_url`, `btn_color`, `header_color`, `text_color`) VALUES
(5, '2022452129@teschi.edu.mx', '$2y$10$ZrasbnjuMSAA.7G1riGJbeAfdY/qpE2TfpVhEDUN6JnGkqEaybSSi', 'Jorge vazquez gomez', '5531391379', 'calle mariano', 'me gustan los perro', 'dark', '#238636', NULL, '#238636', '#161b22', '#c9d1d9'),
(7, 'luis27@gmail.com', '$2y$10$BhesZd4a5S/f/k38cCBEqekuOZlUXWrYayY9jGkScxz5oNK2EydC2', 'Luis Roberto', '5583729379', 'Ciudad de México, México', 'Lider del proyecto', 'dark', '#238636', NULL, '#ff0000', '#000000', '#927777'),
(20, 'daniela1@gmail.com', '$2y$10$hSgYQDtMocAru3vo8BWQ/ukz/1zT1yf6UKNSzEB9/6iLffMHkgY3y', 'daniela mendez', '5568903456', 'Ciudad de México, México', 'estudiante', 'dark', '#238636', NULL, '#238636', '#161b22', '#c9d1d9'),
(21, 'prueba3432@gmail.com', '$2y$10$tmr2QxCQqOt/Gk3ABtsoNu4ZZDd3/qm4tVeBIuF6NWq/fKRfnez0q', 'Jorge_Mendez_Calva', '556765432390', 'Ciudad de México, México', 'estudiante', 'light', '#16448d', NULL, '#ff0026', '#ffffff', '#000000');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `integrantes`
--
ALTER TABLE `integrantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proyecto_id` (`proyecto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `maestros`
--
ALTER TABLE `maestros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `maestro_id` (`maestro_id`);

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
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `archivos`
--
ALTER TABLE `archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `integrantes`
--
ALTER TABLE `integrantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `maestros`
--
ALTER TABLE `maestros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `integrantes`
--
ALTER TABLE `integrantes`
  ADD CONSTRAINT `integrantes_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `integrantes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`maestro_id`) REFERENCES `maestros` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
