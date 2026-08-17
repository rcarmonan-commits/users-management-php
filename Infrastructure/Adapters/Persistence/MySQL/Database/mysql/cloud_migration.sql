-- ============================================================================
-- MIGRACIÓN CLOUD SEGURA E IDEMPOTENTE (Alwaysdata MariaDB 11.4 / MySQL Cloud)
-- ----------------------------------------------------------------------------
-- REGLA ESTRICTA DE INTEGRIDAD Y NO DESTRUCCIÓN DE DATOS:
--   * CERO sentencias destructivas (`DROP`, `TRUNCATE` o `DELETE` masivo).
--   * Verifica si existen las estructuras (`CREATE TABLE IF NOT EXISTS`) y
--     sincroniza semillas mediante `ON DUPLICATE KEY UPDATE` sin sobrescribir
--     tablas ni registros previos (`usuarios`, `gastos` con `id_gasto`,
--     `configuracion_smtp`).
-- ============================================================================

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
    CONSTRAINT fk_cloud_user_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla `gastos` ya existe en el motor cloud (ej. esquema compartido con `id_gasto`),
-- `CREATE TABLE IF NOT EXISTS` la preserva intacta sin alterar columnas ni llaves foráneas.
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
    INDEX idx_cloud_gastos_fecha (fecha),
    INDEX idx_cloud_gastos_usuario (nombre_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, name) VALUES
    (1, 'ADMIN'),
    (2, 'MEMBER'),
    (3, 'REVIEWER')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT INTO users (id, name, email, password, role, status, role_id)
VALUES (
    '00000000-0000-4000-8000-000000000001',
    'Administrador',
    'admin@crud.com',
    '$2y$10$qSJkkchCSWMcNWyyEvqQNeSOtTHxexw5qo.XYcgVVk9QT33B4A5ie',
    'ADMIN',
    'ACTIVE',
    1
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role),
    status = VALUES(status),
    role_id = VALUES(role_id);