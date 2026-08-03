-- =====================================================================
--  MIGRACION  -  usuario_modulo: columna permiso (lectura/editor)
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-03
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Agrega columna `permiso` a la tabla `usuario_modulo` para indicar
--    si el usuario tiene acceso de 'lectura' (solo ver) o 'editor'
--    (crear/editar/eliminar) en cada modulo asignado.
--    Default: 'lectura' (minimo privilegio por defecto).
--
--  Re-ejecutable: usa IF NOT EXISTS / guards.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Agregar columna permiso si no existe
-- ---------------------------------------------------------------------
ALTER TABLE usuario_modulo
    ADD COLUMN IF NOT EXISTS permiso varchar(20) NOT NULL DEFAULT 'lectura';

-- ---------------------------------------------------------------------
-- 2. CHECK constraint para validar valores permitidos
-- ---------------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_usuario_modulo_permiso') THEN
        ALTER TABLE usuario_modulo
            ADD CONSTRAINT chk_usuario_modulo_permiso
            CHECK (permiso IN ('lectura', 'editor'));
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT column_name, data_type, is_nullable, column_default
--      FROM information_schema.columns
--      WHERE table_name = 'usuario_modulo' AND column_name = 'permiso';
--    SELECT conname FROM pg_constraint WHERE conname = 'chk_usuario_modulo_permiso';
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- ALTER TABLE usuario_modulo DROP CONSTRAINT IF EXISTS chk_usuario_modulo_permiso;
-- ALTER TABLE usuario_modulo DROP COLUMN IF EXISTS permiso;
-- COMMIT;
-- =====================================================================
