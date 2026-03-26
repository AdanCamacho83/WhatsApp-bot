-- ===================================================================
-- REDISEÑO TABLA EMPRESAS CON codigo_empresa y telefono_twilio
-- ===================================================================

-- 1. Eliminar tabla empresas anterior (ADVERTENCIA: perderás datos)
-- Si tienes datos importantes, primero haz un backup:
-- mysqldump -u root -P 3308 whatsapp_agenda empresas > backup_empresas.sql

DROP TABLE IF EXISTS empresas;

-- 2. Crear nueva estructura de tabla empresas
CREATE TABLE empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(100) NOT NULL,
    codigo_empresa VARCHAR(50) NOT NULL,
    telefono_contacto VARCHAR(20) NOT NULL DEFAULT '',
    telefono_twilio VARCHAR(40) NOT NULL DEFAULT '',
    direccion VARCHAR(255) NOT NULL DEFAULT '',
    usuario VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_codigo_empresa (codigo_empresa),
    UNIQUE KEY uk_usuario (usuario),
    INDEX idx_nombre_empresa (nombre_empresa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insertar empresa de ejemplo
-- Contraseña: admin123 (bcrypt cost 12)
INSERT INTO empresas 
    (nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario, password)
VALUES
    ('Barbería El Corte Fino', 
     'BARBER123', 
     '+52-555-1234567', 
     '+14155238886',
     'Av. Revolución 123, CDMX', 
     'admin', 
     '$2y$12$LQv3c1yYiHKqT.PnpYZm0eqhY4pXNb5qO.YYT4cMdJHxMBZxYQ6Ri');

-- 4. Verificar la creación
SELECT id, nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, usuario 
FROM empresas;
