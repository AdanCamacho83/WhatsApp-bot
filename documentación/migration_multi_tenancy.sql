-- ============================================
-- SCRIPT DE MIGRACIÓN: Multi-Tenancy
-- Agregar soporte para múltiples empresas
-- ============================================

-- 1. Agregar el campo idEmpresa a la tabla citas
-- (Solo ejecutar si el campo no existe)
ALTER TABLE citas ADD COLUMN idEmpresa INT NOT NULL DEFAULT 0 AFTER id;

-- 2. Actualizar citas existentes para asignarlas a la primera empresa
-- IMPORTANTE: Reemplaza el "1" con el ID correcto de la empresa
-- Si tienes la empresa "testuser3" con ID=1, usa:
UPDATE citas SET idEmpresa = 1 WHERE idEmpresa = 0;

-- 3. Agregar índices para mejorar el rendimiento
CREATE INDEX idx_idEmpresa ON citas(idEmpresa);
CREATE INDEX idx_idEmpresa_estado ON citas(idEmpresa, estado);
CREATE INDEX idx_idEmpresa_fecha ON citas(idEmpresa, fecha_inicio);

-- 4. Verificar que todo se aplicó correctamente
SELECT 
    COUNT(*) as total_citas,
    idEmpresa,
    estado
FROM citas 
GROUP BY idEmpresa, estado;

-- 5. Ver estructura actualizada de la tabla
DESCRIBE citas;

-- ============================================
-- CONSULTAS ÚTILES PARA TESTING
-- ============================================

-- Ver todas las empresas registradas
SELECT id, nombre_empresa, usuario FROM empresas;

-- Ver todas las citas con su empresa asociada
SELECT 
    c.id,
    c.idEmpresa,
    e.nombre_empresa,
    c.telefono_usuario,
    c.fecha_inicio,
    c.servicio,
    c.estado
FROM citas c
LEFT JOIN empresas e ON c.idEmpresa = e.id
ORDER BY c.fecha_inicio DESC;

-- Contar citas por empresa
SELECT 
    e.nombre_empresa,
    COUNT(c.id) as total_citas,
    SUM(CASE WHEN c.estado = 'activa' THEN 1 ELSE 0 END) as activas,
    SUM(CASE WHEN c.estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
FROM empresas e
LEFT JOIN citas c ON e.id = c.idEmpresa
GROUP BY e.id, e.nombre_empresa;
