-- ============================================
-- AGREGAR CÓDIGO ÚNICO A EMPRESAS
-- Para identificar empresa en mensajes de WhatsApp
-- ============================================

-- 1. Agregar campo codigo a tabla empresas
ALTER TABLE empresas 
ADD COLUMN codigo VARCHAR(20) DEFAULT NULL AFTER id,
ADD UNIQUE INDEX idx_codigo (codigo);

-- 2. Generar códigos para empresas existentes
-- Puedes modificar estos códigos como prefieras
UPDATE empresas SET codigo = 'BARBER123' WHERE id = 1;

-- O generar códigos automáticos basados en el nombre:
-- UPDATE empresas 
-- SET codigo = CONCAT(
--     UPPER(SUBSTRING(REPLACE(nombre_empresa, ' ', ''), 1, 6)),
--     LPAD(id, 3, '0')
-- )
-- WHERE codigo IS NULL;

-- 3. Verificar códigos generados
SELECT id, nombre_empresa, codigo, usuario FROM empresas;

-- 4. Ejemplo de cómo se vería:
-- +----+------------------+------------+-----------+
-- | id | nombre_empresa   | codigo     | usuario   |
-- +----+------------------+------------+-----------+
-- |  1 | Barbería El Rey  | BARBER123  | testuser3 |
-- |  2 | Salón Elegante   | SALON456   | salon1    |
-- |  3 | Spa Relax        | SPARELA789 | spa1      |
-- +----+------------------+------------+-----------+
