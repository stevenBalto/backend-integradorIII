-- =====================================================================
--  MIGRACION F6  -  Aislar Inventario (insumos) por instancia
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-13   |   Depende de: migracion_2026-07-13_insumos.sql
-- ---------------------------------------------------------------------
--  El modulo Inventario del compañero se creó sin instancia_id (trabajo en
--  paralelo). Esto lo integra al multi-tenant: cada sucursal tiene SU propio
--  inventario. `insumo_movimientos` NO lleva instancia_id: hereda vía insumo_id.
--  Re-ejecutable.
-- =====================================================================

BEGIN;

ALTER TABLE insumos ADD COLUMN IF NOT EXISTS instancia_id bigint;
UPDATE insumos SET instancia_id = 1 WHERE instancia_id IS NULL;
ALTER TABLE insumos ALTER COLUMN instancia_id SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_insumos_instancia') THEN
        ALTER TABLE insumos
            ADD CONSTRAINT fk_insumos_instancia
            FOREIGN KEY (instancia_id) REFERENCES instancias(id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_insumos_instancia ON insumos (instancia_id);

COMMIT;

-- ROLLBACK:
-- ALTER TABLE insumos DROP CONSTRAINT IF EXISTS fk_insumos_instancia;
-- DROP INDEX IF EXISTS idx_insumos_instancia;
-- ALTER TABLE insumos DROP COLUMN IF EXISTS instancia_id;
