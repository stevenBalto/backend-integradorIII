-- =====================================================================
--  MIGRACION  -  insumos y notificaciones dejan de compartirse entre
--                 sedes del mismo negocio
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-14
-- ---------------------------------------------------------------------
--  POR QUE:
--    Al crear las Sedes (2026-08-13) solo Pedido quedo aislado por sede
--    (PerteneceASucursal). Insumo y Notificacion seguian usando SOLO
--    PerteneceAInstancia -> una sede nueva veia automaticamente el
--    inventario y las notificaciones de las demas sedes del negocio.
--    Regla del usuario: SOLO productos, ofertas y cupones se comparten
--    entre sedes del mismo negocio. Todo lo demas (inventario, bandeja
--    de notificaciones) es exclusivo de cada sede.
--
--  QUE HACE:
--    1. insumos.sucursal_id (bigint, NOT NULL, FK -> sucursales) — cada
--       insumo pasa a pertenecer a UNA sede especifica, sin excepcion.
--       Backfill: todos los insumos existentes hoy son de la sede 1
--       (La Fortuna, la unica que operaba hasta ahora).
--    2. notificaciones.sucursal_id (bigint, NULLABLE, FK -> sucursales)
--       — NULLABLE a proposito: hay tipos de notificacion que SI son del
--       negocio completo (producto_nuevo, cliente_nuevo, usuario_nuevo -
--       no tiene sentido que sean "de una sede"). Los que si nacen de un
--       lugar puntual (pedido_nuevo, resena_nueva, stock_bajo) llevan la
--       sede de su origen. NULL = visible para todas las sedes.
--       Backfill: pedido_nuevo/resena_nueva toman la sucursal_id del
--       pedido asociado (via pedido_id); stock_bajo y el resto quedan
--       NULL (no hay forma de inferir la sede de un insumo retroactivo
--       sin la migracion anterior ya aplicada).
--
--  Re-ejecutable: guardas IF NOT EXISTS / DO $$ para no fallar si ya se
--  aplico.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. insumos.sucursal_id
-- ---------------------------------------------------------------------
ALTER TABLE insumos ADD COLUMN IF NOT EXISTS sucursal_id bigint;

UPDATE insumos SET sucursal_id = 1 WHERE sucursal_id IS NULL;

ALTER TABLE insumos ALTER COLUMN sucursal_id SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_insumos_sucursal') THEN
        ALTER TABLE insumos
            ADD CONSTRAINT fk_insumos_sucursal FOREIGN KEY (sucursal_id)
            REFERENCES sucursales(id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_insumos_sucursal ON insumos USING btree (sucursal_id);

-- ---------------------------------------------------------------------
-- 2. notificaciones.sucursal_id (nullable)
-- ---------------------------------------------------------------------
ALTER TABLE notificaciones ADD COLUMN IF NOT EXISTS sucursal_id bigint;

-- Backfill: pedido_nuevo/resena_nueva heredan la sede del pedido asociado.
UPDATE notificaciones n
SET sucursal_id = p.sucursal_id
FROM pedidos p
WHERE n.pedido_id = p.id
  AND n.sucursal_id IS NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_notificaciones_sucursal') THEN
        ALTER TABLE notificaciones
            ADD CONSTRAINT fk_notificaciones_sucursal FOREIGN KEY (sucursal_id)
            REFERENCES sucursales(id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_notificaciones_sucursal ON notificaciones USING btree (sucursal_id);

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT sucursal_id, count(*) FROM insumos GROUP BY sucursal_id;
--    SELECT tipo, sucursal_id, count(*) FROM notificaciones GROUP BY tipo, sucursal_id ORDER BY tipo;
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta.
-- =====================================================================
-- BEGIN;
-- ALTER TABLE insumos DROP CONSTRAINT IF EXISTS fk_insumos_sucursal;
-- ALTER TABLE insumos ALTER COLUMN sucursal_id DROP NOT NULL;
-- ALTER TABLE insumos DROP COLUMN IF EXISTS sucursal_id;
-- ALTER TABLE notificaciones DROP CONSTRAINT IF EXISTS fk_notificaciones_sucursal;
-- ALTER TABLE notificaciones DROP COLUMN IF EXISTS sucursal_id;
-- COMMIT;
-- =====================================================================
