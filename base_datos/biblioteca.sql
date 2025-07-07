-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 22, 2025 at 02:24 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `biblioteca`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `get_mas_prestados`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_mas_prestados` (IN `num_results` INT)   BEGIN
    -- Selecciona el título, autor, imagen y cuenta total de préstamos
    -- Cuenta todos los registros en 'prestamos' para determinar la frecuencia histórica
    SELECT
        l.id,
        l.editorial,
        l.anio,
        l.descripcion,
        l.titulo,
        l.autor,
        l.imagen, -- Usamos el nombre de columna de tu tabla 'libros'
        COUNT(p.id_libro) AS total_prestamos
    FROM
        prestamos p
    JOIN
        libros l ON p.id_libro = l.id -- Une con la tabla de libros usando el ID del libro
    GROUP BY
        p.id_libro, l.titulo, l.autor, l.imagen -- Agrupa por libro para contar préstamos por cada uno
    ORDER BY
        total_prestamos DESC -- Ordena de mayor a menor número de préstamos
    LIMIT num_results; -- Limita los resultados al número especificado

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `libros`
--

DROP TABLE IF EXISTS `libros`;
CREATE TABLE IF NOT EXISTS `libros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `autor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `anio` date NOT NULL,
  `editorial` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `imagen` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_usuario` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `anio`, `editorial`, `descripcion`, `imagen`, `estado`, `id_usuario`) VALUES
(5, 'El Principito', 'Antoine de Saint-Exupéry', '1943-04-13', 'Everand', 'En este libro, un aviador se encuentra perdido en el desierto del Sahara, después de haber tenido una avería en su avión. Entonces aparece un pequeño príncipe. En sus conversaciones con él, el narrador revela su propia visión sobre la estupidez humana y la sencilla sabiduría de los niños que la mayoría de las personas pierden cuando crecen y se hacen adultos.', './imagenes/682d68643b74b2.07865117.png', 'disponible', 1),
(6, 'El mundo de Sofia', 'Jostein Gaarder', '1991-06-27', 'Siruela', 'El mundo de Sofía no es sólo una novela de misterio, también es la primera novela hasta el momento que presenta una completa –y entretenida– historia de la filosofía desde sus inicios hasta nuestros días.', 'imagenes/mundo_sofia.png', 'disponible', 4),
(7, 'El Bosque Negro', 'Steve Hillard', '2012-05-23', 'Timun Mas', 'El Bosque Negro podría considerarse tanto una novela épica como un ensayo de crítica literaria, cuya trama explora las difusas fronteras entre los mundos. ', './imagenes/682d68ed817c70.65304054.jpg', 'prestado', 4),
(8, 'Prohibido Morir Aqui', 'Elizabeth Taylor', '1971-03-06', 'Libros de la Catarata', 'Esta encantadora historia sobre las excentricidades y sinsabores de la tercera edad es una inteligente indagación sobre la soledad y las posibilidades de la amistad. Sus divertidos personajes, la precisión de las observaciones sobre la vida cotidiana y un fino sentido de la ironía y de la compasión hacen de este libro una narración inolvidable.', './imagenes/682d69c14b8db7.44654024.jpg', 'disponible', 4);

--
-- Triggers `libros`
--
DROP TRIGGER IF EXISTS `establecer_estado_disponible`;
DELIMITER $$
CREATE TRIGGER `establecer_estado_disponible` BEFORE INSERT ON `libros` FOR EACH ROW BEGIN
    SET NEW.estado = 'disponible';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `prestamos`
--

DROP TABLE IF EXISTS `prestamos`;
CREATE TABLE IF NOT EXISTS `prestamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_libro` int NOT NULL,
  `id_usuario` int NOT NULL,
  `inicio_prestamo` date NOT NULL,
  `fin_prestamo` date NOT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prestamos`
--

INSERT INTO `prestamos` (`id`, `id_libro`, `id_usuario`, `inicio_prestamo`, `fin_prestamo`, `estado`) VALUES
(5, 5, 4, '2025-04-14', '2025-04-14', 'devuelto'),
(6, 6, 4, '2025-04-14', '2025-04-25', 'devuelto'),
(7, 7, 4, '2025-04-25', '2025-04-26', 'devuelto'),
(8, 5, 5, '2025-04-25', '2025-04-25', 'devuelto'),
(9, 5, 5, '2025-04-25', '2025-04-25', 'devuelto'),
(10, 8, 5, '2025-04-26', '2025-04-26', 'devuelto'),
(11, 8, 5, '2025-04-26', '2025-04-26', 'devuelto'),
(12, 9, 1, '2025-04-26', '2025-04-26', 'devuelto'),
(13, 8, 4, '2025-04-30', '2025-05-05', 'devuelto'),
(14, 5, 4, '2025-05-05', '2025-05-05', 'devuelto'),
(15, 8, 1, '2025-05-05', '2025-05-07', 'devuelto'),
(16, 7, 5, '2025-05-05', '2025-05-07', 'devuelto'),
(17, 8, 5, '2025-05-05', '2025-05-05', 'devuelto'),
(18, 7, 5, '2025-05-05', '2025-05-05', 'devuelto'),
(19, 5, 4, '2025-05-05', '2025-05-05', 'devuelto'),
(20, 5, 5, '2025-05-05', '2025-05-05', 'devuelto'),
(21, 8, 4, '2025-05-05', '2025-05-08', 'devuelto'),
(22, 5, 4, '2025-05-05', '2025-05-06', 'devuelto'),
(23, 5, 4, '2025-05-06', '2025-05-06', 'devuelto'),
(24, 7, 4, '2025-05-06', '2025-05-07', 'devuelto'),
(25, 7, 4, '2025-05-06', '2025-05-06', 'devuelto'),
(26, 5, 4, '2025-05-21', '2025-05-21', 'devuelto'),
(27, 5, 5, '2025-05-21', '2025-05-26', 'devuelto'),
(28, 5, 1, '2025-05-21', '2025-05-21', 'devuelto'),
(51, 5, 1, '2025-05-22', '2025-05-22', 'devuelto'),
(52, 5, 1, '2025-05-22', '2025-05-22', 'devuelto'),
(53, 5, 1, '2025-05-22', '2025-05-22', 'devuelto'),
(54, 7, 1, '2025-05-22', '2025-05-24', 'prestado');

--
-- Triggers `prestamos`
--
DROP TRIGGER IF EXISTS `asignar_estado_prestado`;
DELIMITER $$
CREATE TRIGGER `asignar_estado_prestado` BEFORE INSERT ON `prestamos` FOR EACH ROW BEGIN
  SET NEW.estado="prestado";
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contrasena` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `user`, `contrasena`, `nombre`, `correo`, `tipo`) VALUES
(1, 'daniel', '1234', 'daniel cisneros rojas', 'daniel@gmail.com', 'admin'),
(4, 'Eduardo', '1234', 'Eduardo Huerta Mora', 'mora@gmail.com', 'user'),
(5, 'asdef', '1234', 'Jorge', 'asd@gmail.com', 'user');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
