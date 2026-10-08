-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-09-2026 a las 04:34:42
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_odf`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cablealimentacion`
--

CREATE TABLE `cablealimentacion` (
  `id_cable` int(11) NOT NULL,
  `id_site` int(11) DEFAULT NULL,
  `origen` varchar(80) DEFAULT NULL,
  `destino` varchar(80) DEFAULT NULL,
  `capacidad_hilos` int(11) NOT NULL,
  `rack` varchar(100) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cablealimentacion`
--

INSERT INTO `cablealimentacion` (`id_cable`, `id_site`, `origen`, `destino`, `capacidad_hilos`, `rack`, `fecha_instalacion`, `activo`) VALUES
(14, 6, '0100374_LM_Zapallal', '0100512_LM_Ventanilla_Pachacutec', 144, '1', NULL, 1),
(18, 6, '0100374_LM_Zapallal', '0100463_LM_PanNorte_Km33', 144, '1', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historialcambiospuerto`
--

CREATE TABLE `historialcambiospuerto` (
  `id_historial` int(11) NOT NULL,
  `id_puerto` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL,
  `estado_nuevo` varchar(20) DEFAULT NULL,
  `observacion` varchar(500) DEFAULT NULL,
  `fecha_cambio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historialcambiospuerto`
--

INSERT INTO `historialcambiospuerto` (`id_historial`, `id_puerto`, `id_usuario`, `estado_anterior`, `estado_nuevo`, `observacion`, `fecha_cambio`) VALUES
(32, 769, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO', '2026-09-21 21:31:10'),
(33, 770, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO', '2026-09-21 21:31:10'),
(34, 771, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO BCAKUP', '2026-09-21 21:31:10'),
(35, 772, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO BCAKUP', '2026-09-21 21:31:10'),
(36, 773, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPAPLLAL TO HIJOS DE VILLA', '2026-09-21 21:31:10'),
(37, 774, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPAPLLAL TO HIJOS DE VILLA', '2026-09-21 21:31:10'),
(38, 775, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG1', '2026-09-21 21:31:10'),
(39, 776, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG1', '2026-09-21 21:31:10'),
(40, 779, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL-DWDM (TO AEROPUERTO)', '2026-09-21 21:31:10'),
(41, 780, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL-DWDM (TO AEROPUERTO)', '2026-09-21 21:31:10'),
(42, 791, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-21 21:31:10'),
(43, 792, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-21 21:31:10'),
(44, 793, 1, 'Sin Servicio', 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', '2026-09-21 21:31:10'),
(45, 794, 1, 'Sin Servicio', 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', '2026-09-21 21:31:10'),
(46, 795, 1, 'Sin Servicio', 'En Servicio', 'SIN ROTULO', '2026-09-21 21:31:10'),
(47, 796, 1, 'Sin Servicio', 'En Servicio', 'SIN ROTULO', '2026-09-21 21:31:10'),
(48, 799, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO SAN JUDAS TADEO', '2026-09-21 21:31:10'),
(49, 800, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO SAN JUDAS TADEO', '2026-09-21 21:31:10'),
(50, 801, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPL_RAN_1', '2026-09-21 21:31:10'),
(51, 802, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPL_RAN_1', '2026-09-21 21:31:10'),
(52, 803, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(53, 804, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(54, 805, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL_TO_HILOS_DE_VILLA', '2026-09-21 21:31:10'),
(55, 806, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL_TO_HILOS_DE_VILLA', '2026-09-21 21:31:10'),
(56, 807, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO CHANCAY', '2026-09-21 21:31:10'),
(57, 808, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO CHANCAY', '2026-09-21 21:31:10'),
(58, 817, 1, 'Sin Servicio', 'En Servicio', 'SIN ROTULO', '2026-09-21 21:31:10'),
(59, 818, 1, 'Sin Servicio', 'En Servicio', 'SIN ROTULO', '2026-09-21 21:31:10'),
(60, 841, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG', '2026-09-21 21:31:10'),
(61, 842, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG', '2026-09-21 21:31:10'),
(62, 853, 1, 'Sin Servicio', 'En Servicio', 'ENLACE ZAPALLAL TO VENTANILLA', '2026-09-21 21:31:10'),
(63, 854, 1, 'Sin Servicio', 'En Servicio', 'ENLACE ZAPALLAL TO VENTANILLA', '2026-09-21 21:31:10'),
(64, 855, 1, 'Sin Servicio', 'En Servicio', 'ZAPALLAL TO VENTANILLA PACHACUTEC', '2026-09-21 21:31:10'),
(65, 856, 1, 'Sin Servicio', 'En Servicio', 'ZAPALLAL TO VENTANILLA PACHACUTEC', '2026-09-21 21:31:10'),
(66, 859, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(67, 860, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(68, 861, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(69, 862, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-21 21:31:10'),
(80, 1019, 1, 'Sin Servicio', 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', '2026-09-22 19:49:05'),
(81, 1020, 1, 'Sin Servicio', 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', '2026-09-22 19:49:05'),
(82, 1021, 1, 'Sin Servicio', 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', '2026-09-22 19:49:05'),
(83, 1022, 1, 'Sin Servicio', 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', '2026-09-22 19:49:05'),
(84, 1025, 1, 'Sin Servicio', 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(85, 1026, 1, 'Sin Servicio', 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(86, 1029, 1, 'Sin Servicio', 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(87, 1030, 1, 'Sin Servicio', 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(88, 1035, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-22 19:49:05'),
(89, 1036, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-22 19:49:05'),
(90, 1037, 1, 'Sin Servicio', 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(91, 1038, 1, 'Sin Servicio', 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(92, 1039, 1, 'Sin Servicio', 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(93, 1040, 1, 'Sin Servicio', 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', '2026-09-22 19:49:05'),
(94, 1041, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-22 19:49:05'),
(95, 1042, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-22 19:49:05'),
(96, 1043, 1, 'Sin Servicio', 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', '2026-09-22 19:49:05'),
(97, 1044, 1, 'Sin Servicio', 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', '2026-09-22 19:49:05'),
(98, 1045, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(99, 1046, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(100, 1047, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(101, 1048, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(102, 1049, 1, 'Sin Servicio', 'En Servicio', 'LM ZAPALLAL TO LM VALLE SAGRADO', '2026-09-22 19:49:05'),
(103, 1050, 1, 'Sin Servicio', 'En Servicio', 'LM ZAPALLAL TO LM VALLE SAGRADO', '2026-09-22 19:49:05'),
(104, 1055, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(105, 1056, 1, 'Sin Servicio', 'En Servicio', 'SIN ETIQUETA', '2026-09-22 19:49:05'),
(106, 1061, 1, 'Sin Servicio', 'En Servicio', 'ODF RACK ZAP -01', '2026-09-22 19:49:05'),
(107, 1062, 1, 'Sin Servicio', 'En Servicio', 'ODF RACK ZAP -01', '2026-09-22 19:49:05'),
(108, 1063, 1, 'Sin Servicio', 'En Servicio', 'PUENTE PIEDRA TO TRAPICHE', '2026-09-22 19:49:05'),
(109, 1064, 1, 'Sin Servicio', 'En Servicio', 'PUENTE PIEDRA TO TRAPICHE', '2026-09-22 19:49:05'),
(110, 1065, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-22 19:49:05'),
(111, 1066, 1, 'Sin Servicio', 'En Servicio', 'GESTION DE FO', '2026-09-22 19:49:05'),
(112, 1067, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO LAS_LOMAS_ZAPALLAL', '2026-09-22 19:49:05'),
(113, 1068, 1, 'Sin Servicio', 'En Servicio', '0100374_LM_ZAPALLAL TO LAS_LOMAS_ZAPALLAL', '2026-09-22 19:49:05'),
(114, 1093, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-22 19:49:05'),
(115, 1094, 1, 'Sin Servicio', 'En Servicio', 'LIM_ZPLL_AGG_1', '2026-09-22 19:49:05'),
(116, 1103, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05'),
(117, 1104, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05'),
(118, 1109, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05'),
(119, 1110, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05'),
(120, 1111, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05'),
(121, 1112, 1, 'Sin Servicio', 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', '2026-09-22 19:49:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `odf`
--

CREATE TABLE `odf` (
  `id_odf` int(11) NOT NULL,
  `id_site` int(11) DEFAULT NULL,
  `origen` varchar(80) DEFAULT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `capacidad_puertos` enum('12','24','48','96','144') NOT NULL,
  `ubicacion_rack` varchar(100) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `odf`
--

INSERT INTO `odf` (`id_odf`, `id_site`, `origen`, `destino`, `capacidad_puertos`, `ubicacion_rack`, `activo`) VALUES
(10, 6, '0100374_LM_Zapallal', '0100512_LM_Ventanilla_Pachacutec', '144', '1', 1),
(17, 6, '0100374_LM_Zapallal', '0100463_LM_PanNorte_Km33', '144', '1', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puerto`
--

CREATE TABLE `puerto` (
  `id_puerto` int(11) NOT NULL,
  `id_odf` int(11) DEFAULT NULL,
  `posicion` int(11) NOT NULL,
  `estado` enum('Sin Servicio','En Servicio','Reservado','Malogrado') NOT NULL DEFAULT 'Sin Servicio',
  `descripcion` varchar(255) DEFAULT NULL,
  `id_cable_conectado` int(11) DEFAULT NULL,
  `numero_hilo` int(11) DEFAULT NULL,
  `bloqueado` tinyint(4) DEFAULT 0,
  `modificado_por` int(11) DEFAULT NULL,
  `ultima_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puerto`
--

INSERT INTO `puerto` (`id_puerto`, `id_odf`, `posicion`, `estado`, `descripcion`, `id_cable_conectado`, `numero_hilo`, `bloqueado`, `modificado_por`, `ultima_modificacion`) VALUES
(769, 10, 1, 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO', 14, 1, 0, NULL, '2026-09-21 21:31:10'),
(770, 10, 2, 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO', 14, 2, 0, 1, '2026-09-21 21:31:10'),
(771, 10, 3, 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO BCAKUP', 14, 3, 0, 1, '2026-09-21 21:31:10'),
(772, 10, 4, 'En Servicio', 'LM_ZAPALLAL_DWM_to_AEROPUERTO BCAKUP', 14, 4, 0, 1, '2026-09-21 21:31:10'),
(773, 10, 5, 'En Servicio', '0100374_LM_ZAPAPLLAL TO HIJOS DE VILLA', 14, 5, 0, 1, '2026-09-21 21:31:10'),
(774, 10, 6, 'En Servicio', '0100374_LM_ZAPAPLLAL TO HIJOS DE VILLA', 14, 6, 0, 1, '2026-09-21 21:31:10'),
(775, 10, 7, 'En Servicio', 'LIM_ZPLL_AGG1', 14, 7, 0, 1, '2026-09-21 21:31:10'),
(776, 10, 8, 'En Servicio', 'LIM_ZPLL_AGG1', 14, 8, 0, 1, '2026-09-21 21:31:10'),
(777, 10, 9, 'Sin Servicio', NULL, 14, 9, 0, 1, '2026-09-21 21:31:10'),
(778, 10, 10, 'Sin Servicio', NULL, 14, 10, 0, 1, '2026-09-21 21:31:10'),
(779, 10, 11, 'En Servicio', 'LM_ZAPALLAL-DWDM (TO AEROPUERTO)', 14, 11, 0, 1, '2026-09-21 21:31:10'),
(780, 10, 12, 'En Servicio', 'LM_ZAPALLAL-DWDM (TO AEROPUERTO)', 14, 12, 0, 1, '2026-09-21 21:31:10'),
(781, 10, 13, 'Sin Servicio', NULL, 14, 13, 0, 1, '2026-09-21 21:31:10'),
(782, 10, 14, 'Sin Servicio', NULL, 14, 14, 0, 1, '2026-09-21 21:31:10'),
(783, 10, 15, 'Sin Servicio', NULL, 14, 15, 0, 1, '2026-09-21 21:31:10'),
(784, 10, 16, 'Sin Servicio', NULL, 14, 16, 0, 1, '2026-09-21 21:31:10'),
(785, 10, 17, 'Sin Servicio', NULL, 14, 17, 0, 1, '2026-09-21 21:31:10'),
(786, 10, 18, 'Sin Servicio', NULL, 14, 18, 0, 1, '2026-09-21 21:31:10'),
(787, 10, 19, 'Sin Servicio', 'SIN ACOPLADOR', 14, 19, 0, 1, '2026-09-21 21:31:10'),
(788, 10, 20, 'Sin Servicio', 'SIN ACOPLADOR', 14, 20, 0, 1, '2026-09-21 21:31:10'),
(789, 10, 21, 'Sin Servicio', NULL, 14, 21, 0, 1, '2026-09-21 21:31:10'),
(790, 10, 22, 'Sin Servicio', NULL, 14, 22, 0, 1, '2026-09-21 21:31:10'),
(791, 10, 23, 'En Servicio', 'GESTION DE FO', 14, 23, 0, 1, '2026-09-21 21:31:10'),
(792, 10, 24, 'En Servicio', 'GESTION DE FO', 14, 24, 0, 1, '2026-09-21 21:31:10'),
(793, 10, 25, 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', 14, 25, 0, 1, '2026-09-21 21:31:10'),
(794, 10, 26, 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', 14, 26, 0, 1, '2026-09-21 21:31:10'),
(795, 10, 27, 'En Servicio', 'SIN ROTULO', 14, 27, 0, 1, '2026-09-21 21:31:10'),
(796, 10, 28, 'En Servicio', 'SIN ROTULO', 14, 28, 0, 1, '2026-09-21 21:31:10'),
(797, 10, 29, 'Sin Servicio', NULL, 14, 29, 0, 1, '2026-09-21 21:31:10'),
(798, 10, 30, 'Sin Servicio', NULL, 14, 30, 0, 1, '2026-09-21 21:31:10'),
(799, 10, 31, 'En Servicio', '0100374_LM_ZAPALLAL TO SAN JUDAS TADEO', 14, 31, 0, 1, '2026-09-21 21:31:10'),
(800, 10, 32, 'En Servicio', '0100374_LM_ZAPALLAL TO SAN JUDAS TADEO', 14, 32, 0, 1, '2026-09-21 21:31:10'),
(801, 10, 33, 'En Servicio', 'LIM_ZPL_RAN_1', 14, 33, 0, 1, '2026-09-21 21:31:10'),
(802, 10, 34, 'En Servicio', 'LIM_ZPL_RAN_1', 14, 34, 0, 1, '2026-09-21 21:31:10'),
(803, 10, 35, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 35, 0, 1, '2026-09-21 21:31:10'),
(804, 10, 36, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 36, 0, 1, '2026-09-21 21:31:10'),
(805, 10, 37, 'En Servicio', '0100374_LM_ZAPALLAL_TO_HILOS_DE_VILLA', 14, 37, 0, 1, '2026-09-21 21:31:10'),
(806, 10, 38, 'En Servicio', '0100374_LM_ZAPALLAL_TO_HILOS_DE_VILLA', 14, 38, 0, 1, '2026-09-21 21:31:10'),
(807, 10, 39, 'En Servicio', '0100374_LM_ZAPALLAL TO CHANCAY', 14, 39, 0, 1, '2026-09-21 21:31:10'),
(808, 10, 40, 'En Servicio', '0100374_LM_ZAPALLAL TO CHANCAY', 14, 40, 0, 1, '2026-09-21 21:31:10'),
(809, 10, 41, 'Sin Servicio', NULL, 14, 41, 0, 1, '2026-09-21 21:31:10'),
(810, 10, 42, 'Sin Servicio', NULL, 14, 42, 0, 1, '2026-09-21 21:31:10'),
(811, 10, 43, 'Sin Servicio', NULL, 14, 43, 0, 1, '2026-09-21 21:31:10'),
(812, 10, 44, 'Sin Servicio', NULL, 14, 44, 0, 1, '2026-09-21 21:31:10'),
(813, 10, 45, 'Sin Servicio', NULL, 14, 45, 0, 1, '2026-09-21 21:31:10'),
(814, 10, 46, 'Sin Servicio', NULL, 14, 46, 0, 1, '2026-09-21 21:31:10'),
(815, 10, 47, 'Sin Servicio', NULL, 14, 47, 0, 1, '2026-09-21 21:31:10'),
(816, 10, 48, 'Sin Servicio', NULL, 14, 48, 0, 1, '2026-09-21 21:31:10'),
(817, 10, 49, 'En Servicio', 'SIN ROTULO', 14, 49, 0, 1, '2026-09-21 21:31:10'),
(818, 10, 50, 'En Servicio', 'SIN ROTULO', 14, 50, 0, 1, '2026-09-21 21:31:10'),
(819, 10, 51, 'Sin Servicio', NULL, 14, 51, 0, 1, '2026-09-21 21:31:10'),
(820, 10, 52, 'Sin Servicio', NULL, 14, 52, 0, 1, '2026-09-21 21:31:10'),
(821, 10, 53, 'Sin Servicio', NULL, 14, 53, 0, 1, '2026-09-21 21:31:10'),
(822, 10, 54, 'Sin Servicio', NULL, 14, 54, 0, 1, '2026-09-21 21:31:10'),
(823, 10, 55, 'Sin Servicio', NULL, 14, 55, 0, 1, '2026-09-21 21:31:10'),
(824, 10, 56, 'Sin Servicio', NULL, 14, 56, 0, 1, '2026-09-21 21:31:10'),
(825, 10, 57, 'Sin Servicio', NULL, 14, 57, 0, 1, '2026-09-21 21:31:10'),
(826, 10, 58, 'Sin Servicio', NULL, 14, 58, 0, 1, '2026-09-21 21:31:10'),
(827, 10, 59, 'Sin Servicio', NULL, 14, 59, 0, 1, '2026-09-21 21:31:10'),
(828, 10, 60, 'Sin Servicio', NULL, 14, 60, 0, 1, '2026-09-21 21:31:10'),
(829, 10, 61, 'Sin Servicio', NULL, 14, 61, 0, 1, '2026-09-21 21:31:10'),
(830, 10, 62, 'Sin Servicio', NULL, 14, 62, 0, 1, '2026-09-21 21:31:10'),
(831, 10, 63, 'Sin Servicio', NULL, 14, 63, 0, 1, '2026-09-21 21:31:10'),
(832, 10, 64, 'Sin Servicio', NULL, 14, 64, 0, 1, '2026-09-21 21:31:10'),
(833, 10, 65, 'Sin Servicio', NULL, 14, 65, 0, 1, '2026-09-21 21:31:10'),
(834, 10, 66, 'Sin Servicio', NULL, 14, 66, 0, 1, '2026-09-21 21:31:10'),
(835, 10, 67, 'Sin Servicio', NULL, 14, 67, 0, 1, '2026-09-21 21:31:10'),
(836, 10, 68, 'Sin Servicio', NULL, 14, 68, 0, 1, '2026-09-21 21:31:10'),
(837, 10, 69, 'Sin Servicio', NULL, 14, 69, 0, 1, '2026-09-21 21:31:10'),
(838, 10, 70, 'Sin Servicio', NULL, 14, 70, 0, 1, '2026-09-21 21:31:10'),
(839, 10, 71, 'Sin Servicio', NULL, 14, 71, 0, 1, '2026-09-21 21:31:10'),
(840, 10, 72, 'Sin Servicio', NULL, 14, 72, 0, 1, '2026-09-21 21:31:10'),
(841, 10, 73, 'En Servicio', 'LIM_ZPLL_AGG', 14, 73, 0, 1, '2026-09-21 21:31:10'),
(842, 10, 74, 'En Servicio', 'LIM_ZPLL_AGG', 14, 74, 0, 1, '2026-09-21 21:31:10'),
(843, 10, 75, 'Sin Servicio', NULL, 14, 75, 0, 1, '2026-09-21 21:31:10'),
(844, 10, 76, 'Sin Servicio', NULL, 14, 76, 0, 1, '2026-09-21 21:31:10'),
(845, 10, 77, 'Sin Servicio', NULL, 14, 77, 0, 1, '2026-09-21 21:31:10'),
(846, 10, 78, 'Sin Servicio', NULL, 14, 78, 0, 1, '2026-09-21 21:31:10'),
(847, 10, 79, 'Sin Servicio', NULL, 14, 79, 0, 1, '2026-09-21 21:31:10'),
(848, 10, 80, 'Sin Servicio', NULL, 14, 80, 0, 1, '2026-09-21 21:31:10'),
(849, 10, 81, 'Sin Servicio', NULL, 14, 81, 0, 1, '2026-09-21 21:31:10'),
(850, 10, 82, 'Sin Servicio', NULL, 14, 82, 0, 1, '2026-09-21 21:31:10'),
(851, 10, 83, 'Sin Servicio', NULL, 14, 83, 0, 1, '2026-09-21 21:31:10'),
(852, 10, 84, 'Sin Servicio', NULL, 14, 84, 0, 1, '2026-09-21 21:31:10'),
(853, 10, 85, 'En Servicio', 'ENLACE ZAPALLAL TO VENTANILLA', 14, 85, 0, 1, '2026-09-21 21:31:10'),
(854, 10, 86, 'En Servicio', 'ENLACE ZAPALLAL TO VENTANILLA', 14, 86, 0, 1, '2026-09-21 21:31:10'),
(855, 10, 87, 'En Servicio', 'ZAPALLAL TO VENTANILLA PACHACUTEC', 14, 87, 0, 1, '2026-09-21 21:31:10'),
(856, 10, 88, 'En Servicio', 'ZAPALLAL TO VENTANILLA PACHACUTEC', 14, 88, 0, 1, '2026-09-21 21:31:10'),
(857, 10, 89, 'Sin Servicio', NULL, 14, 89, 0, 1, '2026-09-21 21:31:10'),
(858, 10, 90, 'Sin Servicio', NULL, 14, 90, 0, 1, '2026-09-21 21:31:10'),
(859, 10, 91, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 91, 0, 1, '2026-09-21 21:31:10'),
(860, 10, 92, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 92, 0, 1, '2026-09-21 21:31:10'),
(861, 10, 93, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 93, 0, 1, '2026-09-21 21:31:10'),
(862, 10, 94, 'En Servicio', 'LIM_ZPLL_AGG_1', 14, 94, 0, 1, '2026-09-21 21:31:10'),
(863, 10, 95, 'Sin Servicio', NULL, 14, 95, 0, 1, '2026-09-21 21:31:10'),
(864, 10, 96, 'Sin Servicio', NULL, 14, 96, 0, 1, '2026-09-21 21:31:10'),
(865, 10, 97, 'Sin Servicio', NULL, 14, 97, 0, 1, '2026-09-21 21:31:10'),
(866, 10, 98, 'Sin Servicio', NULL, 14, 98, 0, 1, '2026-09-21 21:31:10'),
(867, 10, 99, 'Sin Servicio', NULL, 14, 99, 0, 1, '2026-09-21 21:31:10'),
(868, 10, 100, 'Sin Servicio', NULL, 14, 100, 0, 1, '2026-09-21 21:31:10'),
(869, 10, 101, 'Sin Servicio', NULL, 14, 101, 0, 1, '2026-09-21 21:31:10'),
(870, 10, 102, 'Sin Servicio', NULL, 14, 102, 0, 1, '2026-09-21 21:31:10'),
(871, 10, 103, 'Sin Servicio', NULL, 14, 103, 0, 1, '2026-09-21 21:31:10'),
(872, 10, 104, 'Sin Servicio', NULL, 14, 104, 0, 1, '2026-09-21 21:31:10'),
(873, 10, 105, 'Sin Servicio', NULL, 14, 105, 0, 1, '2026-09-21 21:31:10'),
(874, 10, 106, 'Sin Servicio', NULL, 14, 106, 0, 1, '2026-09-21 21:31:10'),
(875, 10, 107, 'Sin Servicio', NULL, 14, 107, 0, 1, '2026-09-21 21:31:10'),
(876, 10, 108, 'Sin Servicio', NULL, 14, 108, 0, 1, '2026-09-21 21:31:10'),
(877, 10, 109, 'Sin Servicio', NULL, 14, 109, 0, 1, '2026-09-21 21:31:10'),
(878, 10, 110, 'Sin Servicio', NULL, 14, 110, 0, 1, '2026-09-21 21:31:10'),
(879, 10, 111, 'Sin Servicio', NULL, 14, 111, 0, 1, '2026-09-21 21:31:10'),
(880, 10, 112, 'Sin Servicio', NULL, 14, 112, 0, 1, '2026-09-21 21:31:10'),
(881, 10, 113, 'Sin Servicio', NULL, 14, 113, 0, 1, '2026-09-21 21:31:10'),
(882, 10, 114, 'Sin Servicio', NULL, 14, 114, 0, 1, '2026-09-21 21:31:10'),
(883, 10, 115, 'Sin Servicio', NULL, 14, 115, 0, 1, '2026-09-21 21:31:10'),
(884, 10, 116, 'Sin Servicio', NULL, 14, 116, 0, 1, '2026-09-21 21:31:10'),
(885, 10, 117, 'Sin Servicio', NULL, 14, 117, 0, 1, '2026-09-21 21:31:10'),
(886, 10, 118, 'Sin Servicio', NULL, 14, 118, 0, 1, '2026-09-21 21:31:10'),
(887, 10, 119, 'Sin Servicio', NULL, 14, 119, 0, 1, '2026-09-21 21:31:10'),
(888, 10, 120, 'Sin Servicio', NULL, 14, 120, 0, 1, '2026-09-21 21:31:10'),
(889, 10, 121, 'Sin Servicio', NULL, 14, 121, 0, 1, '2026-09-21 21:31:10'),
(890, 10, 122, 'Sin Servicio', NULL, 14, 122, 0, 1, '2026-09-21 21:31:10'),
(891, 10, 123, 'Sin Servicio', NULL, 14, 123, 0, 1, '2026-09-21 21:31:10'),
(892, 10, 124, 'Sin Servicio', NULL, 14, 124, 0, 1, '2026-09-21 21:31:10'),
(893, 10, 125, 'Sin Servicio', NULL, 14, 125, 0, 1, '2026-09-21 21:31:10'),
(894, 10, 126, 'Sin Servicio', NULL, 14, 126, 0, 1, '2026-09-21 21:31:10'),
(895, 10, 127, 'Sin Servicio', NULL, 14, 127, 0, 1, '2026-09-21 21:31:10'),
(896, 10, 128, 'Sin Servicio', NULL, 14, 128, 0, 1, '2026-09-21 21:31:10'),
(897, 10, 129, 'Sin Servicio', NULL, 14, 129, 0, 1, '2026-09-21 21:31:10'),
(898, 10, 130, 'Sin Servicio', NULL, 14, 130, 0, 1, '2026-09-21 21:31:10'),
(899, 10, 131, 'Sin Servicio', NULL, 14, 131, 0, 1, '2026-09-21 21:31:10'),
(900, 10, 132, 'Sin Servicio', NULL, 14, 132, 0, 1, '2026-09-21 21:31:10'),
(901, 10, 133, 'Sin Servicio', NULL, 14, 133, 0, 1, '2026-09-21 21:31:10'),
(902, 10, 134, 'Sin Servicio', NULL, 14, 134, 0, 1, '2026-09-21 21:31:10'),
(903, 10, 135, 'Sin Servicio', NULL, 14, 135, 0, 1, '2026-09-21 21:31:10'),
(904, 10, 136, 'Sin Servicio', NULL, 14, 136, 0, 1, '2026-09-21 21:31:10'),
(905, 10, 137, 'Sin Servicio', NULL, 14, 137, 0, 1, '2026-09-21 21:31:10'),
(906, 10, 138, 'Sin Servicio', NULL, 14, 138, 0, 1, '2026-09-21 21:31:10'),
(907, 10, 139, 'Sin Servicio', 'SIN ACOPLADOR', 14, 139, 0, 1, '2026-09-21 21:31:10'),
(908, 10, 140, 'Sin Servicio', 'SIN ACOPLADOR', 14, 140, 0, 1, '2026-09-21 21:31:10'),
(909, 10, 141, 'Sin Servicio', 'SIN ACOPLADOR', 14, 141, 0, 1, '2026-09-21 21:31:10'),
(910, 10, 142, 'Sin Servicio', 'SIN ACOPLADOR', 14, 142, 0, 1, '2026-09-21 21:31:10'),
(911, 10, 143, 'Sin Servicio', 'SIN ACOPLADOR', 14, 143, 0, 1, '2026-09-21 21:31:10'),
(912, 10, 144, 'Sin Servicio', 'SIN ACOPLADOR', 14, 144, 0, 1, '2026-09-21 21:31:10'),
(1019, 17, 1, 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', 18, 1, 0, 1, '2026-09-22 19:49:05'),
(1020, 17, 2, 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', 18, 2, 0, 1, '2026-09-22 19:49:05'),
(1021, 17, 3, 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', 18, 3, 0, 1, '2026-09-22 19:49:05'),
(1022, 17, 4, 'En Servicio', 'LM-ZAPALLAL-DWDM (TO CARABAYLLO)', 18, 4, 0, 1, '2026-09-22 19:49:05'),
(1023, 17, 5, 'Sin Servicio', NULL, 18, 5, 0, 1, '2026-09-22 19:49:05'),
(1024, 17, 6, 'Sin Servicio', NULL, 18, 6, 0, 1, '2026-09-22 19:49:05'),
(1025, 17, 7, 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 7, 0, 1, '2026-09-22 19:49:05'),
(1026, 17, 8, 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 8, 0, 1, '2026-09-22 19:49:05'),
(1027, 17, 9, 'Sin Servicio', NULL, 18, 9, 0, 1, '2026-09-22 19:49:05'),
(1028, 17, 10, 'Sin Servicio', NULL, 18, 10, 0, 1, '2026-09-22 19:49:05'),
(1029, 17, 11, 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 11, 0, 1, '2026-09-22 19:49:05'),
(1030, 17, 12, 'En Servicio', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 12, 0, 1, '2026-09-22 19:49:05'),
(1031, 17, 13, 'Sin Servicio', NULL, 18, 13, 0, 1, '2026-09-22 19:49:05'),
(1032, 17, 14, 'Sin Servicio', NULL, 18, 14, 0, 1, '2026-09-22 19:49:05'),
(1033, 17, 15, 'Sin Servicio', NULL, 18, 15, 0, 1, '2026-09-22 19:49:05'),
(1034, 17, 16, 'Sin Servicio', NULL, 18, 16, 0, 1, '2026-09-22 19:49:05'),
(1035, 17, 17, 'En Servicio', 'LIM_ZPLL_AGG_1', 18, 17, 0, 1, '2026-09-22 19:49:05'),
(1036, 17, 18, 'En Servicio', 'LIM_ZPLL_AGG_1', 18, 18, 0, 1, '2026-09-22 19:49:05'),
(1037, 17, 19, 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 19, 0, 1, '2026-09-22 19:49:05'),
(1038, 17, 20, 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 20, 0, 1, '2026-09-22 19:49:05'),
(1039, 17, 21, 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 21, 0, 1, '2026-09-22 19:49:05'),
(1040, 17, 22, 'Reservado', 'ENLACE 0100374_LM_ZAPALLAL TO 0105403_LM_CENTENARIO_PUENTE', 18, 22, 0, 1, '2026-09-22 19:49:05'),
(1041, 17, 23, 'En Servicio', 'GESTION DE FO', 18, 23, 0, 1, '2026-09-22 19:49:05'),
(1042, 17, 24, 'En Servicio', 'GESTION DE FO', 18, 24, 0, 1, '2026-09-22 19:49:05'),
(1043, 17, 25, 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', 18, 25, 0, 1, '2026-09-22 19:49:05'),
(1044, 17, 26, 'En Servicio', 'ENLACE C PUENTE TO V PACHACUTEC', 18, 26, 0, 1, '2026-09-22 19:49:05'),
(1045, 17, 27, 'En Servicio', 'SIN ETIQUETA', 18, 27, 0, 1, '2026-09-22 19:49:05'),
(1046, 17, 28, 'En Servicio', 'SIN ETIQUETA', 18, 28, 0, 1, '2026-09-22 19:49:05'),
(1047, 17, 29, 'En Servicio', 'SIN ETIQUETA', 18, 29, 0, 1, '2026-09-22 19:49:05'),
(1048, 17, 30, 'En Servicio', 'SIN ETIQUETA', 18, 30, 0, 1, '2026-09-22 19:49:05'),
(1049, 17, 31, 'En Servicio', 'LM ZAPALLAL TO LM VALLE SAGRADO', 18, 31, 0, 1, '2026-09-22 19:49:05'),
(1050, 17, 32, 'En Servicio', 'LM ZAPALLAL TO LM VALLE SAGRADO', 18, 32, 0, 1, '2026-09-22 19:49:05'),
(1051, 17, 33, 'Sin Servicio', NULL, 18, 33, 0, 1, '2026-09-22 19:49:05'),
(1052, 17, 34, 'Sin Servicio', NULL, 18, 34, 0, 1, '2026-09-22 19:49:05'),
(1053, 17, 35, 'Sin Servicio', NULL, 18, 35, 0, 1, '2026-09-22 19:49:05'),
(1054, 17, 36, 'Sin Servicio', NULL, 18, 36, 0, 1, '2026-09-22 19:49:05'),
(1055, 17, 37, 'En Servicio', 'SIN ETIQUETA', 18, 37, 0, 1, '2026-09-22 19:49:05'),
(1056, 17, 38, 'En Servicio', 'SIN ETIQUETA', 18, 38, 0, 1, '2026-09-22 19:49:05'),
(1057, 17, 39, 'Sin Servicio', NULL, 18, 39, 0, 1, '2026-09-22 19:49:05'),
(1058, 17, 40, 'Sin Servicio', NULL, 18, 40, 0, 1, '2026-09-22 19:49:05'),
(1059, 17, 41, 'Sin Servicio', NULL, 18, 41, 0, 1, '2026-09-22 19:49:05'),
(1060, 17, 42, 'Sin Servicio', NULL, 18, 42, 0, 1, '2026-09-22 19:49:05'),
(1061, 17, 43, 'En Servicio', 'ODF RACK ZAP -01', 18, 43, 0, 1, '2026-09-22 19:49:05'),
(1062, 17, 44, 'En Servicio', 'ODF RACK ZAP -01', 18, 44, 0, 1, '2026-09-22 19:49:05'),
(1063, 17, 45, 'En Servicio', 'PUENTE PIEDRA TO TRAPICHE', 18, 45, 0, 1, '2026-09-22 19:49:05'),
(1064, 17, 46, 'En Servicio', 'PUENTE PIEDRA TO TRAPICHE', 18, 46, 0, 1, '2026-09-22 19:49:05'),
(1065, 17, 47, 'En Servicio', 'GESTION DE FO', 18, 47, 0, 1, '2026-09-22 19:49:05'),
(1066, 17, 48, 'En Servicio', 'GESTION DE FO', 18, 48, 0, 1, '2026-09-22 19:49:05'),
(1067, 17, 49, 'En Servicio', '0100374_LM_ZAPALLAL TO LAS_LOMAS_ZAPALLAL', 18, 49, 0, 1, '2026-09-22 19:49:05'),
(1068, 17, 50, 'En Servicio', '0100374_LM_ZAPALLAL TO LAS_LOMAS_ZAPALLAL', 18, 50, 0, 1, '2026-09-22 19:49:05'),
(1069, 17, 51, 'Sin Servicio', NULL, 18, 51, 0, 1, '2026-09-22 19:49:05'),
(1070, 17, 52, 'Sin Servicio', NULL, 18, 52, 0, 1, '2026-09-22 19:49:05'),
(1071, 17, 53, 'Sin Servicio', NULL, 18, 53, 0, 1, '2026-09-22 19:49:05'),
(1072, 17, 54, 'Sin Servicio', NULL, 18, 54, 0, 1, '2026-09-22 19:49:05'),
(1073, 17, 55, 'Sin Servicio', NULL, 18, 55, 0, 1, '2026-09-22 19:49:05'),
(1074, 17, 56, 'Sin Servicio', NULL, 18, 56, 0, 1, '2026-09-22 19:49:05'),
(1075, 17, 57, 'Sin Servicio', NULL, 18, 57, 0, 1, '2026-09-22 19:49:05'),
(1076, 17, 58, 'Sin Servicio', NULL, 18, 58, 0, 1, '2026-09-22 19:49:05'),
(1077, 17, 59, 'Sin Servicio', NULL, 18, 59, 0, 1, '2026-09-22 19:49:05'),
(1078, 17, 60, 'Sin Servicio', NULL, 18, 60, 0, 1, '2026-09-22 19:49:05'),
(1079, 17, 61, 'Sin Servicio', NULL, 18, 61, 0, 1, '2026-09-22 19:49:05'),
(1080, 17, 62, 'Sin Servicio', NULL, 18, 62, 0, 1, '2026-09-22 19:49:05'),
(1081, 17, 63, 'Sin Servicio', NULL, 18, 63, 0, 1, '2026-09-22 19:49:05'),
(1082, 17, 64, 'Sin Servicio', NULL, 18, 64, 0, 1, '2026-09-22 19:49:05'),
(1083, 17, 65, 'Sin Servicio', NULL, 18, 65, 0, 1, '2026-09-22 19:49:05'),
(1084, 17, 66, 'Sin Servicio', NULL, 18, 66, 0, 1, '2026-09-22 19:49:05'),
(1085, 17, 67, 'Sin Servicio', NULL, 18, 67, 0, 1, '2026-09-22 19:49:05'),
(1086, 17, 68, 'Sin Servicio', NULL, 18, 68, 0, 1, '2026-09-22 19:49:05'),
(1087, 17, 69, 'Sin Servicio', NULL, 18, 69, 0, 1, '2026-09-22 19:49:05'),
(1088, 17, 70, 'Sin Servicio', NULL, 18, 70, 0, 1, '2026-09-22 19:49:05'),
(1089, 17, 71, 'Sin Servicio', NULL, 18, 71, 0, 1, '2026-09-22 19:49:05'),
(1090, 17, 72, 'Sin Servicio', NULL, 18, 72, 0, 1, '2026-09-22 19:49:05'),
(1091, 17, 73, 'Sin Servicio', NULL, 18, 73, 0, 1, '2026-09-22 19:49:05'),
(1092, 17, 74, 'Sin Servicio', NULL, 18, 74, 0, 1, '2026-09-22 19:49:05'),
(1093, 17, 75, 'En Servicio', 'LIM_ZPLL_AGG_1', 18, 75, 0, 1, '2026-09-22 19:49:05'),
(1094, 17, 76, 'En Servicio', 'LIM_ZPLL_AGG_1', 18, 76, 0, 1, '2026-09-22 19:49:05'),
(1095, 17, 77, 'Sin Servicio', NULL, 18, 77, 0, 1, '2026-09-22 19:49:05'),
(1096, 17, 78, 'Sin Servicio', NULL, 18, 78, 0, 1, '2026-09-22 19:49:05'),
(1097, 17, 79, 'Sin Servicio', NULL, 18, 79, 0, 1, '2026-09-22 19:49:05'),
(1098, 17, 80, 'Sin Servicio', NULL, 18, 80, 0, 1, '2026-09-22 19:49:05'),
(1099, 17, 81, 'Sin Servicio', NULL, 18, 81, 0, 1, '2026-09-22 19:49:05'),
(1100, 17, 82, 'Sin Servicio', NULL, 18, 82, 0, 1, '2026-09-22 19:49:05'),
(1101, 17, 83, 'Sin Servicio', NULL, 18, 83, 0, 1, '2026-09-22 19:49:05'),
(1102, 17, 84, 'Sin Servicio', NULL, 18, 84, 0, 1, '2026-09-22 19:49:05'),
(1103, 17, 85, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 85, 0, 1, '2026-09-22 19:49:05'),
(1104, 17, 86, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 86, 0, 1, '2026-09-22 19:49:05'),
(1105, 17, 87, 'Sin Servicio', NULL, 18, 87, 0, 1, '2026-09-22 19:49:05'),
(1106, 17, 88, 'Sin Servicio', NULL, 18, 88, 0, 1, '2026-09-22 19:49:05'),
(1107, 17, 89, 'Sin Servicio', NULL, 18, 89, 0, 1, '2026-09-22 19:49:05'),
(1108, 17, 90, 'Sin Servicio', NULL, 18, 90, 0, 1, '2026-09-22 19:49:05'),
(1109, 17, 91, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 91, 0, 1, '2026-09-22 19:49:05'),
(1110, 17, 92, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 92, 0, 1, '2026-09-22 19:49:05'),
(1111, 17, 93, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 93, 0, 1, '2026-09-22 19:49:05'),
(1112, 17, 94, 'En Servicio', 'LM_ZAPALLAL TO CEMENTERIO PUENTE', 18, 94, 0, 1, '2026-09-22 19:49:05'),
(1113, 17, 95, 'Sin Servicio', NULL, 18, 95, 0, 1, '2026-09-22 19:49:05'),
(1114, 17, 96, 'Sin Servicio', NULL, 18, 96, 0, 1, '2026-09-22 19:49:05'),
(1115, 17, 97, 'Sin Servicio', NULL, 18, 97, 0, 1, '2026-09-22 19:49:05'),
(1116, 17, 98, 'Sin Servicio', NULL, 18, 98, 0, 1, '2026-09-22 19:49:05'),
(1117, 17, 99, 'Sin Servicio', NULL, 18, 99, 0, 1, '2026-09-22 19:49:05'),
(1118, 17, 100, 'Sin Servicio', NULL, 18, 100, 0, 1, '2026-09-22 19:49:05'),
(1119, 17, 101, 'Sin Servicio', NULL, 18, 101, 0, 1, '2026-09-22 19:49:05'),
(1120, 17, 102, 'Sin Servicio', NULL, 18, 102, 0, 1, '2026-09-22 19:49:05'),
(1121, 17, 103, 'Sin Servicio', NULL, 18, 103, 0, 1, '2026-09-22 19:49:05'),
(1122, 17, 104, 'Sin Servicio', NULL, 18, 104, 0, 1, '2026-09-22 19:49:05'),
(1123, 17, 105, 'Sin Servicio', NULL, 18, 105, 0, 1, '2026-09-22 19:49:05'),
(1124, 17, 106, 'Sin Servicio', NULL, 18, 106, 0, 1, '2026-09-22 19:49:05'),
(1125, 17, 107, 'Sin Servicio', NULL, 18, 107, 0, 1, '2026-09-22 19:49:05'),
(1126, 17, 108, 'Sin Servicio', NULL, 18, 108, 0, 1, '2026-09-22 19:49:05'),
(1127, 17, 109, 'Sin Servicio', NULL, 18, 109, 0, 1, '2026-09-22 19:49:05'),
(1128, 17, 110, 'Sin Servicio', NULL, 18, 110, 0, 1, '2026-09-22 19:49:05'),
(1129, 17, 111, 'Sin Servicio', NULL, 18, 111, 0, 1, '2026-09-22 19:49:05'),
(1130, 17, 112, 'Sin Servicio', NULL, 18, 112, 0, 1, '2026-09-22 19:49:05'),
(1131, 17, 113, 'Sin Servicio', NULL, 18, 113, 0, 1, '2026-09-22 19:49:05'),
(1132, 17, 114, 'Sin Servicio', NULL, 18, 114, 0, 1, '2026-09-22 19:49:05'),
(1133, 17, 115, 'Sin Servicio', 'SIN ACOPLADOR', 18, 115, 0, 1, '2026-09-22 19:49:05'),
(1134, 17, 116, 'Sin Servicio', 'SIN ACOPLADOR', 18, 116, 0, 1, '2026-09-22 19:49:05'),
(1135, 17, 117, 'Sin Servicio', NULL, 18, 117, 0, 1, '2026-09-22 19:49:05'),
(1136, 17, 118, 'Sin Servicio', NULL, 18, 118, 0, 1, '2026-09-22 19:49:05'),
(1137, 17, 119, 'Sin Servicio', NULL, 18, 119, 0, 1, '2026-09-22 19:49:05'),
(1138, 17, 120, 'Sin Servicio', NULL, 18, 120, 0, 1, '2026-09-22 19:49:05'),
(1139, 17, 121, 'Sin Servicio', NULL, 18, 121, 0, 1, '2026-09-22 19:49:05'),
(1140, 17, 122, 'Sin Servicio', NULL, 18, 122, 0, 1, '2026-09-22 19:49:05'),
(1141, 17, 123, 'Sin Servicio', NULL, 18, 123, 0, 1, '2026-09-22 19:49:05'),
(1142, 17, 124, 'Sin Servicio', NULL, 18, 124, 0, 1, '2026-09-22 19:49:05'),
(1143, 17, 125, 'Sin Servicio', NULL, 18, 125, 0, 1, '2026-09-22 19:49:05'),
(1144, 17, 126, 'Sin Servicio', NULL, 18, 126, 0, 1, '2026-09-22 19:49:05'),
(1145, 17, 127, 'Sin Servicio', NULL, 18, 127, 0, 1, '2026-09-22 19:49:05'),
(1146, 17, 128, 'Sin Servicio', NULL, 18, 128, 0, 1, '2026-09-22 19:49:05'),
(1147, 17, 129, 'Sin Servicio', NULL, 18, 129, 0, 1, '2026-09-22 19:49:05'),
(1148, 17, 130, 'Sin Servicio', NULL, 18, 130, 0, 1, '2026-09-22 19:49:05'),
(1149, 17, 131, 'Sin Servicio', NULL, 18, 131, 0, 1, '2026-09-22 19:49:05'),
(1150, 17, 132, 'Sin Servicio', NULL, 18, 132, 0, 1, '2026-09-22 19:49:05'),
(1151, 17, 133, 'Sin Servicio', NULL, 18, 133, 0, 1, '2026-09-22 19:49:05'),
(1152, 17, 134, 'Sin Servicio', NULL, 18, 134, 0, 1, '2026-09-22 19:49:05'),
(1153, 17, 135, 'Sin Servicio', NULL, 18, 135, 0, 1, '2026-09-22 19:49:05'),
(1154, 17, 136, 'Sin Servicio', NULL, 18, 136, 0, 1, '2026-09-22 19:49:05'),
(1155, 17, 137, 'Sin Servicio', NULL, 18, 137, 0, 1, '2026-09-22 19:49:05'),
(1156, 17, 138, 'Sin Servicio', NULL, 18, 138, 0, 1, '2026-09-22 19:49:05'),
(1157, 17, 139, 'Sin Servicio', NULL, 18, 139, 0, 1, '2026-09-22 19:49:05'),
(1158, 17, 140, 'Sin Servicio', NULL, 18, 140, 0, 1, '2026-09-22 19:49:05'),
(1159, 17, 141, 'Sin Servicio', NULL, 18, 141, 0, 1, '2026-09-22 19:49:05'),
(1160, 17, 142, 'Sin Servicio', NULL, 18, 142, 0, 1, '2026-09-22 19:49:05'),
(1161, 17, 143, 'Sin Servicio', 'SIN ACOPLADOR', 18, 143, 0, 1, '2026-09-22 19:49:05'),
(1162, 17, 144, 'Sin Servicio', 'SIN ACOPLADOR', 18, 144, 0, 1, '2026-09-22 19:49:05');

--
-- Disparadores `puerto`
--
DELIMITER $$
CREATE TRIGGER `trg_auditar_cambio_estado` BEFORE UPDATE ON `puerto` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO HistorialCambiosPuerto (id_puerto, id_usuario, estado_anterior, estado_nuevo, observacion, fecha_cambio)
        VALUES (NEW.id_puerto, NEW.modificado_por, OLD.estado, NEW.estado, NEW.descripcion, NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `site`
--

CREATE TABLE `site` (
  `id_site` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `departamento` varchar(80) DEFAULT NULL,
  `distrito` varchar(80) DEFAULT NULL,
  `tipo` enum('PAG','AGG','SITE','AGG/PAG') NOT NULL DEFAULT 'SITE',
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `site`
--

INSERT INTO `site` (`id_site`, `nombre`, `direccion`, `departamento`, `distrito`, `tipo`, `activo`) VALUES
(6, '0100374_LM_Zapallal', 'Cerro Paredes Alt Km-37 de la panamericana norte, lado derecho AA.HH. Bella Aurora', 'LIMA', 'PUENTE PIEDRA', 'AGG', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` enum('Administrador','Supervisor','Tecnico') NOT NULL,
  `activo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `correo`, `contrasena_hash`, `rol`, `activo`) VALUES
(1, 'Administrador', 'admin@telefonica.com', '$2y$10$nakf51F3Xqj7FqYPEQRTs.I0sw8IuFByLzXoI.7Ca8eNxc4LMo0bu', 'Administrador', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cablealimentacion`
--
ALTER TABLE `cablealimentacion`
  ADD PRIMARY KEY (`id_cable`),
  ADD KEY `id_site` (`id_site`);

--
-- Indices de la tabla `historialcambiospuerto`
--
ALTER TABLE `historialcambiospuerto`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_historial_puerto` (`id_puerto`),
  ADD KEY `fk_historial_usuario` (`id_usuario`);

--
-- Indices de la tabla `odf`
--
ALTER TABLE `odf`
  ADD PRIMARY KEY (`id_odf`),
  ADD KEY `id_site` (`id_site`);

--
-- Indices de la tabla `puerto`
--
ALTER TABLE `puerto`
  ADD PRIMARY KEY (`id_puerto`),
  ADD UNIQUE KEY `uq_puerto_odf_posicion` (`id_odf`,`posicion`),
  ADD UNIQUE KEY `uq_cable_hilo` (`id_cable_conectado`,`numero_hilo`),
  ADD KEY `fk_puerto_modificado_por` (`modificado_por`);

--
-- Indices de la tabla `site`
--
ALTER TABLE `site`
  ADD PRIMARY KEY (`id_site`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cablealimentacion`
--
ALTER TABLE `cablealimentacion`
  MODIFY `id_cable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `historialcambiospuerto`
--
ALTER TABLE `historialcambiospuerto`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `odf`
--
ALTER TABLE `odf`
  MODIFY `id_odf` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `puerto`
--
ALTER TABLE `puerto`
  MODIFY `id_puerto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1631;

--
-- AUTO_INCREMENT de la tabla `site`
--
ALTER TABLE `site`
  MODIFY `id_site` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cablealimentacion`
--
ALTER TABLE `cablealimentacion`
  ADD CONSTRAINT `cablealimentacion_ibfk_1` FOREIGN KEY (`id_site`) REFERENCES `site` (`id_site`);

--
-- Filtros para la tabla `historialcambiospuerto`
--
ALTER TABLE `historialcambiospuerto`
  ADD CONSTRAINT `fk_historial_puerto` FOREIGN KEY (`id_puerto`) REFERENCES `puerto` (`id_puerto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `odf`
--
ALTER TABLE `odf`
  ADD CONSTRAINT `odf_ibfk_1` FOREIGN KEY (`id_site`) REFERENCES `site` (`id_site`);

--
-- Filtros para la tabla `puerto`
--
ALTER TABLE `puerto`
  ADD CONSTRAINT `fk_puerto_modificado_por` FOREIGN KEY (`modificado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `puerto_ibfk_1` FOREIGN KEY (`id_odf`) REFERENCES `odf` (`id_odf`),
  ADD CONSTRAINT `puerto_ibfk_2` FOREIGN KEY (`id_cable_conectado`) REFERENCES `cablealimentacion` (`id_cable`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
