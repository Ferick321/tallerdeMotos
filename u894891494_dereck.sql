-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 05-09-2025 a las 14:01:16
-- Versión del servidor: 10.11.10-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u894891494_dereck`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cambios_aceite`
--

CREATE TABLE `cambios_aceite` (
  `id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `kilometraje_actual` int(11) NOT NULL,
  `proximo_cambio` int(11) NOT NULL,
  `fecha_cambio` timestamp NULL DEFAULT current_timestamp(),
  `responsable_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombres`, `cedula`, `telefono`, `email`, `direccion`, `fecha_registro`) VALUES
(38, 'Erick Anderson Pallo Quisilema', '1726354051', '0978629854', 'skrillexlmntrix@outlook.com', 'POMASQUI BARRIO SAN JOSE', '2025-07-04 04:44:04'),
(39, 'Santiago Velasco', '1715585814', '593969010428', 'unlargocamino@hotmail.com', 'Santa Prisca', '2025-07-05 17:51:46'),
(40, 'CONSUMIDOR FINAL', '9999999999', '0991196092', 'patosdj@gmail.com', 'pomasqui', '2025-07-09 19:11:19'),
(41, 'Javier Alcivar', '1310921661', '0991367838', 'javigip@hotmail.com', '', '2025-07-09 19:40:15'),
(42, 'Erick Joshua', '1728661339', '0979084330', 'erickjoshuasalazar011@gmail.com', 'San Antonio', '2025-07-14 23:09:49'),
(43, 'Beltrán Luis', '1002781019', '0995624647', 'lfbmedicine@gmail.com', 'Calderón', '2025-07-15 17:11:16'),
(44, 'Raúl Gómez', '1706352331', '0998144149', '', 'Casales buena ventura', '2025-07-16 00:28:58'),
(45, 'Adrian Arrubla', '1716270101', '0989279908', '', 'Av.la prensa y manta quito', '2025-07-20 21:42:39'),
(46, 'Damian Zambrano', '1728229236', '0995666371', 'damnale69@gmail.com', 'San Antonio', '2025-07-21 23:35:42'),
(47, 'Cesar Arias', '1714224167', '0967184652', 'cesar.arias1@outlook.es', 'San Antonio', '2025-07-22 20:47:29'),
(48, 'Díana malan', '1750571554', '0995049726', 'danilopin@gmail.com', 'Pomasqui', '2025-07-24 16:55:59'),
(49, 'Angel Jiménez', '1104212921', '0997214529', '', 'Pomasqui', '2025-07-25 14:32:21'),
(50, 'Andrés Guachamin', '1722633060', '0987894037', 'andymauro3@gmail.com', 'Mitad del mundo', '2025-07-26 14:55:35'),
(51, 'Kewin González', '1755558665', '0967997750', 'gonzaleskevin112@gmail.com', 'Carapungo', '2025-07-27 22:10:08'),
(52, 'Luis triviño', '1310110190', '0986131715', '', 'Pomasqui', '2025-07-28 13:32:03'),
(53, 'Marcelo Trujillo', '0201139060', '0994197962', '', 'Pomasqui', '2025-07-28 19:29:18'),
(54, 'Kewin Paredes', '1718306440', '0999769533', 'paredesk890@gmail.com', 'Hospital del sur', '2025-07-29 01:40:35'),
(55, 'Tatiana Trujillo', '1720451374', '0996160378', 'tatyplincesa@gmail.com', 'Pomasqui', '2025-07-29 20:00:08'),
(56, 'Joel Morales', '2350923013', '0990382315', 'moralessergio123@hotmail.com', 'Ibarra', '2025-08-01 22:23:06'),
(57, 'Anderson', '17557155560', '0985816893', '', 'Mitad del mundo', '2025-08-02 15:10:19'),
(58, 'Aracely Pallo', '1726354055', '09803545996', 'dddd@d.com', 'pomasq', '2025-08-03 16:00:05'),
(60, 'Walter mogro', '1713276705', '0958877474', '', 'Las casas', '2025-08-08 20:55:28'),
(61, 'Willian wadir', '1719150284', '0980054322', '', 'Calderón', '2025-08-10 22:07:36'),
(62, 'Dario aneloa', '1753645645', '0958694530', 'darioaneloa486@gmail.com', 'Mitad del mundo', '2025-08-12 22:43:42'),
(63, 'Darwin Quinapanta', '1725956419', '0939605091', 'darwinchocho25@gmail.com', 'Mitad del mundo', '2025-08-13 22:33:55'),
(64, 'Rubén Castro', '1761764305', '0984918756', 'rubenmanuel2507@gmail.com', 'Mitad del mundo', '2025-08-13 22:38:53'),
(65, 'Hernan Suquillo', '1717914137', '0988034592', 'hernansuquillo@hotmail.com', 'Mitad del mundo', '2025-08-14 17:01:07'),
(66, 'René Alvan', '1708515141', '0995825495', '', 'Conocoto', '2025-08-15 13:35:51'),
(67, 'Geovanny Guaman', '1726021668', '0994039332', 'geovanny-gu1994@homail.es', 'Calacali', '2025-08-16 13:25:33'),
(68, 'Eduardo Andrade', '1704933215', '0997098813', 'eandrade2856@gmail.com', 'Pomasqui', '2025-08-16 19:19:25'),
(69, 'Abigail Granda', '1754416715', '0998762326', 'abigail_27_08@hotmail.com', 'Mitad del mundo', '2025-08-17 18:58:16'),
(70, 'Edgar Montero', '0200725356', '0982468412', 'emontero45@homail.es', 'La magdalena', '2025-08-18 18:54:51'),
(71, 'Alexandro Morales', '1721233466', '0962698919', '', 'Pomasqui', '2025-08-19 00:06:33'),
(72, 'Raúl Landázuri', '1709845604', '0991026169', '', 'Pomasqui', '2025-08-19 22:26:23'),
(73, 'Patricio chipantashi', '1720797107', '0994159281', 'patosdj@gmail.com', 'Calacali', '2025-08-20 12:04:12'),
(74, 'Javier Almeida', '1715923601', '0998231161', 'nax.diseno@gmail.com', 'Pomasqui', '2025-08-25 20:29:00'),
(75, 'Esteban Cerón', '1718561671', '0996511692', '', 'Guallamaba', '2025-08-25 22:32:14'),
(76, 'Juan Carlos Guerrero', '1716981202', '0987568114', '', 'Caspigasi', '2025-08-25 22:40:24'),
(77, 'Johnny mauricio coka', '1713265013', '0980894355', '', 'Ponciano alto', '2025-08-27 17:21:14'),
(78, 'Jeremi shuguli', '1727871657', '0986933029', 'jeremibdc92@gmail.com', 'Mitad del mundo', '2025-08-28 23:27:55'),
(79, 'Henry Cisneros', '1710533991', '0995162872', '', 'Mitad del mundo', '2025-08-30 12:31:49'),
(80, 'John Meza', '1752399541', '0995601033', 'johnmeza.318@gmail.com', 'Pomasqui', '2025-09-03 23:33:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `tipo` varchar(255) DEFAULT 'general',
  `fecha_ingreso` timestamp NULL DEFAULT current_timestamp(),
  `fecha_entrega_estimada` date DEFAULT NULL,
  `fecha_entrega_real` datetime DEFAULT NULL,
  `novedades` text DEFAULT NULL,
  `estado` enum('recibido','en_proceso','terminado','entregado') DEFAULT 'recibido',
  `tipo_mantenimiento` varchar(50) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `kilometraje_actual` int(11) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimientos`
--

INSERT INTO `mantenimientos` (`id`, `moto_id`, `tipo`, `fecha_ingreso`, `fecha_entrega_estimada`, `fecha_entrega_real`, `novedades`, `estado`, `tipo_mantenimiento`, `responsable_id`, `kilometraje_actual`, `costo`) VALUES
(73, 46, 'cambio_aceite', '2025-07-05 00:50:13', NULL, '2025-07-05 00:51:39', 'ABC y cambio', 'entregado', NULL, 5, 65001, 0.00),
(78, 47, 'general', '2025-07-09 19:28:11', NULL, '2025-08-18 10:13:10', 'Cambio de aceite con mannol', 'entregado', NULL, 6, 69127, 77.50),
(79, 48, 'general', '2025-07-09 19:40:15', NULL, '2025-08-27 03:58:56', 'Cambio aceite y filtro \r\nRevision frenos', 'entregado', NULL, 6, 54898, 0.00),
(81, 48, 'general', '2025-07-09 23:03:29', NULL, '2025-07-09 23:04:07', 'Cambio de aceite y filtro \r\nPastillas de frenos frontales y posterior', 'entregado', NULL, 6, 54899, 0.00),
(85, 49, 'general', '2025-07-14 23:09:49', NULL, '2025-08-15 16:14:51', 'Revisar carburador \r\nRevisar fuga de aceite \r\nY fuga de aceite \r\nEngrasar ejes y ajuste de cadena lubricación \r\nCambio liquido de frenos \r\nRevisar filtro de aire \r\nMoto pierde fuerza \r\nSoldar pata apoyo', 'entregado', NULL, 6, 4210, 0.00),
(86, 49, 'general', '2025-07-14 23:11:41', NULL, '2025-08-15 16:15:12', 'Moto pierde fuerza \r\nCambio liquido de frenos \r\nSoldar pata apoyo \r\nRevisar válvulas \r\nEngrase de ejes \r\nPierde fuerza', 'entregado', NULL, 6, 4211, 0.00),
(87, 49, 'general', '2025-07-14 23:12:12', NULL, '2025-08-15 16:15:01', 'Moto pierde fuerza \r\nCambio liquido de frenos \r\nSoldar pata apoyo \r\nRevisar válvulas \r\nEngrase de ejes \r\nPierde fuerza', 'entregado', NULL, 6, 4211, 0.00),
(88, 49, 'general', '2025-07-14 23:12:12', NULL, '2025-08-15 16:15:07', 'Moto pierde fuerza \r\nCambio liquido de frenos \r\nSoldar pata apoyo \r\nRevisar válvulas \r\nEngrase de ejes \r\nPierde fuerza', 'entregado', NULL, 6, 4211, 0.00),
(89, 50, 'general', '2025-07-15 17:11:16', NULL, '2025-08-15 16:14:39', 'Fabricación cúpula y una revisión preventiva para viaje .\r\nRevisar el caña aceleración de calentamiento no calienta \r\nRevisar sonido con peso suena .', 'entregado', NULL, 6, 18940, 0.00),
(90, 51, 'general', '2025-07-16 00:28:58', NULL, '2025-08-15 16:14:28', 'Cambio aceite y filtro \r\nFiltro de aire \r\nBujías \r\nKit de arrastre \r\nRrulimanes delantero rueda', 'entregado', NULL, 6, 67201, 0.00),
(91, 50, 'general', '2025-07-16 12:34:33', NULL, '2025-08-15 16:14:20', 'Cambio de cúpula \r\nRevisión de caña aceleración no calienta\r\nRevisión general preventivo para viaje', 'entregado', NULL, 6, 18941, 0.00),
(92, 48, 'general', '2025-07-16 12:36:09', NULL, '2025-08-15 16:14:08', 'Cambio kit de arrastre \r\nCambio de bujías \r\nCambio de filtro de aire \r\nCambio de rrulimanes frontal \r\nCambio de aceite y filtro', 'entregado', NULL, 6, 54900, 0.00),
(93, 52, 'general', '2025-07-20 21:42:39', NULL, '2025-08-15 16:14:02', 'Abc para revisión técnica vehicular \r\nAsegurar asiento y base\r\nColocar tapa lateral RH', 'entregado', NULL, 6, 34903, 0.00),
(94, 53, 'general', '2025-07-21 23:35:42', NULL, '2025-08-15 16:13:57', 'Revisar tijera \r\nPoner botón luces', 'entregado', NULL, 6, 11103, 0.00),
(95, 54, 'general', '2025-07-22 20:47:29', NULL, '2025-08-15 16:16:17', 'Limpieza de carburador', 'entregado', NULL, 6, 0, 0.00),
(97, 55, 'general', '2025-07-24 16:55:59', NULL, '2025-08-15 16:15:20', 'Revisión moto se ahoga', 'entregado', NULL, 6, 6819, 0.00),
(99, 56, 'general', '2025-07-25 14:34:07', NULL, '2025-08-15 16:16:13', 'Cambio de aceite con 20/50', 'entregado', NULL, 6, 51245, 0.00),
(101, 57, 'general', '2025-07-26 14:56:00', NULL, '2025-08-15 16:16:08', 'Abc completo \r\nRevisar fuga de aceite parte superior barras', 'entregado', NULL, 6, 16992, 85.00),
(103, 58, 'general', '2025-07-27 22:11:49', NULL, '2025-08-15 16:16:04', 'Revisión motor por sonido \r\nRevisar pierde fuerza \r\nMoto consume aceite', 'entregado', NULL, 6, 110001, 0.00),
(105, 59, 'general', '2025-07-28 13:33:39', NULL, '2025-08-15 16:16:00', 'Abc completo \r\nRevisan barra izquierda golpea \r\nSonido balancines \r\nSonido al rodar llanta delantera \r\nEn la curvas media inestable \r\nRevisar cable acelerador al girar', 'entregado', NULL, 6, 26854, 0.00),
(107, 60, 'general', '2025-07-28 19:30:13', NULL, '2025-08-15 16:15:54', 'Revisión carburador trae suelto\r\nCambio de aceite \r\nRevisión frenos', 'entregado', NULL, 6, 1, 0.00),
(109, 61, 'general', '2025-07-29 01:41:49', NULL, '2025-08-15 16:15:47', 'Revisión moto no prende \r\nTrae piezas sueltas', 'entregado', NULL, 6, 53001, 0.00),
(110, 62, 'general', '2025-07-29 20:00:08', NULL, '2025-08-15 16:15:25', 'Cambio de aceite \r\nRevisar moto floja \r\nLavar moto \r\nRevisión general', 'entregado', NULL, 6, 9756, 0.00),
(113, 63, 'general', '2025-08-01 22:23:37', NULL, '2025-08-15 16:13:49', 'Revision motor posible daño interno\r\n\r\nRevisar motor se apagó manejando\r\n', 'entregado', NULL, 6, 24301, 0.00),
(118, 67, 'general', '2025-08-08 20:55:28', NULL, '2025-08-08 20:57:33', 'Cambio y repuestos', 'entregado', NULL, 6, 129807, 68.50),
(119, 68, 'general', '2025-08-10 22:07:36', NULL, '2025-08-10 22:24:51', 'Cambio de aceite después de la reparación', 'entregado', NULL, 6, 38769, 25.50),
(120, 69, 'general', '2025-08-12 22:43:42', NULL, '2025-08-27 04:00:05', 'Reingreso.revisar sonido de motor y revisar cambios duros \r\nCambio kit de arrastre \r\nCambio de pastillas frontales', 'entregado', NULL, 6, 9474, 0.00),
(121, 70, 'general', '2025-08-13 22:33:55', NULL, NULL, 'Revisión moto se apagó', 'recibido', NULL, 6, 71278, 0.00),
(122, 71, 'general', '2025-08-13 22:38:53', NULL, '2025-09-01 02:00:36', 'Revisión moto no prende', 'entregado', NULL, 6, 0, 0.00),
(123, 72, 'general', '2025-08-14 17:01:07', NULL, '2025-09-01 02:00:47', 'Revisión fuga de aceite tapa de válvula', 'entregado', NULL, 6, 59064, 0.00),
(124, 73, 'general', '2025-08-15 13:35:51', NULL, '2025-08-15 16:14:34', 'Abc motor. frenos ,eléctrico .\r\nMoto no prende \r\nRetirar luces extras \r\nArreglar sistema eléctrico de conecciones \r\nCambio retenedor barra LH\r\nCambio de aceite y filtro de aceite', 'entregado', NULL, 6, 35802, 0.00),
(125, 74, 'general', '2025-08-16 13:25:33', NULL, '2025-09-01 02:00:03', 'Abc de 8500', 'entregado', NULL, 6, 8437, 0.00),
(126, 75, 'general', '2025-08-17 18:58:16', NULL, '2025-09-01 01:59:55', 'Revisión moto no prende', 'entregado', NULL, 6, 44807, 0.00),
(127, 47, 'general', '2025-08-17 20:59:41', NULL, NULL, 'Abc frenos\r\nRevisión suspensión frontal \r\nRevisión sistema eléctrico \r\nRevisión embrague \r\nRevisión cable acelerador \r\nRevisión para revisión técnica vehicular', 'recibido', NULL, 6, 69128, 0.00),
(128, 76, 'general', '2025-08-18 18:54:51', NULL, '2025-09-01 01:59:24', 'Abc rutinario\r\nCambio aceite y filtro \r\nAbc frenos \r\nTensión cadena y engrase \r\nLubricación Guayas', 'entregado', NULL, 6, 27712, 0.00),
(129, 77, 'general', '2025-08-19 00:06:33', NULL, '2025-09-01 01:59:11', 'Revision Bendix \r\nCambio de aceite \r\nRevisión carburador', 'entregado', NULL, 6, 951, 0.00),
(130, 78, 'general', '2025-08-19 22:26:23', NULL, '2025-09-01 01:58:59', 'Cambio de piezas \r\nGuarda fango frontal \r\nFaro frontal \r\nDireccionales 2 \r\nVelocímetro', 'entregado', NULL, 6, 44283, 0.00),
(131, 79, 'general', '2025-08-20 12:04:12', NULL, NULL, 'Cambio de aceite', 'recibido', NULL, 6, 42779, 0.00),
(132, 80, 'general', '2025-08-25 20:29:00', NULL, '2025-09-01 01:58:44', 'Abc cambio aceite \r\nRevisar foco stop guía\r\nRevision frenos \r\nRevisión templada de cadena \r\nRevisión direccionales izquierdo', 'entregado', NULL, 6, 47582, 0.00),
(133, 81, 'general', '2025-08-25 22:32:14', NULL, NULL, 'Revisión motor sin fuerza', 'recibido', NULL, 6, 0, 0.00),
(134, 82, 'general', '2025-08-25 22:40:24', NULL, '2025-09-01 01:58:30', 'Revisión de moto no prende \r\nRevisión fuga de aceite lado izquierdo', 'entregado', NULL, 6, 75517, 0.00),
(135, 83, 'general', '2025-08-27 17:21:14', NULL, NULL, 'Revisión moto no desarrolla \r\nPrende F1 \r\nRevisión pata de apoyo \r\nRevisión manija embrague \r\nCentrada dirección \r\nArreglo sistema eléctrico \r\nHacer espaciador de base tanque', 'recibido', NULL, 6, 51558, 0.00),
(136, 84, 'general', '2025-08-28 23:27:55', NULL, '2025-09-01 01:58:17', 'Cambio disco de freno frontal \r\nCambio llanta posterior \r\nCambio liquido de frenos \r\nRevisión frenos \r\nRevisar para revisión vehicular', 'entregado', NULL, 6, 28975, 0.00),
(137, 85, 'general', '2025-08-30 12:31:49', NULL, '2025-09-01 01:57:22', 'Abc completo \r\nRevisión sonido parte motor lado izquierdo', 'entregado', NULL, 6, 27511, 0.00),
(138, 86, 'general', '2025-08-30 18:46:45', NULL, '2025-09-01 01:57:15', 'Cambio de aceite y pastillas posterior \r\nRetirar luces exploradores', 'entregado', 'cambio_aceite', 6, 65, 0.00),
(139, 87, 'general', '2025-09-01 01:55:35', NULL, '2025-09-01 02:00:21', 'Cambio de aceite \r\nCambio filtro de aceite \r\nRevisión frenos \r\nTensión de cadena y lubricación', 'entregado', 'cambio_aceite', 6, 24, 0.00),
(140, 88, 'general', '2025-09-03 23:33:34', NULL, '2025-09-03 23:35:36', 'Abc completo', 'entregado', NULL, 6, 25424, 0.00);

--
-- Disparadores `mantenimientos`
--
DELIMITER $$
CREATE TRIGGER `after_mantenimiento_entregado` AFTER UPDATE ON `mantenimientos` FOR EACH ROW BEGIN
    DECLARE v_cliente_id INT;
    
    IF NEW.estado = 'entregado' AND OLD.estado != 'entregado' THEN
        -- Obtener el cliente_id a través de la moto
        SELECT cliente_id INTO v_cliente_id 
        FROM motos 
        WHERE id = NEW.moto_id
        LIMIT 1;
        
        -- Verificar si la tabla de historial existe antes de insertar
        SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'historial_entregas');
        
        IF @table_exists > 0 THEN
            INSERT INTO historial_entregas (mantenimiento_id, cliente_id, fecha_entrega)
            VALUES (NEW.id, v_cliente_id, NEW.fecha_entrega_real);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento_historial`
--

CREATE TABLE `mantenimiento_historial` (
  `id` int(11) NOT NULL,
  `mantenimiento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` enum('creacion','modificacion','eliminacion','estado') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mantenimiento_historial`
