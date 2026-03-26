-- Script para agregar campo 'servicio_propuesto' a la tabla conversaciones
-- Ejecutar este script en MySQL antes de usar la nueva funcionalidad

USE whatsapp_agenda;

-- Agregar columna servicio_propuesto
-- Si ya existe, simplemente ignorará el error
ALTER TABLE conversaciones 
ADD COLUMN servicio_propuesto VARCHAR(100) DEFAULT NULL;

-- Verificar la estructura de la tabla
DESCRIBE conversaciones;
