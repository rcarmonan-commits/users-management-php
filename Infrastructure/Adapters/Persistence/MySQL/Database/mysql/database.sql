-- ============================================================================
-- CRUD Usuarios + Ejercicio #6 (Gasto) — Arquitectura Hexagonal, DDD y CQRS
-- Estudiante: Rosary Eilyn Carmona Navarro (rcarmonan@unicartagena.edu.co)
-- Universidad de Cartagena — Ingeniería de Sistemas
-- Correcciones aplicadas respecto al script base de la guía:
--   1. Columna id VARCHAR(64) compatible con UUIDv4 generado en Entrypoint Web/REST.
--   2. Columna status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' requerida por UserRepositoryMySQL.
--   3. Comillas simples ASCII estándar en DEFAULT 'MEMBER' y columna role_id explícita con FK a roles(id).
--   4. Usuario administrador inicial con contraseña hasheada con BCrypt ('Admin12345', >= 8 caracteres).
--   5. Tabla gastos con los 7 atributos de negocio del Ejercicio #6 + auditoría e índices.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS crud_usuarios
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE crud_usuarios;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'MEMBER',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    role_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gastos (
    id VARCHAR(64) PRIMARY KEY,
    fecha DATE NOT NULL,
    valor_total_sin_iva DECIMAL(14, 2) NOT NULL,
    iva_total DECIMAL(14, 2) NOT NULL,
    valor_total_con_iva DECIMAL(14, 2) NOT NULL,
    nombre_usuario VARCHAR(100) NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gastos_fecha (fecha),
    INDEX idx_gastos_nombre_usuario (nombre_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (id, name) VALUES
    (1, 'ADMIN'),
    (2, 'MEMBER'),
    (3, 'REVIEWER');

-- Usuario Administrador Base (Clave en texto plano para pruebas: Admin12345)
INSERT INTO users (id, name, email, password, role, status, role_id)
VALUES (
    '00000000-0000-4000-8000-000000000001',
    'Administrador',
    'admin@crud.com',
    '$2y$10$UM06b9jm4cmz2hgQK8sEae9l6hUU7iK8EjCKfxQq46g/4CpUGCtWi',
    'ADMIN',
    'ACTIVE',
    1
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role),
    status = VALUES(status),
    role_id = VALUES(role_id);

-- Registros semilla de ejemplo para la entidad #6 (Gasto)
INSERT INTO gastos (
    id, fecha, valor_total_sin_iva, iva_total, valor_total_con_iva, nombre_usuario, lugar, descripcion
) VALUES
(
    '11111111-1111-4111-8111-111111111101',
    '2026-08-20',
    100000.00,
    19000.00,
    119000.00,
    'Administrador',
    'Papelería Panamericana Cartagena',
    'Compra de resmas de papel e insumos de oficina para el departamento de sistemas'
),
(
    '11111111-1111-4111-8111-111111111102',
    '2026-09-05',
    250000.00,
    47500.00,
    297500.00,
    'Administrador',
    'Centro de Convenciones Cartagena',
    'Servicio de conectividad y soporte logístico para sustentación académica'
)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion);