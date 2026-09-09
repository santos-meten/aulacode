-- =============================================================================
-- AulaCode - Plataforma de Aprendizaje, Práctica y Evaluación
-- database.sql  |  Fase 1 (esquema completo preparado para fases posteriores)
-- Motor: InnoDB  |  Charset: utf8mb4
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `aulacode`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `aulacode`;

-- -----------------------------------------------------------------------------
-- grupos
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sesiones_activas`;
DROP TABLE IF EXISTS `resultados`;
DROP TABLE IF EXISTS `respuestas`;
DROP TABLE IF EXISTS `intentos`;
DROP TABLE IF EXISTS `asignaciones`;
DROP TABLE IF EXISTS `opciones`;
DROP TABLE IF EXISTS `preguntas`;
DROP TABLE IF EXISTS `ejercicios`;
DROP TABLE IF EXISTS `materiales`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `grupos`;

CREATE TABLE `grupos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) NULL DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_grupos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- usuarios
-- -----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(150) NOT NULL,
  `usuario` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('ADMIN','ALUMNO') NOT NULL DEFAULT 'ALUMNO',
  `grupo_id` INT UNSIGNED NULL DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `ultimo_acceso` DATETIME NULL DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  KEY `idx_usuarios_rol` (`rol`),
  KEY `idx_usuarios_grupo` (`grupo_id`),
  CONSTRAINT `fk_usuarios_grupo`
    FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ejercicios
-- -----------------------------------------------------------------------------
CREATE TABLE `ejercicios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT NULL,
  `tipo` ENUM('CUESTIONARIO','SQL','PHP','RESPUESTA_ESCRITA','PROYECTO') NOT NULL DEFAULT 'CUESTIONARIO',
  `duracion_minutos` INT UNSIGNED NOT NULL DEFAULT 30,
  `fecha_inicio` DATETIME NULL DEFAULT NULL,
  `fecha_fin` DATETIME NULL DEFAULT NULL,
  `puntuacion_maxima` DECIMAL(6,2) NOT NULL DEFAULT 10.00,
  `estado` ENUM('BORRADOR','PUBLICADO','CERRADO') NOT NULL DEFAULT 'BORRADOR',
  `creado_por` INT UNSIGNED NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ejercicios_estado` (`estado`),
  KEY `idx_ejercicios_tipo` (`tipo`),
  KEY `idx_ejercicios_creado_por` (`creado_por`),
  CONSTRAINT `fk_ejercicios_creado_por`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- preguntas
-- -----------------------------------------------------------------------------
CREATE TABLE `preguntas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ejercicio_id` INT UNSIGNED NOT NULL,
  `enunciado` TEXT NOT NULL,
  `tipo` ENUM('OPCION_MULTIPLE','VERDADERO_FALSO','RESPUESTA_CORTA','SQL','PHP','TEXTO_LARGO') NOT NULL,
  `puntos` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `respuesta_esperada` TEXT NULL COMMENT 'Para respuesta corta / SQL esperado',
  `orden` INT UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_preguntas_ejercicio` (`ejercicio_id`),
  CONSTRAINT `fk_preguntas_ejercicio`
    FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- opciones
-- -----------------------------------------------------------------------------
CREATE TABLE `opciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pregunta_id` INT UNSIGNED NOT NULL,
  `texto` VARCHAR(500) NOT NULL,
  `es_correcta` TINYINT(1) NOT NULL DEFAULT 0,
  `orden` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_opciones_pregunta` (`pregunta_id`),
  CONSTRAINT `fk_opciones_pregunta`
    FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- asignaciones
-- -----------------------------------------------------------------------------
CREATE TABLE `asignaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ejercicio_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
  `grupo_id` INT UNSIGNED NULL DEFAULT NULL,
  `asignado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asignaciones_ejercicio` (`ejercicio_id`),
  KEY `idx_asignaciones_usuario` (`usuario_id`),
  KEY `idx_asignaciones_grupo` (`grupo_id`),
  CONSTRAINT `fk_asignaciones_ejercicio`
    FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_grupo`
    FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- intentos
