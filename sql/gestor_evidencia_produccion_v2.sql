-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3308
-- Tiempo de generación: 18-11-2026 a las 06:21:24
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
-- Base de datos: `gestor_evidencia_produccion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atributos_tipo_evidencia`
--

CREATE TABLE `atributos_tipo_evidencia` (
  `id_ate` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `id_tipo_atributo` int(11) NOT NULL,
  `nombre_atributo` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `requerido` tinyint(1) NOT NULL DEFAULT 0,
  `unico_por_evidencia` tinyint(1) NOT NULL DEFAULT 0,
  `multiple` tinyint(1) NOT NULL DEFAULT 0,
  `min_longitud` int(11) DEFAULT NULL,
  `max_longitud` int(11) DEFAULT NULL,
  `min_valor` decimal(18,6) DEFAULT NULL,
  `max_valor` decimal(18,6) DEFAULT NULL,
  `opciones_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opciones_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `atributos_tipo_evidencia`
--

INSERT INTO `atributos_tipo_evidencia` (`id_ate`, `id_tipo_evidencia`, `id_tipo_atributo`, `nombre_atributo`, `slug`, `descripcion`, `orden`, `requerido`, `unico_por_evidencia`, `multiple`, `min_longitud`, `max_longitud`, `min_valor`, `max_valor`, `opciones_json`) VALUES
(1, 1, 7, 'Autor 1 (Prueba 1)', 'autor_1_prueba', 'Prueba de Texto de Ayuda', 1, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
(2, 1, 10, 'Prueba2', 'p', 'Prueba 2 ISBN', 2, 1, 0, 1, 10, 200, NULL, NULL, NULL),
(3, 3, 1, 'Nombre de la Institución', 'i', 'Nombre de la Institución que expidió el documento (NO POR SIGLAS)', 1, 1, 0, 0, 15, 150, NULL, NULL, NULL),
(4, 3, 5, 'Fecha de Certificado', 'f', 'Fecha de Expedición', 1, 0, 1, 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion_evidencia`
--

CREATE TABLE `calificacion_evidencia` (
  `id_calificacion` int(11) NOT NULL,
  `id_evidencia` int(11) NOT NULL,
  `id_instrumento` int(11) NOT NULL,
  `resultado` decimal(6,2) NOT NULL,
  `comentario` varchar(1000) DEFAULT NULL,
  `id_usuario_eval` int(11) DEFAULT NULL,
  `calificado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluador_instrumento`
--

CREATE TABLE `evaluador_instrumento` (
  `id_evaluador` int(11) NOT NULL,
  `id_instrumento` int(11) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluador_instrumento`
--

INSERT INTO `evaluador_instrumento` (`id_evaluador`, `id_instrumento`, `asignado_en`) VALUES
(3, 1, '2026-11-17 18:38:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias`
--

CREATE TABLE `evidencias` (
  `id_evidencia` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_docente` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `ocultar` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evidencias`
--

INSERT INTO `evidencias` (`id_evidencia`, `titulo`, `archivo`, `fecha_subida`, `id_docente`, `id_tipo_evidencia`, `ocultar`) VALUES
(1, 'Prueba de Docente Evidencia', 'prueba_de_docente_evidencia_20261111_035655_a57eee59.pdf', '2026-11-11 02:56:55', 2, 1, 0),
(2, 'Evidencia Prueba 2', 'evidencia_prueba_2_20261112_191432_32370ab1.pdf', '2026-11-12 18:14:32', 2, 1, 0),
(3, 'Prueba B', 'prueba_b_20261117_193111_92b46a95.pdf', '2026-11-17 18:31:11', 2, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencia_valores_atributo`
--

CREATE TABLE `evidencia_valores_atributo` (
  `id_eva` bigint(20) NOT NULL,
  `id_evidencia` int(11) NOT NULL,
  `id_ate` int(11) NOT NULL,
  `indice` int(11) NOT NULL DEFAULT 1,
  `valor_texto` varchar(500) DEFAULT NULL,
  `valor_largo` text DEFAULT NULL,
  `valor_int` int(11) DEFAULT NULL,
  `valor_decimal` decimal(18,6) DEFAULT NULL,
  `valor_fecha` date DEFAULT NULL,
  `valor_bool` tinyint(1) DEFAULT NULL,
  `valor_archivo` varchar(255) DEFAULT NULL,
  `valor_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instrumentos`
--

CREATE TABLE `instrumentos` (
  `id_instrumento` int(11) NOT NULL,
  `abreviatura` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `tipo_calificacion` enum('APROBACION','NUMERICA') NOT NULL DEFAULT 'APROBACION',
  `min_calificacion` decimal(5,2) DEFAULT NULL,
  `max_calificacion` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instrumentos`
--

INSERT INTO `instrumentos` (`id_instrumento`, `abreviatura`, `nombre_completo`, `tipo_calificacion`, `min_calificacion`, `max_calificacion`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'SNII', 'Sistema Nacional de Investigadores e Investigadoras', 'NUMERICA', 0.00, 10.00, 1, '2025-10-28 05:11:31', '2026-11-17 18:35:32'),
(2, 'PRODEP', 'Programa para el Desarrollo Profesional Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31'),
(3, 'ESDEPED', 'Estímulos al Desempeño del Personal Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instrumento_tipo_evidencia`
--

CREATE TABLE `instrumento_tipo_evidencia` (
  `id_instrumento` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instrumento_tipo_evidencia`
--

INSERT INTO `instrumento_tipo_evidencia` (`id_instrumento`, `id_tipo_evidencia`, `asignado_en`) VALUES
(1, 1, '2026-11-11 02:58:04'),
(1, 2, '2026-11-17 18:49:54'),
(2, 1, '2026-11-11 02:58:04'),
(2, 2, '2026-11-17 18:49:54'),
(2, 3, '2026-11-17 18:56:40'),
(3, 1, '2026-11-11 02:58:04'),
(3, 3, '2026-11-17 18:56:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(2, 'Administrador'),
(4, 'Docente'),
(3, 'Evaluador'),
(1, 'Super Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_atributo`
--

CREATE TABLE `tipos_atributo` (
  `id_tipo_atributo` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `grupo_storage` enum('texto_corto','texto_largo','entero','decimal','fecha','booleano','archivo','json') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `validador_regex` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_atributo`
--

INSERT INTO `tipos_atributo` (`id_tipo_atributo`, `nombre_tipo`, `slug`, `grupo_storage`, `descripcion`, `validador_regex`) VALUES
(1, 'Texto corto', 'texto_corto', 'texto_corto', 'Cadenas hasta ~500 caracteres', NULL),
(2, 'Texto largo', 'texto_largo', 'texto_largo', 'Cadenas largas (descripciones, autores en bloque)', NULL),
(3, 'Entero', 'entero', 'entero', 'Valores enteros (año, páginas, horas)', NULL),
(4, 'Decimal', 'decimal', 'decimal', 'Valores decimales (calificaciones, costos)', NULL),
(5, 'Fecha', 'fecha', 'fecha', 'Fechas YYYY-MM-DD', NULL),
(6, 'Booleano', 'booleano', 'booleano', 'Sí/No', NULL),
(7, 'Archivo', 'archivo', 'archivo', 'Ruta a archivo adicional', NULL),
(8, 'JSON / Lista', 'json', 'json', 'Estructuras complejas, multiselect o pares clave-valor', NULL),
(9, 'DOI', 'doi', 'texto_corto', 'Identificador DOI', '^10\\.\\d{4,9}/[-._;()/:A-Z0-9]+$'),
(10, 'ISBN', 'isbn', 'texto_corto', 'ISBN-10 o ISBN-13', '^(97(8|9))?\\d{9}(\\d|X)$'),
(11, 'ISSN', 'issn', 'texto_corto', 'ISSN con guion', '^\\d{4}-\\d{3}[\\dxX]$'),
(12, 'URL', 'url', 'texto_corto', 'Direcciones web', '^(https?:\\/\\/).+$'),
(13, 'Email', 'email', 'texto_corto', 'Correo electrónico', '^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_de_evidencia`
--

CREATE TABLE `tipos_de_evidencia` (
  `id_tipo_evidencia` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_de_evidencia`
--

INSERT INTO `tipos_de_evidencia` (`id_tipo_evidencia`, `nombre_tipo`, `descripcion`) VALUES
(1, 'TEST SNII', 'Es una pequeña prueba de que funciona el instrumento de evaluación SNII'),
(2, 'Constancia', 'Constancia de Estudios'),
(3, 'Constancia de Estudios', 'Debe de incluir calificaciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidop` varchar(100) NOT NULL,
  `apellidom` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellidop`, `apellidom`, `correo`, `password`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'Super', 'Usuario', 'anton.tower21@gmail.com', '$2y$10$yQBsFZXnDvpVYMsjxtLTyeyJXIR/sgbmiK5mskUWtTGdpss3E9igu', 1, 1, '2026-11-10 19:42:30', '2026-11-17 18:23:22'),
(2, 'Luis Antonio', 'Elias', 'Torres', 'anton.tower21docente@gmail.com', '$2y$10$RE9PQnXPipzyjRbM04sThug2W1X/uGPom.H37fMn9eVjKC1rLm6/m', 4, 1, '2026-11-10 20:25:58', '2026-11-12 18:11:57'),
(3, 'Luis Antonio', 'Elias', 'Torres', 'anton.tower21evaluador@gmail.com', '$2y$10$c5Cos5dgdp.ys5/jx97..OhDrTpfcgEqmhX0812ARaK0Y8DZN/DDG', 3, 1, '2026-11-12 18:10:38', '2026-11-17 18:38:02'),
(4, 'Luis Antonio', 'Elias', 'Torres', 'anton.tower21administrador@gmail.com', '$2y$10$krevpEX3huoQhaKxNpEp8.i.Og3eX3VFwpJmSv.jZMuSpKmI6MKL.', 2, 1, '2026-11-12 18:13:23', '2026-11-12 18:13:23');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  ADD PRIMARY KEY (`id_ate`),
  ADD UNIQUE KEY `uq_ate_tipo_slug` (`id_tipo_evidencia`,`slug`),
  ADD KEY `idx_ate_tipo_evidencia` (`id_tipo_evidencia`),
  ADD KEY `idx_ate_tipo_atributo` (`id_tipo_atributo`);

--
-- Indices de la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uniq_evi_inst` (`id_evidencia`,`id_instrumento`),
  ADD KEY `idx_inst` (`id_instrumento`),
  ADD KEY `idx_evi` (`id_evidencia`),
  ADD KEY `fk_ce_eval` (`id_usuario_eval`);

--
-- Indices de la tabla `evaluador_instrumento`
--
ALTER TABLE `evaluador_instrumento`
  ADD PRIMARY KEY (`id_evaluador`,`id_instrumento`),
  ADD KEY `idx_ei_instrumento` (`id_instrumento`);

--
-- Indices de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `id_tipo_evidencia` (`id_tipo_evidencia`);

--
-- Indices de la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  ADD PRIMARY KEY (`id_eva`),
  ADD UNIQUE KEY `uq_eva_unico` (`id_evidencia`,`id_ate`,`indice`),
  ADD KEY `idx_eva_evidencia` (`id_evidencia`),
  ADD KEY `idx_eva_ate` (`id_ate`);

--
-- Indices de la tabla `instrumentos`
--
ALTER TABLE `instrumentos`
  ADD PRIMARY KEY (`id_instrumento`),
  ADD UNIQUE KEY `abreviatura` (`abreviatura`),
  ADD UNIQUE KEY `nombre_completo` (`nombre_completo`);

--
-- Indices de la tabla `instrumento_tipo_evidencia`
--
ALTER TABLE `instrumento_tipo_evidencia`
  ADD PRIMARY KEY (`id_instrumento`,`id_tipo_evidencia`),
  ADD KEY `idx_ite_tipo` (`id_tipo_evidencia`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_atributo`
--
ALTER TABLE `tipos_atributo`
  ADD PRIMARY KEY (`id_tipo_atributo`),
  ADD UNIQUE KEY `uq_tipos_atributo_slug` (`slug`),
  ADD UNIQUE KEY `uq_tipos_atributo_nombre` (`nombre_tipo`);

--
-- Indices de la tabla `tipos_de_evidencia`
--
ALTER TABLE `tipos_de_evidencia`
  ADD PRIMARY KEY (`id_tipo_evidencia`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  MODIFY `id_ate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  MODIFY `id_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  MODIFY `id_eva` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `instrumentos`
--
ALTER TABLE `instrumentos`
  MODIFY `id_instrumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipos_atributo`
--
ALTER TABLE `tipos_atributo`
  MODIFY `id_tipo_atributo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `tipos_de_evidencia`
--
ALTER TABLE `tipos_de_evidencia`
  MODIFY `id_tipo_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  ADD CONSTRAINT `fk_ate_tipo_atributo` FOREIGN KEY (`id_tipo_atributo`) REFERENCES `tipos_atributo` (`id_tipo_atributo`),
  ADD CONSTRAINT `fk_ate_tipo_evidencia` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  ADD CONSTRAINT `fk_ce_eval` FOREIGN KEY (`id_usuario_eval`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_evi` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_inst` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluador_instrumento`
--
ALTER TABLE `evaluador_instrumento`
  ADD CONSTRAINT `fk_ei_evaluador` FOREIGN KEY (`id_evaluador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ei_instrumento` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_ibfk_1` FOREIGN KEY (`id_docente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidencias_ibfk_2` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  ADD CONSTRAINT `fk_eva_ate` FOREIGN KEY (`id_ate`) REFERENCES `atributos_tipo_evidencia` (`id_ate`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eva_evidencia` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `instrumento_tipo_evidencia`
--
ALTER TABLE `instrumento_tipo_evidencia`
  ADD CONSTRAINT `fk_ite_instrumento` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ite_tipo` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