--

INSERT INTO `mantenimiento_historial` (`id`, `mantenimiento_id`, `usuario_id`, `accion`, `descripcion`, `fecha`) VALUES
(6, 101, 6, 'modificacion', 'Se agregó repuesto: ABC COMPLETO VSTROM (Cantidad: 1, Total: $85)', '2025-08-02 15:22:23'),
(10, 118, 6, 'modificacion', 'Se agregó repuesto: Aceite Suzuki 10W-40 (Cantidad: 3, Total: $28.5)', '2025-08-08 20:55:58'),
(11, 118, 6, 'modificacion', 'Se agregó repuesto: Filtro de aceite vstrom (Cantidad: 1, Total: $15)', '2025-08-08 20:56:17'),
(12, 118, 6, 'modificacion', 'Se agregó repuesto: Mano de obra A domicilio(En la Ciudad) - (Cantidad: 1, Total: $25)', '2025-08-08 20:56:40'),
(13, 118, 6, 'estado', 'Marcado como entregado', '2025-08-08 20:57:33'),
(14, 119, 6, 'modificacion', 'Se agregó repuesto: Aceite Ipone 15W-50 500ML (Cantidad: 1, Total: $7.5)', '2025-08-10 22:13:42'),
(15, 119, 6, 'modificacion', 'Se agregó repuesto: Aceite Ipone 15W-50 (Cantidad: 1, Total: $15)', '2025-08-10 22:14:06'),
(16, 119, 6, 'modificacion', 'Se agregó repuesto: FILTRO DE ACEITE PULSAR 160NS (Cantidad: 1, Total: $3)', '2025-08-10 22:17:30'),
(17, 78, 6, 'estado', 'Marcado como entregado', '2025-08-18 10:13:10'),
(18, 79, 6, 'estado', 'Marcado como entregado', '2025-08-27 03:58:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento_repuestos`
--

CREATE TABLE `mantenimiento_repuestos` (
  `id` int(11) NOT NULL,
  `mantenimiento_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `producto_nombre` varchar(100) NOT NULL,
  `producto_codigo` varchar(50) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `eliminado` tinyint(1) DEFAULT 0,
  `fecha_eliminacion` timestamp NULL DEFAULT NULL,
  `usuario_eliminacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mantenimiento_repuestos`
--

INSERT INTO `mantenimiento_repuestos` (`id`, `mantenimiento_id`, `producto_id`, `producto_nombre`, `producto_codigo`, `cantidad`, `precio_unitario`, `subtotal`, `fecha_registro`, `eliminado`, `fecha_eliminacion`, `usuario_eliminacion`) VALUES
(4, 101, 35, 'ABC COMPLETO VSTROM', 'S004', 1, 85.00, 85.00, '2025-08-02 15:22:23', 0, NULL, NULL),
(8, 118, 59, 'Aceite Suzuki 10W-40', '7861081405916', 3, 9.50, 28.50, '2025-08-08 20:55:58', 0, NULL, NULL),
(9, 118, 75, 'Filtro de aceite vstrom', '240521', 1, 15.00, 15.00, '2025-08-08 20:56:17', 0, NULL, NULL),
(10, 118, 115, 'Mano de obra A domicilio(En la Ciudad) -', 'S5-AltaCc', 1, 25.00, 25.00, '2025-08-08 20:56:40', 0, NULL, NULL),
(11, 119, 125, 'Aceite Ipone 15W-50 500ML', '3700142304505-500ML', 1, 7.50, 7.50, '2025-08-10 22:13:42', 0, NULL, NULL),
(12, 119, 44, 'Aceite Ipone 15W-50', '3700142304505', 1, 15.00, 15.00, '2025-08-10 22:14:06', 0, NULL, NULL),
(13, 119, 126, 'FILTRO DE ACEITE PULSAR 160NS', 'F01', 1, 3.00, 3.00, '2025-08-10 22:17:30', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento_ventas`
--

CREATE TABLE `mantenimiento_ventas` (
  `id` int(11) NOT NULL,
  `mantenimiento_id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimiento_ventas`
--

INSERT INTO `mantenimiento_ventas` (`id`, `mantenimiento_id`, `venta_id`) VALUES
(46, 101, 73),
(50, 118, 77),
(51, 118, 78),
(52, 118, 79),
(53, 119, 80),
(54, 119, 81),
(55, 119, 82);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motos`
--

CREATE TABLE `motos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `kilometraje` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `proximo_cambio_aceite` int(11) DEFAULT NULL,
  `fecha_ultimo_cambio` date DEFAULT NULL,
  `ultimo_cambio_aceite` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `motos`
--

INSERT INTO `motos` (`id`, `cliente_id`, `marca`, `modelo`, `placa`, `serie`, `color`, `kilometraje`, `fecha_registro`, `proximo_cambio_aceite`, `fecha_ultimo_cambio`, `ultimo_cambio_aceite`) VALUES
(46, 38, 'Suzuki', 'Gixxer 2019', 'Ix043m', '', 'Roja con plomo', 70001, '2025-07-04 04:44:04', 80000, '2025-07-09', NULL),
(47, 39, 'Kawasaki', 'ZX6RR 2006', 'HB530T', 'JkAZX4N106A005627', 'Verde', 69128, '2025-07-05 17:51:46', 77000, '2025-07-09', NULL),
(48, 41, 'Suzuki', 'Vstrom 650 2023', 'JU827A', '9fsc733hxrc100245', 'Negro', 54900, '2025-07-09 19:40:15', 57898, NULL, NULL),
(49, 42, 'Daytona', '250cc', 'Ki662T', 'Ltzpcnle016347', 'Negra', 4211, '2025-07-14 23:09:49', NULL, NULL, NULL),
(50, 43, 'Yamaha', '2021', 'JA486H', 'Me1rg4256m2035978', 'Azul', 18941, '2025-07-15 17:11:16', 21940, NULL, NULL),
(51, 44, 'Suzuki', '2019', 'Is820H', '9FSC733H7KC100337', 'Amarillo', 67201, '2025-07-16 00:28:58', 70201, NULL, NULL),
(52, 45, 'Keeway súperlight', '200', 'Io098q', 'LBBPGM2G8JB802503', 'Negro', 34903, '2025-07-20 21:42:39', 36903, NULL, NULL),
(53, 46, 'Bajaj', 'Ct100', 'Kg345q', 'Md2b37AX8swd51708', 'Negro', 11110, '2025-07-21 23:35:42', 11210, NULL, NULL),
(54, 47, 'Ktm', '2015 exc300', 'Sin', '00', 'Naranja', 1, '2025-07-22 20:47:29', NULL, NULL, NULL),
(55, 48, 'Z1', 'Turismo pro 250cc', 'Kh358D', 'LDlpcnla4r10300290', 'Negro', 6819, '2025-07-24 16:55:59', NULL, NULL, NULL),
(56, 49, 'Suzuki', 'Intruder 150cc 2019', 'JL142k', 'Mb5dy1187K8100272', 'Gris', 51245, '2025-07-25 14:32:21', NULL, NULL, NULL),
(57, 50, 'Ktm', '790 2021', 'Jl550B', 'Wbkts3406lm795084', 'Blanco y negro', 16992, '2025-07-26 14:55:35', NULL, NULL, NULL),
(58, 51, 'Suzuki', 'Gsxs150cc 2019', 'It684F', 'Mh9dl22A1kj100417', 'Negro', 110001, '2025-07-27 22:10:08', NULL, NULL, NULL),
(59, 52, 'Z1', '300cc 2022', 'Ka026B', 'Ldlpcpla8n1003549', 'Negro', 26854, '2025-07-28 13:32:03', NULL, NULL, NULL),
(60, 53, 'Sin nombre', '000', '000', '00', 'Rojo', 1, '2025-07-28 19:29:18', NULL, NULL, NULL),
(61, 54, 'Suzuki', 'Vstrom 650XT 2020', 'Iy277F', '9fsc733H5lc101021', 'Negro', 53001, '2025-07-29 01:40:35', NULL, NULL, NULL),
(62, 55, 'Yamaha', 'Rayzr 115', 'Jm499M', 'ME1Se77l5n3062682', 'Azul', 9757, '2025-07-29 20:00:08', NULL, NULL, NULL),
(63, 56, 'Daytona', 'Xpawer250. 2023', 'Jv260Z', '', 'Roja', 24301, '2025-08-01 22:23:06', NULL, NULL, NULL),
(67, 60, 'Suzuki', '2016', 'Ih913x', '', 'Blanca', 129807, '2025-08-08 20:55:28', 159807, NULL, NULL),
(68, 61, 'Pulsar', '160ns 2021', 'Jj542D', 'MD2A92CX3MCJ07346', 'Blanco', 38769, '2025-08-10 22:07:36', 39769, NULL, NULL),
(69, 62, 'Motor 1', 'Suzukida 2013', 'HV660J', 'LP6pCK3B4D0FF2398', 'Negro', 9474, '2025-08-12 22:43:42', NULL, NULL, NULL),
(70, 63, 'Ranger', '250- 2018', 'Iq399P', 'Laeearcm8jh061017', 'Negra', 71278, '2025-08-13 22:33:55', NULL, NULL, NULL),
(71, 64, 'Ranger', 'Gy8 200 - 2018', 'IQ592J', 'Lrsjcml03j0224684', 'Rojo', 0, '2025-08-13 22:38:53', NULL, NULL, NULL),
(72, 65, 'Honda', 'Cbf125 2015', 'Ii247w', 'G01545296', 'Rojo', 59064, '2025-08-14 17:01:07', NULL, NULL, NULL),
(73, 66, 'Suzuki', 'Vstrom1000A 2018', 'IW719A', 'JS1DD1245J0107431', 'Amarillo', 35802, '2025-08-15 13:35:51', 38802, NULL, NULL),
(74, 67, 'Yamaha', 'ZR115', 'KE862H', 'ME1SE77L7R3091770', 'Verde', 8437, '2025-08-16 13:25:33', NULL, NULL, NULL),
(75, 69, 'Suzuki', 'Ur110 2017', 'I0480F', 'MB8CE46A5H8101564', 'Rojo', 44807, '2025-08-17 18:58:16', NULL, NULL, NULL),
(76, 70, 'Suzuki', 'Gs500- 2016', 'iK109X', '9FSGM51AXGC006035', 'Negro', 27712, '2025-08-18 18:54:51', NULL, NULL, NULL),
(77, 71, 'Daytona', 'Wingevo 200cc 2022', 'JQ', 'Leapcm0dxn0048944', 'Negro', 951, '2025-08-19 00:06:33', NULL, NULL, NULL),
(78, 72, 'Suzuki', 'Gn125  2011', 'HC528N', '9FSNF41B5BC08729', 'Negro', 44283, '2025-08-19 22:26:23', NULL, NULL, NULL),
(79, 73, 'Suzuki', 'UK110', 'JR234R', '9FSCE47GPC102895', 'Rojo', 42779, '2025-08-20 12:04:12', NULL, NULL, NULL),
(80, 74, 'Honda', 'Shadonw 600', 'Ia238z', 'Jh2pc2135vm403490', 'Negro', 47582, '2025-08-25 20:29:00', NULL, NULL, NULL),
(81, 75, 'Jhon deere', 'Tractor', '5431', 'Sin', 'Verde', 0, '2025-08-25 22:32:14', NULL, NULL, NULL),
(82, 76, 'Daytona', 'Gp1 250', 'Jj095V', 'Lkxpd00043103', 'Gris', 75517, '2025-08-25 22:40:24', NULL, NULL, NULL),
(83, 77, 'Suzuki', 'Vstrom 650 -2007', 'Hh834F', 'jS1VP54A572100928', 'Negro', 51558, '2025-08-27 17:21:14', NULL, NULL, NULL),
(84, 78, 'Tuko', 'Cr3 200', 'Jj018F', 'Llcjpl4f7na50050', 'Blanca', 28975, '2025-08-28 23:27:55', NULL, NULL, NULL),
(85, 79, 'Suzuki', 'Loncin cr5  250cc', 'JT417S', 'LLCLPMCA9PA501893', 'Gris', 27511, '2025-08-30 12:31:49', NULL, NULL, NULL),
(86, 43, 'Suzuki', 'Vstrom 650 XT 2020', 'Jf300L', '9fsc733HLC101461', 'Amarillo', 65, '2025-08-30 18:46:45', 1665, '2025-08-30', NULL),
(87, 41, 'Suzuki', 'Vstrom 650xt 2024', 'Kc200U', '', 'Negro', 24, '2025-09-01 01:55:35', 1624, '2025-09-01', NULL),
(88, 80, 'Suzuki', 'Gsxf 150 2019', 'ji204A', 'MB8NG4BB6K8300552', 'Rojo', 25424, '2025-09-03 23:33:34', 27000, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `titulo`, `mensaje`, `leida`, `fecha`) VALUES
(40, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ix043m', 1, '2025-07-04 04:44:04'),
(41, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: HB530T', 1, '2025-07-05 17:51:46'),
(42, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JU827A', 0, '2025-07-09 19:40:15'),
(45, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ki662T', 0, '2025-07-14 23:09:49'),
(48, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JA486H', 0, '2025-07-15 17:11:16'),
(51, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Is820H', 0, '2025-07-16 00:28:58'),
(54, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Io098q', 0, '2025-07-20 21:42:39'),
(57, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Kg345q', 0, '2025-07-21 23:35:42'),
(60, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Sin', 0, '2025-07-22 20:47:29'),
(63, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Kh358D', 0, '2025-07-24 16:55:59'),
(66, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JL142k', 0, '2025-07-25 14:32:21'),
(69, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jl550B', 0, '2025-07-26 14:55:35'),
(72, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: It684F', 0, '2025-07-27 22:10:08'),
(75, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ka026B', 0, '2025-07-28 13:32:03'),
(78, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: 000', 0, '2025-07-28 19:29:18'),
(81, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Iy277F', 0, '2025-07-29 01:40:35'),
(84, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jm499M', 0, '2025-07-29 20:00:08'),
(87, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jv260Z', 0, '2025-08-01 22:23:06'),
(90, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Kh231f', 0, '2025-08-02 15:10:19'),
(93, 5, 'Stock bajo', 'El producto Bujia DR8EA Brasileña 6000 Km (7897707508785) tiene stock bajo: 3 unidades (mínimo recomendado: 5)', 0, '2025-08-02 17:27:38'),
(96, 5, 'Stock bajo', 'El producto Bujia DR8EA Brasileña 6000 Km (7897707508785) tiene stock bajo: 3 unidades (mínimo recomendado: 5)', 0, '2025-08-02 17:29:37'),
(99, 5, 'Stock bajo', 'El producto Bujia C7HSA Brasileña (7897707505517) tiene stock bajo: 1 unidades (mínimo recomendado: 4)', 0, '2025-08-02 17:33:32'),
(102, 5, 'Stock bajo', 'El producto Bujia CR8E Brasileña (7897707509874) tiene stock bajo: 2 unidades (mínimo recomendado: 4)', 0, '2025-08-02 17:35:40'),
(105, 5, 'Stock bajo', 'El producto WD-40 (079567520115) tiene stock bajo: 2 unidades (mínimo recomendado: 3)', 0, '2025-08-02 17:40:15'),
(108, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: iuo123', 0, '2025-08-03 16:00:05'),
(111, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ih913x', 0, '2025-08-08 20:18:04'),
(114, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ih913x', 0, '2025-08-08 20:55:28'),
(117, 5, 'Stock bajo', 'El producto CAMBIO DE ACEITE PULSAR NS160 (S007) tiene stock bajo: 1 unidades (mínimo recomendado: 1000)', 0, '2025-08-10 22:06:34'),
(118, 6, 'Stock bajo', 'El producto CAMBIO DE ACEITE PULSAR NS160 (S007) tiene stock bajo: 1 unidades (mínimo recomendado: 1000)', 0, '2025-08-10 22:06:34'),
(120, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jj542D ', 0, '2025-08-10 22:07:36'),
(121, 6, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jj542D ', 0, '2025-08-10 22:07:36'),
(123, 5, 'Stock bajo', 'El producto Aceite Ipone 15W-50 500ML (3700142304505-500ML) tiene stock bajo: 1 unidades (mínimo recomendado: 50)', 0, '2025-08-10 22:12:27'),
(124, 6, 'Stock bajo', 'El producto Aceite Ipone 15W-50 500ML (3700142304505-500ML) tiene stock bajo: 1 unidades (mínimo recomendado: 50)', 0, '2025-08-10 22:12:27'),
(126, 5, 'Stock bajo', 'El producto FILTRO DE ACEITE PULSAR 160NS (F01) tiene stock bajo: 1 unidades (mínimo recomendado: 3)', 0, '2025-08-10 22:15:26'),
(127, 6, 'Stock bajo', 'El producto FILTRO DE ACEITE PULSAR 160NS (F01) tiene stock bajo: 1 unidades (mínimo recomendado: 3)', 0, '2025-08-10 22:15:26'),
(129, 5, 'Stock bajo', 'El producto FILTRO DE ACEITE PULSAR 160NS (F01) tiene stock bajo: 1 unidades (mínimo recomendado: 3)', 0, '2025-08-10 22:17:02'),
(132, 5, 'Stock bajo', 'El producto Rulimanes NTN 6201-2RS (6201-2RS) tiene stock bajo: 5 unidades (mínimo recomendado: 9)', 0, '2025-08-11 16:25:40'),
(133, 6, 'Stock bajo', 'El producto Rulimanes NTN 6201-2RS (6201-2RS) tiene stock bajo: 5 unidades (mínimo recomendado: 9)', 0, '2025-08-11 16:25:40'),
(135, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: HV660J', 0, '2025-08-12 22:43:42'),
(138, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Iq399P', 0, '2025-08-13 22:33:55'),
(141, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: IQ592J', 0, '2025-08-13 22:38:53'),
(144, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ii247w', 0, '2025-08-14 17:01:07'),
(147, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: IW719A', 0, '2025-08-15 13:35:51'),
(150, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: KE862H', 0, '2025-08-16 13:25:33'),
(153, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: I0480F', 0, '2025-08-17 18:58:16'),
(156, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: iK109X', 0, '2025-08-18 18:54:51'),
(159, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JQ ', 0, '2025-08-19 00:06:33'),
(162, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: HC528N', 0, '2025-08-19 22:26:23'),
(165, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JR234R', 0, '2025-08-20 12:04:12'),
(168, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ia238z', 0, '2025-08-25 20:29:00'),
(169, 6, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Ia238z', 0, '2025-08-25 20:29:00'),
(171, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: 5431', 0, '2025-08-25 22:32:14'),
(172, 6, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: 5431', 0, '2025-08-25 22:32:14'),
(174, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jj095V', 0, '2025-08-25 22:40:24'),
(175, 6, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jj095V', 0, '2025-08-25 22:40:24'),
(177, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Hh834F', 0, '2025-08-27 17:21:14'),
(178, 6, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Hh834F', 0, '2025-08-27 17:21:14'),
(180, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jj018F', 0, '2025-08-28 23:27:55'),
(183, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: JT417S', 0, '2025-08-30 12:31:49'),
(186, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Jf300L', 0, '2025-08-30 18:46:45'),
(189, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: Kc200U', 0, '2025-09-01 01:55:35'),
(192, 5, 'Nuevo mantenimiento', 'Se ha registrado un nuevo mantenimiento para la moto placa: ji204A', 0, '2025-09-03 23:33:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `marca`, `categoria`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `activo`) VALUES
(30, '0001', 'LUBRICANTE DE CADENA- CHAIN GUARD', '', 'MAXIMA', 'LUBRICANTES', 12.00, 15.00, 1, 1, 1),
(31, 'S001', 'ABC COMPLETO MULTIMARCA', '', 'Patsmotos', 'Mano Obra', 1.00, 40.00, 1000, 500, 1),
(32, 'S002', 'RECTIFICADA', '', '', '', 25.00, 28.00, 1000, 200, 1),
(33, 'S003', 'ASISTENCIA TECNICA', '', '', '', 60.00, 120.00, 1000, 200, 1),
(35, 'S004', 'ABC COMPLETO VSTROM', '', '', '', 1.00, 85.00, 999, 200, 1),
(36, 'S005', 'CAMBIO DE ACEITE BAJA CILINDRADA', '', '', '', 1.00, 5.00, 1001, 200, 1),
(37, 'S006', 'TENSION DE CADENA Y LUBRICACION', '', '', '', 1.00, 3.00, 9999, 200, 1),
(39, '7861023602526', 'Aceite Castrol 10W-30', 'Sintético Para motor a Gasolina , Gas - protección instantánea desde el momento en que arrancas', 'Castrol', 'Aceites', 8.00, 9.00, 6, 3, 1),
(40, '3700142009318', 'Aceite Ipone f5 - Suspención/Barras', 'Fork 5 sintético plus 1.05 litros', 'katana', 'Aceites', 11.00, 12.00, 14, 3, 1),
(41, '3700142319196', 'Aceite Ipone f10  - Suspención/Barras', 'Fork 10 Sintético Plus', 'katana', 'Aceites', 13.00, 14.00, 10, 3, 1),
(42, '8003699013087', 'Aceite Eni 20W-50', 'Ride Street y Touring 1 litro sintético', 'Eni', 'Aceites', 5.00, 6.00, 8, 4, 1),
(43, '7750804006632', 'Aceite Rayvon 10W P/Amortiguación Hidráulica', 'Full Sintético 1 litro Para sistemas de amortiguación hidráulica', 'Vistony', 'Aceites', 6.00, 7.00, 12, 5, 1),
(44, '3700142304505', 'Aceite Ipone 15W-50', 'iphone 100% sintético', 'Katana', 'Aceites', 14.00, 15.00, 14, 5, 1),
(45, '7750804000463', 'Liquido de Frenos 120ml -DOT4', 'Liquido de frenos Dot 4', 'Vistony', 'Liq/Frenos', 4.00, 4.50, 8, 4, 1),
(46, '7750804000777', 'Liquido de Frenos 120ml -DOT3', 'Liquido de Frenos 120ml -DOT3', 'Vistony', 'Liq/Frenos', 3.00, 3.50, 5, 2, 1),
(47, '3374650237312', 'Liquido/Frenos 500ml -DOT3&4', 'Liquido/Frenos 500ml -DOT3&4 - 500ml Motul', 'Motul', 'Liq/Frenos', 14.00, 15.00, 1, 1, 1),
(48, '7897707508785', 'Bujia DR8EA Brasileña 6000 Km', 'XL-200/DR-200 / GS-500 y chinas', 'NGK', 'Bujias', 3.00, 3.75, 3, 5, 1),
(49, '3700142304338', 'Aceite Ipone 10W-40', '1 litro aceite Ipone 10W-40', 'katana', 'Aceites', 14.00, 15.00, 25, 10, 1),
(50, '5901234123457', 'Aceite MotorTec 20W-50 1L', 'Aceite MotorTec 20W-50 1L 100% Sintético', 'MotorTec', 'Aceites', 6.00, 7.00, 1, 1, 1),
(51, '7861180313310', 'Aceite Porten 10W-30', 'Aceite Porten 10W-30', 'Porten', 'Aceites', 14.00, 15.00, 2, 1, 1),
(52, '7891414547440', 'Aceite Petronas 10W-40', 'Sintético 4T 1Litro', 'Petronas', 'Aceites', 6.00, 7.00, 1, 1, 1),
(53, '3374650247304', 'Aceite Motul 1L  10W-40', 'Aceite Motul 1L  10W-40', 'Motul', 'Aceites', 14.00, 15.50, 4, 2, 1),
(54, '4008177191497', 'Aceite Castrol 1L  10W-40', 'Aceite Castrol 1L  10W-40', 'Castrol', 'Aceites', 15.00, 16.00, 4, 1, 1),
(55, '3700142300446', 'Aceite Ipone - Liq/Radiador Anti-Corro', 'Anti corrosión liquido para radiadores', 'Ipone', 'Liq/Radiador', 8.00, 9.00, 12, 5, 1),
(56, 'Blue\'4 5098251', 'Refrigerante 4litros 50/50', 'A litros de refrigerante 50/50 - J1034', 'Wollfe', 'Refrigerante', 1.00, 1.00, 2, 1, 1),
(57, '7750804001606', 'Aceite Para Transmisión 80W-90', 'Aceite Transmisión GL-5', 'Vistony', 'Aceites', 8.00, 9.00, 4, 2, 1),
(58, '097012213016', 'Refrigerante AMSOIL 946ml FS', 'Full Sintético', 'Amsoil', 'Refrigerante', 16.00, 18.00, 1, 1, 1),
(59, '7861081405916', 'Aceite Suzuki 10W-40', 'Suzuki 4T 100% sintético', 'Suzuki', 'Aceites', 8.00, 9.50, 14, 8, 1),
(60, '7861081405961', 'Aceite Suzuki 20W-50', 'Aceite Suzuki 20W-50 FULL SINTETICO', 'Suzuki', 'Aceites', 9.00, 9.50, 2, 1, 1),
(61, '99CMX\'21030\'P95', 'Aceite Ecstar 10W-40', 'Ecstar 10W-40  Aceite lubricante para motores de motocicletas', 'Suzuki', 'Aceites', 14.50, 14.50, 1, 1, 1),
(62, '7897707509874', 'Bujia CR8E Brasileña', 'Motos chinas', 'NGK', 'Bujias', 4.00, 5.50, 2, 4, 1),
(63, '7897707505517', 'Bujia C7HSA Brasileña', 'Motos chinas', 'NGK', 'Bujias', 2.50, 2.75, 9, 4, 1),
(65, '079567520115', 'WD-40', 'Elimina residuos', 'WD', 'Spray', 1.00, 1.00, 2, 3, 1),
(66, '7861208623605', 'Sp - Limpiador de frenos', 'limpiador de motos y bicicletas', 'Adheplast', 'Spray', 1.00, 1.00, 1, 1, 1),
(67, '851211003942', 'Sp - Lubricante de cadena', '', 'Maxima', 'Spray-Lubri', 1.00, 1.00, 3, 2, 1),
(68, '7750804002085', 'Sp - Limpia Carburador', 'Limpieza total 356 ml', 'Vistony', 'Spray-L/Carburador', 3.00, 4.00, 9, 4, 1),
(69, '7750804002238', 'Sp - Limpia Contacto', '', 'Vistony', 'Spray', 1.00, 1.00, 5, 2, 1),
(70, 'B01', 'Bujia C7HSA suzuki', 'Made in India', 'Suzuki', 'Bujias', 8.00, 8.50, 4, 3, 1),
(71, '7897707505425', 'Bujia BM6A brasileña', 'Motos chinas', 'NGK', 'Bujias', 2.00, 2.75, 5, 2, 1),
(72, '78977007505371', 'Bujia B7HS brasileña', 'Motos chinas', 'GNK', 'Bujias', 3.00, 3.75, 9, 4, 1),
(73, 'B002', 'Bujia LMAR8BI-9 suzuki', 'Original\r\nVstrom 1000A', 'Suzuki', 'Bujias', 20.00, 26.00, 4, 2, 1),
(74, 'Cb150-8', 'Stator 8x2 huecos/5 cables l723 chino', 'Chino', 'Magneto', 'Stator', 8.75, 9.00, 1, 1, 1),
(75, '240521', 'Filtro de aceite vstrom', '', 'Suzuki', 'Filtros', 14.00, 15.00, 0, 1, 1),
(76, '6910016852', 'Pastillas de freno 69100-16852', '', 'Suzuki', 'Pastillas', 98.00, 98.00, 4, 2, 1),
(77, '5910233870', 'Pastillas de freno 59102-33870', '', 'Suzuki', 'Pastillas', 84.00, 85.00, 1, 1, 1),
(78, '5930233850', 'Pastillas de freno 59302-33850', '', 'Suzuki', 'Pastillas', 79.00, 80.00, 1, 1, 1),
(79, '69100-06830', 'Pastillas de freno 69100-06830', '', 'Suzuki', 'Pastillas', 97.00, 98.00, 1, 1, 1),
(80, '59100-23852', 'Pastillas de freno 59100-23852', '', 'Suzuki', 'Pastillas', 89.00, 90.00, 2, 1, 1),
(81, '69101-44890', 'Pastillas de freno 69101-44890', '', 'Suzuki', 'Pastillas', 1.00, 1.00, 2, 1, 1),
(82, '57500-24F22', 'Manija de embrague 57500-24F22', '', 'Suzuki', 'Manijas', 74.00, 75.00, 1, 1, 1),
(83, '57300-44G12', 'Manija de freno 57300-44G12', '', 'Suzuki', 'Manijas', 74.00, 75.00, 1, 1, 1),
(84, '27000-31813', 'Kit de Arrastre (DL1000A) 27000-31813', '', 'Suzuki', 'Rodamiento', 229.00, 230.00, 1, 1, 1),
(85, '27000-32843', 'Kit de Arrastre (DL650A) 27000-32843', '', 'Suzuki', 'Rodamiento', 229.00, 230.00, 2, 1, 1),
(87, '6204-2RS', 'Rulimanes NTN 6204-2RS', '', 'Ntn', 'Rulimanes', 7.00, 7.50, 8, 2, 1),
(88, '6300-2RS', 'Rulimanes NTN 6300-2RS', '', 'Ntn', 'Rulimanes', 4.00, 5.00, 4, 2, 1),
(89, '6006-2RS', 'Rulimanes NTN 6006-2RS', '', 'Ntn', 'Rulimanes', 9.00, 10.00, 6, 3, 1),
(90, '6005-2RS', 'Rulimanes NTN 6005-2RS', '', 'Ntn', 'Rulimanes', 7.00, 7.50, 5, 2, 1),
(91, '6004-2RS', 'Rulimanes NTN 6004-2RS', '', 'Ntn', 'Rulimanes', 7.00, 7.50, 6, 3, 1),
(92, '6002-2RS', 'Rulimanes NTN 6002-2RS', '', 'Ntn', 'Rulimanes', 4.00, 5.00, 5, 2, 1),
(93, '6201-2RS', 'Rulimanes NTN 6201-2RS', '', 'Ntn', 'Rulimanes', 4.00, 5.00, 9, 2, 1),
(94, 'RU6202', 'Rulimanes MotorTec - RU6202', '', 'MotorTec', 'Rulimanes', 2.00, 2.50, 5, 2, 1),
(95, 'RU6301', 'Rulimanes MotorTec - RU6301', 'Chino', 'MotorTec', 'Rulimanes', 2.00, 2.50, 4, 2, 1),
(96, '6302-2RS', 'Rulimanes NTN 6302-2RS', '', 'Ntn', 'Rulimanes', 7.00, 7.50, 7, 3, 1),
(97, '6203-2RS', 'Rulimanes NTN 6203-2RS', '', 'Ntn', 'Rulimanes', 7.00, 7.50, 9, 2, 1),
(98, '6301-2RS', 'Rulimanes NTN 6301-2RS', '', 'Ntn', 'Rulimanes', 4.00, 5.00, 6, 1, 1),
(99, 'Vl800', 'Pistas de dirección Koyo-VL800', 'Parte superior', 'Koyo', 'Pistas', 31.00, 32.00, 1, 0, 1),
(100, '4549250046827', 'Pistas de dirección Koyo-30x55x6/17', '', 'Koyo', 'Pistas', 42.00, 42.00, 2, 1, 1),
(101, '4549250150722', 'Pistas de dirección Koyo-62/322RSC3', '', 'Koyo', 'Pistas', 37.00, 38.00, 1, 0, 1),
(102, 'RU6204', 'Rulimanes MotorTec -RU6204', '', 'MotorTec', 'Rulimanes', 6.00, 7.00, 2, 1, 1),
(103, '4549250017353', 'Pistas de dirección Koyo-CAP32005JR', '', 'Koyo', 'Pistas', 35.00, 36.00, 2, 0, 1),
(104, '6201-2RSC3', 'Rulimanes CMB 6201-2RSC3', '', 'Cmb', 'Rulimanes', 2.40, 3.00, 2, 0, 1),
(105, '4549250017452', 'Pistas de dirección Koyo-CAP32006JR', '', 'Koyo', 'Pistas', 35.00, 36.00, 2, 0, 1),
(106, '6002-2RS/C3', 'Rulimanes FCS-6002-2RS/C3', '', 'Fcs', 'Rulimanes', 2.50, 3.00, 1, 1, 1),
(107, '6200-2RS/C3', 'Rulimanes FCS 6200-2RS/C3', '', 'Fcs', 'Rulimanes', 4.00, 4.50, 2, 0, 1),
(108, '6201-2RSH/C3', 'Rulimanes SKF 6201-2RSH/C3', '', 'Skf', 'Rulimanes', 3.50, 3.50, 1, 0, 1),
(109, '6003-2RS/C3', 'Rulimanes  FCS 6003-2RS/C3', '', 'Fcs', 'Rulimanes', 2.50, 3.00, 1, 0, 1),
(110, '6004-2RS/C3', 'Rulimanes FCS 6004-2RS/C3', '', 'Fcs', 'Rulimanes', 5.00, 5.00, 1, 1, 1),
(111, '13780-34800-000', 'Filtro de aire  Gixxer 150 vr', '', 'Suzuki', 'Filtros', 9.00, 9.80, 7, 4, 1),
(112, '16510-33G10-000', 'Filtro de aceite Gxx 150 Varios', '', 'Suzuki', 'Filtros', 4.00, 4.50, 8, 4, 1),
(113, 'Ka001', 'Kit de Arrastre Suzuki Gxx 150 (Indu)', '', 'Suzuki', 'Rodamiento', 44.00, 45.00, 1, 1, 1),
(114, 'S2-BajaCc', 'Mano de obra A domicilio(Dentro del sector) -', 'Domicilios', '', 'Servicios', 8.00, 8.00, 100000, 2, 1),
(115, 'S5-AltaCc', 'Mano de obra A domicilio(En la Ciudad) -', 'Alto cilidraje', '', 'Servicios', 24.00, 25.00, 9999, 1, 1),
(116, 'S6-BajaCC', 'Mano de obra A domicilio(En la ciudad) -', 'Motos para baja cilindrada', '', 'Servicios', 11.00, 10.00, 100000, 1, 1),
(117, 'S7-AltaCc', 'Mano de obra A domicilio(Dentro del sector) -', 'Para motos de alta cilindrada a domicilios', '', 'Servicios', 12.00, 13.00, 100000, 1, 1),
(118, '13780-31J00-0000', 'FILTRO DE AIRE VSTROM 1000A', '', 'VSTROM', 'FILTROS', 21.91, 30.00, 1, 1, 1),
(119, '13780-27G10-000', 'FILTRO AIRE VSTROM650', '', 'VSTROM', 'FILTROS', 18.63, 28.00, 1, 1, 1),
(120, '56500-34J41-000', 'ESPEJO RH GIXXER150', '', 'SUZUKI', 'ESPEJOS', 5.52, 9.50, 1, 1, 1),
(121, '56600-34J41-000', 'ESPEJO LH GIXXER150', '', 'SUZUKI', 'ESPEJOS', 5.50, 9.50, 1, 1, 1),
(122, '6922104470951', 'SIRENA DE MOTO', '', 'HORN', 'SIRENAS', 13.00, 17.00, 1, 1, 1),
(124, 'S007', 'CAMBIO DE ACEITE PULSAR NS160', '', 'PULSAR', 'CAMBIO DE ACEITE', 1.00, 6.00, 100000, 1000, 1),
(125, '3700142304505-500ML', 'Aceite Ipone 15W-50 500ML', '', 'KATANA', 'ACEITE MEDIO USO', 1.00, 7.50, 0, 50, 1),
(126, 'F01', 'FILTRO DE ACEITE PULSAR 160NS', '', '', '', 1.00, 3.00, 0, 3, 1),
(127, '6201-2RS/C3', 'Rulimanes FCS 6201-2RS/C3', '', 'FCS', 'RULIMANES', 2.00, 4.00, 1, 1, 1),
(128, 'NK15/12', 'CANASTILLA DE MOTOR NK15/12-34X16MM CMB', '', 'CMB', 'CANASTILLA', 15.00, 20.00, 1, 1, 1),
(129, '6202', 'RULIMAN 6202 FCS', '', 'FCS', 'RULIMANES', 2.50, 3.50, 1, 1, 1),
(130, '6202-2RS', 'RULIMAN 6202-2RS NTN', '', 'NTN', 'RULIMANES', 1.60, 6.00, 4, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','empleado') NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `fecha_registro`, `ultimo_login`, `activo`) VALUES
(5, 'Administrador', 'admin@devdesoftware.com', '$2y$10$xGDGfB2moWCpMORFSu.lUOmu7ECVcFwdd7GfLw8PBbRA/VeekP.Uq', 'administrador', '2025-05-01 01:36:27', '2025-08-03 15:49:21', 1),
(6, 'Patricio', 'patsmotos@tallermotos.com', '$2y$10$BbMpWkdDlfNSDRwdnlZxL.d3yEUCZxLlk.e6wMyEK1qo1H371VvzO', 'administrador', '2025-05-04 15:25:24', '2025-09-05 03:33:17', 1),
(8, 'Anderson', 'andersonpats@tallermotos.com', '$2y$10$yF0NpY.AS/tR49UohoYvGObudQpRTKKm9Oc3FONKpN9tUZwlTD9Se', 'empleado', '2025-07-06 14:55:36', '2025-08-25 03:32:07', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','completada','cancelada') DEFAULT 'completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_id`, `usuario_id`, `fecha`, `total`, `estado`) VALUES
(65, 40, 6, '2025-07-09 19:18:43', 3.00, 'completada'),
(73, NULL, 6, '2025-08-02 15:22:23', 85.00, 'completada'),
(77, NULL, 6, '2025-08-08 20:55:58', 28.50, 'completada'),
(78, NULL, 6, '2025-08-08 20:56:17', 15.00, 'completada'),
(79, NULL, 6, '2025-08-08 20:56:40', 25.00, 'completada'),
(80, NULL, 6, '2025-08-10 22:13:42', 7.50, 'completada'),
(81, NULL, 6, '2025-08-10 22:14:06', 15.00, 'completada'),
(82, NULL, 6, '2025-08-10 22:17:30', 3.00, 'completada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_detalles`
--

CREATE TABLE `venta_detalles` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `venta_detalles`
--

INSERT INTO `venta_detalles` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(86, 65, 37, 1, 3.00, 3.00),
(94, 73, 35, 1, 85.00, 85.00),
(98, 77, 59, 3, 9.50, 28.50),
(99, 78, 75, 1, 15.00, 15.00),
(100, 79, 115, 1, 25.00, 25.00),
(101, 80, 125, 1, 7.50, 7.50),
(102, 81, 44, 1, 15.00, 15.00),
(103, 82, 126, 1, 3.00, 3.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cambios_aceite`
--
ALTER TABLE `cambios_aceite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `moto_id` (`moto_id`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `moto_id` (`moto_id`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Indices de la tabla `mantenimiento_historial`
--
ALTER TABLE `mantenimiento_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_id` (`mantenimiento_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `mantenimiento_repuestos`
--
ALTER TABLE `mantenimiento_repuestos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_id` (`mantenimiento_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_eliminacion` (`usuario_eliminacion`);

--
-- Indices de la tabla `mantenimiento_ventas`
--
ALTER TABLE `mantenimiento_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_id` (`mantenimiento_id`),
  ADD KEY `venta_id` (`venta_id`);

--
-- Indices de la tabla `motos`
--
ALTER TABLE `motos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `venta_detalles`
--
ALTER TABLE `venta_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cambios_aceite`
--
ALTER TABLE `cambios_aceite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT de la tabla `mantenimiento_historial`
--
ALTER TABLE `mantenimiento_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `mantenimiento_repuestos`
--
ALTER TABLE `mantenimiento_repuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mantenimiento_ventas`
--
ALTER TABLE `mantenimiento_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `motos`
--
ALTER TABLE `motos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de la tabla `venta_detalles`
--
ALTER TABLE `venta_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cambios_aceite`
--
ALTER TABLE `cambios_aceite`
  ADD CONSTRAINT `cambios_aceite_ibfk_1` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cambios_aceite_ibfk_2` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mantenimientos_ibfk_2` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mantenimiento_historial`
--
ALTER TABLE `mantenimiento_historial`
  ADD CONSTRAINT `mantenimiento_historial_ibfk_1` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mantenimientos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mantenimiento_historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mantenimiento_repuestos`
--
ALTER TABLE `mantenimiento_repuestos`
  ADD CONSTRAINT `mantenimiento_repuestos_ibfk_1` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mantenimientos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mantenimiento_repuestos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mantenimiento_repuestos_ibfk_3` FOREIGN KEY (`usuario_eliminacion`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mantenimiento_ventas`
--
ALTER TABLE `mantenimiento_ventas`
  ADD CONSTRAINT `mantenimiento_ventas_ibfk_1` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mantenimientos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mantenimiento_ventas_ibfk_2` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `motos`
--
ALTER TABLE `motos`
  ADD CONSTRAINT `motos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `venta_detalles`
--
ALTER TABLE `venta_detalles`
  ADD CONSTRAINT `venta_detalles_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `venta_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
