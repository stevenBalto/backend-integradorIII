-- =====================================================================
--  MIGRACION - Foto de extras + descripcion de cantidad en tamanos
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-19
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. `extras.imagen_url` (nullable, igual patron que `productos.imagen_url`)
--       para mostrar los acompanamientos con foto en el carrito, estilo
--       "upsell" (Taco Bell), y aumentar la conversion de venta.
--    2. `producto_tamanos.descripcion` (nullable, varchar corto) para
--       mostrar detalle de cantidad junto al nombre del tamano
--       (ej. "Grande" + "12 slices" = "Grande - 12 slices").
--
--  Re-ejecutable: usa IF NOT EXISTS, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

ALTER TABLE extras
    ADD COLUMN IF NOT EXISTS imagen_url varchar(255);

ALTER TABLE producto_tamanos
    ADD COLUMN IF NOT EXISTS descripcion varchar(60);

COMMIT;

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- ALTER TABLE producto_tamanos DROP COLUMN IF EXISTS descripcion;
-- ALTER TABLE extras DROP COLUMN IF EXISTS imagen_url;
-- COMMIT;
