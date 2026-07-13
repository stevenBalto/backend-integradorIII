-- =====================================================================
--  MIGRACION F7  -  Ofertas y Cupones GLOBALES (no aislados por instancia)
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-13
-- ---------------------------------------------------------------------
--  Decision del negocio: las promociones/cupones son NACIONALES (mismas para
--  todas las sucursales, estilo Papa John's), no por zona. Por eso se quita la
--  obligatoriedad de instancia_id en ofertas/cupones (quedan globales).
--  La columna se deja (nullable) por compatibilidad; el codigo ya no filtra.
--  Re-ejecutable.
-- =====================================================================

BEGIN;

ALTER TABLE ofertas ALTER COLUMN instancia_id DROP NOT NULL;
ALTER TABLE cupones ALTER COLUMN instancia_id DROP NOT NULL;

COMMIT;

-- ROLLBACK (volver a aislarlas): requiere que todas las filas tengan instancia_id.
-- UPDATE ofertas SET instancia_id = 1 WHERE instancia_id IS NULL;
-- UPDATE cupones SET instancia_id = 1 WHERE instancia_id IS NULL;
-- ALTER TABLE ofertas ALTER COLUMN instancia_id SET NOT NULL;
-- ALTER TABLE cupones ALTER COLUMN instancia_id SET NOT NULL;
