-- =====================================================================
--  MIGRACION - configuraciones: unique por (instancia_id, clave)
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-22
-- ---------------------------------------------------------------------
--  QUE HACE:
--    La tabla `configuraciones` (clave-valor) YA tiene instancia_id y el
--    modelo la aisla por instancia, pero el UNIQUE estaba sobre `clave`
--    GLOBAL -> dos instancias no podian usar la misma clave (ej. cada
--    sucursal con su propio 'negocio_nombre'). Se cambia el unique a
--    (instancia_id, clave) para que cada instancia tenga su propio juego
--    de ajustes.
--
--  Tabla VACIA hoy (0 filas) -> el cambio no arriesga datos.
--  Re-ejecutable: guards por nombre de constraint/indice.
-- =====================================================================

BEGIN;

-- Quitar el unique global sobre clave (si existe).
ALTER TABLE configuraciones DROP CONSTRAINT IF EXISTS configuraciones_clave_key;

-- Unique compuesto por instancia + clave.
CREATE UNIQUE INDEX IF NOT EXISTS uq_configuraciones_instancia_clave
    ON configuraciones (instancia_id, clave);

COMMIT;

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP INDEX IF EXISTS uq_configuraciones_instancia_clave;
-- ALTER TABLE configuraciones ADD CONSTRAINT configuraciones_clave_key UNIQUE (clave);
-- COMMIT;
