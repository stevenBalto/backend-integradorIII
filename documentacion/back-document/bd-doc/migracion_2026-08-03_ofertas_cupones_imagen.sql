-- =====================================================================
--  MIGRACION  -  imagen_url en ofertas y cupones
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-03
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Agrega columna `imagen_url` (nullable) a las tablas `ofertas` y
--    `cupones` para permitir banners/imagenes promocionales en el
--    frontend (Home, kiosko, app cliente).
--
--  Re-ejecutable: usa IF NOT EXISTS.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. imagen_url en ofertas
-- ---------------------------------------------------------------------
ALTER TABLE ofertas
    ADD COLUMN IF NOT EXISTS imagen_url varchar(255) NULL;

-- ---------------------------------------------------------------------
-- 2. imagen_url en cupones
-- ---------------------------------------------------------------------
ALTER TABLE cupones
    ADD COLUMN IF NOT EXISTS imagen_url varchar(255) NULL;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT table_name, column_name, data_type, is_nullable
--      FROM information_schema.columns
--      WHERE table_name IN ('ofertas', 'cupones')
--        AND column_name = 'imagen_url';
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- ALTER TABLE ofertas DROP COLUMN IF EXISTS imagen_url;
-- ALTER TABLE cupones DROP COLUMN IF EXISTS imagen_url;
-- COMMIT;
-- =====================================================================
