-- =====================================================================
--  MIGRACION - Extras: acompanamientos generales + asignacion por producto
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-17
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. `extras.categoria_id` pasa a nullable + se agrega `es_general`
--       (boolean). Una extra sigue siendo "de categoria" (categoria_id
--       NOT NULL, es_general=false, comportamiento actual sin cambios) O
--       pasa a ser "general" (categoria_id NULL, es_general=true: aplica
--       a TODOS los productos, de cualquier categoria).
--       CHECK constraint garantiza que nunca queden ambas cosas a la vez
--       ni ninguna de las dos.
--    2. Tabla nueva `producto_extras` (pivote producto_id + extra_id):
--       asignacion PUNTUAL de una extra a un producto especifico, aparte
--       de la categoria/general (ej. una extra de "Pastas" que tambien se
--       quiere ofrecer en una pizza puntual sin volverla general).
--
--  Resolucion de "extras disponibles para un producto" (logica en backend,
--  no en BD): es_general=true OR categoria_id = producto.categoria_id OR
--  existe fila en producto_extras para ese producto+extra.
--
--  Referencia: documentacion/back-document/HiloActualBack.md (sesion 2026-07-17).
--  Re-ejecutable: usa IF NOT EXISTS / guards, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. extras: categoria_id nullable + es_general
-- ---------------------------------------------------------------------
ALTER TABLE extras
    ALTER COLUMN categoria_id DROP NOT NULL,
    ADD COLUMN IF NOT EXISTS es_general boolean NOT NULL DEFAULT false;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_extras_general_xor_categoria') THEN
        ALTER TABLE extras
            ADD CONSTRAINT chk_extras_general_xor_categoria
            CHECK (
                (es_general = true  AND categoria_id IS NULL)
                OR
                (es_general = false AND categoria_id IS NOT NULL)
            );
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. producto_extras: asignacion puntual extra <-> producto
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_extras (
    id          bigserial PRIMARY KEY,
    producto_id bigint    NOT NULL,
    extra_id    bigint    NOT NULL,
    created_at  timestamp,
    updated_at  timestamp
);

CREATE INDEX IF NOT EXISTS idx_producto_extras_producto ON producto_extras (producto_id);
CREATE INDEX IF NOT EXISTS idx_producto_extras_extra ON producto_extras (extra_id);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_producto_extras') THEN
        ALTER TABLE producto_extras
            ADD CONSTRAINT uq_producto_extras UNIQUE (producto_id, extra_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_pe_producto') THEN
        ALTER TABLE producto_extras
            ADD CONSTRAINT fk_pe_producto
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_pe_extra') THEN
        ALTER TABLE producto_extras
            ADD CONSTRAINT fk_pe_extra
            FOREIGN KEY (extra_id) REFERENCES extras(id) ON DELETE CASCADE;
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT conname FROM pg_constraint WHERE conname = 'chk_extras_general_xor_categoria';
--    SELECT column_name, is_nullable FROM information_schema.columns WHERE table_name='extras' AND column_name='categoria_id';
--    SELECT * FROM producto_extras;
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS producto_extras;
-- ALTER TABLE extras DROP CONSTRAINT IF EXISTS chk_extras_general_xor_categoria;
-- ALTER TABLE extras DROP COLUMN IF EXISTS es_general;
-- ALTER TABLE extras ALTER COLUMN categoria_id SET NOT NULL;
-- COMMIT;
-- =====================================================================
