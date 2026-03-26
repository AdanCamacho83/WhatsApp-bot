-- ============================================
-- Agregar campo para activar/desactivar Google Calendar por empresa
-- ============================================

-- Agregar campo google_calendar_activo (0 = desactivado, 1 = activado)
ALTER TABLE empresas 
ADD COLUMN google_calendar_activo TINYINT(1) NOT NULL DEFAULT 0 
AFTER telefono_twilio;

-- Verificar que se agregó correctamente
DESCRIBE empresas;

-- Consultar empresas con el nuevo campo
SELECT id, nombre_empresa, codigo_empresa, google_calendar_activo 
FROM empresas;