-- -----------------------------------------------------------------------------
CREATE TABLE `intentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ejercicio_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `started_at` DATETIME NOT NULL,
  `deadline_at` DATETIME NOT NULL,
  `finished_at` DATETIME NULL DEFAULT NULL,
  `estado` ENUM('PENDIENTE','EN_CURSO','FINALIZADO','TIEMPO_AGOTADO','PENDIENTE_CORRECCION') NOT NULL DEFAULT 'EN_CURSO',
  `puntuacion` DECIMAL(6,2) NULL DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_intentos_ejercicio` (`ejercicio_id`),
  KEY `idx_intentos_usuario` (`usuario_id`),
  KEY `idx_intentos_estado` (`estado`),
  CONSTRAINT `fk_intentos_ejercicio`
    FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_intentos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- respuestas
-- -----------------------------------------------------------------------------
CREATE TABLE `respuestas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intento_id` INT UNSIGNED NOT NULL,
  `pregunta_id` INT UNSIGNED NOT NULL,
  `respuesta_texto` TEXT NULL,
  `opcion_id` INT UNSIGNED NULL DEFAULT NULL,
  `es_correcta` TINYINT(1) NULL DEFAULT NULL,
  `puntos_obtenidos` DECIMAL(6,2) NULL DEFAULT NULL,
  `guardado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_respuesta_intento_pregunta` (`intento_id`, `pregunta_id`),
  KEY `idx_respuestas_pregunta` (`pregunta_id`),
  KEY `idx_respuestas_opcion` (`opcion_id`),
  CONSTRAINT `fk_respuestas_intento`
    FOREIGN KEY (`intento_id`) REFERENCES `intentos` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_respuestas_pregunta`
    FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_respuestas_opcion`
    FOREIGN KEY (`opcion_id`) REFERENCES `opciones` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- resultados
-- -----------------------------------------------------------------------------
CREATE TABLE `resultados` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intento_id` INT UNSIGNED NOT NULL,
  `puntuacion_obtenida` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `puntuacion_maxima` DECIMAL(6,2) NOT NULL DEFAULT 10.00,
  `porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `corregido_por` INT UNSIGNED NULL DEFAULT NULL,
  `comentario` TEXT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resultados_intento` (`intento_id`),
  KEY `idx_resultados_corregido_por` (`corregido_por`),
  CONSTRAINT `fk_resultados_intento`
    FOREIGN KEY (`intento_id`) REFERENCES `intentos` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_resultados_corregido_por`
    FOREIGN KEY (`corregido_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- materiales
-- -----------------------------------------------------------------------------
CREATE TABLE `materiales` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(200) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL DEFAULT 'General',
  `contenido` MEDIUMTEXT NOT NULL,
  `orden` INT UNSIGNED NOT NULL DEFAULT 1,
  `publicado` TINYINT(1) NOT NULL DEFAULT 0,
  `creado_por` INT UNSIGNED NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_materiales_categoria` (`categoria`),
  KEY `idx_materiales_publicado` (`publicado`),
  KEY `idx_materiales_creado_por` (`creado_por`),
  CONSTRAINT `fk_materiales_creado_por`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- sesiones_activas (presencia para dashboard en tiempo real)
-- -----------------------------------------------------------------------------
CREATE TABLE `sesiones_activas` (
  `usuario_id` INT UNSIGNED NOT NULL,
  `ultimo_ping` DATETIME NOT NULL,
  `intento_id` INT UNSIGNED NULL DEFAULT NULL,
  `ip` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  KEY `idx_sesiones_ping` (`ultimo_ping`),
  KEY `idx_sesiones_intento` (`intento_id`),
  CONSTRAINT `fk_sesiones_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_sesiones_intento`
    FOREIGN KEY (`intento_id`) REFERENCES `intentos` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DATOS INICIALES DE PRUEBA
-- Contraseñas (password_hash):
--   admin    -> admin123
--   alumno1  -> alumno123
-- =============================================================================

INSERT INTO `grupos` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, '1º Informática', 'Grupo de prueba para el aula', 1);

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `usuario`, `password`, `rol`, `grupo_id`, `estado`) VALUES
(1, 'Administrador', 'AulaCode', 'admin',
 '$2y$10$P5SEk0Wi.2bimqDPaxjFk.qfYC8n16ZMNcehzkSo7tDH4Lmt6RMf.',
 'ADMIN', NULL, 1),
