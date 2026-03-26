-- ============================================
-- Eliminar campo google_calendar_activo de la tabla empresas
-- Ya que ahora se controla desde config.php
-- ============================================

-- Eliminar columna google_calendar_activo
ALTER TABLE empresas 
DROP COLUMN google_calendar_activo;

-- Verificar que se eliminó correctamente
DESCRIBE empresas;
