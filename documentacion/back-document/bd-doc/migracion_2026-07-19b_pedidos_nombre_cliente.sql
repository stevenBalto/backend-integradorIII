-- =====================================================================
--  MIGRACION - pedidos.nombre_cliente ("a nombre de quien")
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-19
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    `pedidos.nombre_cliente` (nullable, varchar): el checkout le pregunta
--    al cliente "a nombre de quien" (puede ser distinto del nombre de la
--    cuenta logueada, ej. alguien pide usando la cuenta familiar a nombre
--    de otra persona). Antes ese texto se pedia/validaba en el frontend
--    pero NUNCA se mandaba al backend ni habia donde guardarlo -> al
--    buscar el pedido despues, siempre se mostraba el nombre de la CUENTA,
--    no el que se escribio. Nullable porque los pedidos ya existentes no
--    lo tienen.
--
--  Re-ejecutable: usa IF NOT EXISTS, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS nombre_cliente varchar(120);

COMMIT;

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- ALTER TABLE pedidos DROP COLUMN IF EXISTS nombre_cliente;
-- COMMIT;