(2, 'Juan', 'Pérez', 'alumno1',
 '$2y$10$x/AEMPsezBPdvyl8ys7LN.1oDp5Bafk9I8kw7SLOfAJuOje0i2cvO',
 'ALUMNO', 1, 1);

-- Ejercicio de demostración
INSERT INTO `ejercicios`
(`id`, `titulo`, `descripcion`, `tipo`, `duracion_minutos`, `puntuacion_maxima`, `estado`, `creado_por`)
VALUES
(1, 'Cuestionario SQL básico', 'Preguntas introductorias sobre SQL y bases de datos.', 'CUESTIONARIO', 20, 10.00, 'PUBLICADO', 1);

INSERT INTO `asignaciones` (`ejercicio_id`, `grupo_id`) VALUES (1, 1);

INSERT INTO `preguntas` (`id`, `ejercicio_id`, `enunciado`, `tipo`, `puntos`, `respuesta_esperada`, `orden`) VALUES
(1, 1, '¿Qué lenguaje utilizamos para realizar consultas en MySQL?', 'OPCION_MULTIPLE', 2.00, NULL, 1),
(2, 1, 'Una clave primaria identifica de forma única cada registro de una tabla.', 'VERDADERO_FALSO', 2.00, NULL, 2),
(3, 1, 'Palabra clave SQL para seleccionar columnas (en mayúsculas).', 'RESPUESTA_CORTA', 2.00, 'SELECT', 3),
(4, 1, '¿Cuál cláusula filtra filas en una consulta SQL?', 'OPCION_MULTIPLE', 2.00, NULL, 4),
(5, 1, 'PHP puede conectarse a MySQL para crear aplicaciones web.', 'VERDADERO_FALSO', 2.00, NULL, 5);

INSERT INTO `opciones` (`pregunta_id`, `texto`, `es_correcta`, `orden`) VALUES
(1, 'HTML', 0, 1),
(1, 'CSS', 0, 2),
(1, 'SQL', 1, 3),
(1, 'JavaScript', 0, 4),
(2, 'Verdadero', 1, 1),
(2, 'Falso', 0, 2),
(4, 'WHERE', 1, 1),
(4, 'ORDER BY', 0, 2),
(4, 'GROUP BY', 0, 3),
(4, 'JOIN', 0, 4),
(5, 'Verdadero', 1, 1),
(5, 'Falso', 0, 2);

INSERT INTO `materiales` (`titulo`, `categoria`, `contenido`, `orden`, `publicado`, `creado_por`) VALUES
('¿Qué es una base de datos?', 'Bases de datos',
 'Una base de datos es un conjunto organizado de datos que se almacenan y se consultan de forma eficiente.\n\nEn el aula trabajaremos principalmente con MySQL/MariaDB.', 1, 1, 1),
('Tablas, registros y campos', 'Bases de datos',
 'Una tabla organiza la información en filas (registros) y columnas (campos).\n\nEjemplo: tabla alumnos con campos id, nombre, apellidos.', 2, 1, 1),
('Claves primarias y foráneas', 'Bases de datos',
 'La clave primaria identifica de forma única cada fila.\nLa clave foránea relaciona una tabla con otra.', 3, 1, 1),
('SELECT', 'SQL',
 'SELECT permite consultar datos.\n\nEjemplo:\nSELECT nombre, apellidos FROM alumnos;', 1, 1, 1),
('WHERE, ORDER BY y JOIN', 'SQL',
 'WHERE filtra filas.\nORDER BY ordena resultados.\nJOIN combina tablas relacionadas.', 2, 1, 1),
('Variables y condicionales', 'PHP',
 'En PHP las variables empiezan por $.\nLos condicionales if/else permiten tomar decisiones en el código.', 1, 1, 1),
('Formularios y sesiones', 'PHP',
 'Los formularios HTML envían datos a PHP con GET o POST.\nLas sesiones permiten recordar al usuario entre páginas.', 2, 1, 1);
