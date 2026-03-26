-- ============================================
-- TABLA: clientes
-- Relaciona teléfonos de clientes con empresas
-- ============================================

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idEmpresa INT UNSIGNED NOT NULL,
    telefono_usuario VARCHAR(40) NOT NULL,
    nombre_cliente VARCHAR(100) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    UNIQUE KEY unique_telefono_empresa (telefono_usuario, idEmpresa),
    INDEX idx_telefono (telefono_usuario),
    INDEX idx_idEmpresa (idEmpresa),
    
    -- Relación con empresas
    FOREIGN KEY (idEmpresa) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CONSULTAS ÚTILES
-- ============================================

-- Ver clientes por empresa
SELECT 
    c.id,
    e.nombre_empresa,
    c.telefono_usuario,
    c.nombre_cliente,
    c.activo
FROM clientes c
INNER JOIN empresas e ON c.idEmpresa = e.id
ORDER BY e.nombre_empresa, c.telefono_usuario;

-- Buscar a qué empresa pertenece un teléfono
SELECT 
    c.idEmpresa,
    e.nombre_empresa,
    c.telefono_usuario,
    c.nombre_cliente
FROM clientes c
INNER JOIN empresas e ON c.idEmpresa = e.id
WHERE c.telefono_usuario = 'whatsapp:+521234567890';

-- Contar clientes por empresa
SELECT 
    e.nombre_empresa,
    COUNT(c.id) as total_clientes,
    SUM(CASE WHEN c.activo = 1 THEN 1 ELSE 0 END) as activos
FROM empresas e
LEFT JOIN clientes c ON e.id = c.idEmpresa
GROUP BY e.id, e.nombre_empresa;
