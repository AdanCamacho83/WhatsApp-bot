-- ============================================
-- CONFIGURACIÓN TEMPORAL: idEmpresa por defecto = 1
-- Mientras se implementa la solución completa
-- ============================================

-- 1. Cambiar el valor por defecto de idEmpresa a 1 en la tabla citas
ALTER TABLE citas MODIFY COLUMN idEmpresa INT UNSIGNED NOT NULL DEFAULT 1;

-- 2. Actualizar todas las citas existentes con idEmpresa = 0 para que sean 1
UPDATE citas SET idEmpresa = 1 WHERE idEmpresa = 0;

-- 3. Verificar que se aplicó correctamente
SELECT idEmpresa, COUNT(*) as total 
FROM citas 
GROUP BY idEmpresa;

-- 4. Ver estructura actualizada
DESCRIBE citas;
