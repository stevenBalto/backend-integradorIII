-- ============================================================================
-- MIGRACION: Borrar extras de categoria
-- Fecha: 2026-08-03
-- Autor: Christian Paniagua (Rooster Pizza & Grill - Proyecto Integrador III)
-- ============================================================================
--
-- CONTEXTO:
-- En el selector de extras de un producto, los extras a nivel de CATEGORIA
-- (es_general=false Y categoria_id NOT NULL) aparecian bloqueados con un
-- candado y la etiqueta "Categoria". El usuario solicito eliminar esos extras.
-- Los extras GENERALES (es_general=true, categoria_id NULL) NO se tocan.
--
-- CRITERIO DE SEGURIDAD:
-- - producto_extras: FK con ON DELETE CASCADE, se borran automaticamente.
-- - detalle_pedido_extras: FK con ON DELETE RESTRICT y extra_id NOT NULL.
--   Si un extra fue usado en pedidos historicos, NO se puede borrar sin
--   romper el historial de pedidos. Esos extras se SALTAN.
--
-- EXTRAS SALTADOS (NO BORRADOS) por estar en historial de pedidos:
--   id=1, "Extra Queso" (1 uso en detalle_pedido_extras)
--
-- EXTRAS BORRADOS:
--   id=12, "Extra Pepperoni"
--   id=15, "Demo Tocineta"
--   id=16, "Demo Champinones"
--   id=17, "Demo Jalapenos"
--   id=19, "Demo Pina"
--   id=20, "Demo Aceitunas"
--   id=21, "Demo Cebolla caramelizada"
--   id=23, "Demo Pepperoni extra"
--   id=24, "Demo Guacamole"
--   id=25, "Demo Papas"
--   id=27, "Demo Salsa ranch"
--   id=28, "Demo Queso azul"
--   id=29, "Demo Chile dulce"
--
-- FILAS DE producto_extras ELIMINADAS (por CASCADE):
--   id=3 (producto_id=38, extra_id=12 "Extra Pepperoni")
--   id=5 (producto_id=38, extra_id=24 "Demo Guacamole")
--
-- ============================================================================

-- Este script es re-ejecutable: usa WHERE NOT EXISTS para no fallar si ya se borraron.

BEGIN;

-- Borrar extras de categoria que NO estan en historial de pedidos
-- (producto_extras se limpia automaticamente por ON DELETE CASCADE)
DELETE FROM extras
WHERE es_general = false
  AND categoria_id IS NOT NULL
  AND id NOT IN (
      SELECT DISTINCT extra_id FROM detalle_pedido_extras
  );

-- Verificacion: no deberian quedar extras de categoria salvo los que tienen historial
-- SELECT id, nombre, categoria_id, es_general FROM extras
-- WHERE es_general = false AND categoria_id IS NOT NULL;

COMMIT;
