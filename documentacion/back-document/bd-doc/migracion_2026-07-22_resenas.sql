-- =====================================================================
--  MIGRACION - Modulo Reseñas: reseñas generales + por producto
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-22
-- ---------------------------------------------------------------------
--  QUE HACE (la tabla `resenas` ya existia pero solo soportaba reseñas
--  POR PRODUCTO; esto la abre a los dos tipos que pide el modulo):
--
--    1. `resenas.producto_id` pasa a NULLABLE (las reseñas GENERALES —
--       experiencia de la compra — no apuntan a un producto).
--    2. `resenas.tipo` ('general' | 'producto') + CHECK que garantiza la
--       coherencia: general => producto_id NULL ; producto => producto_id NOT NULL.
--    3. `resenas.deleted_at` (soft delete) para "eliminar" del admin.
--       El "ocultar" del admin reutiliza `estado` ('publicada' | 'oculta'):
--       una reseña oculta no la ven los clientes ni cuenta en el promedio
--       publico, pero sigue en la BD. Se le pone DEFAULT 'publicada'.
--    4. Unique parciales anti-duplicado: 1 reseña GENERAL por pedido, y
--       1 reseña por (pedido, producto). Ignoran las borradas (deleted_at).
--    5. `pedidos.resena_descartada` (bool): el cliente puede "descartar" el
--       pedido de reseña estilo Uber; con esto deja de insistir para ese pedido.
--
--    Reglas de negocio (solo quien compro y el pedido esta 'entregado'
--    puede reseñar) viven en el backend (ResenaService), no en el esquema.
--
--  Tabla `resenas` esta VACIA hoy (0 filas) -> el cambio no arriesga datos.
--  Re-ejecutable: usa IF NOT EXISTS / guards por nombre de constraint.
-- =====================================================================

BEGIN;

-- 1. producto_id nullable
ALTER TABLE resenas ALTER COLUMN producto_id DROP NOT NULL;

-- 2. tipo + su CHECK
ALTER TABLE resenas
    ADD COLUMN IF NOT EXISTS tipo varchar(20) NOT NULL DEFAULT 'producto';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_resenas_tipo') THEN
        ALTER TABLE resenas
            ADD CONSTRAINT chk_resenas_tipo CHECK (tipo IN ('general', 'producto'));
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_resenas_tipo_producto') THEN
        ALTER TABLE resenas
            ADD CONSTRAINT chk_resenas_tipo_producto CHECK (
                (tipo = 'general'  AND producto_id IS NULL) OR
                (tipo = 'producto' AND producto_id IS NOT NULL)
            );
    END IF;
END $$;

-- 3. estado con default + soft delete
ALTER TABLE resenas ALTER COLUMN estado SET DEFAULT 'publicada';
ALTER TABLE resenas
    ADD COLUMN IF NOT EXISTS deleted_at timestamp;

-- 4. Anti-duplicado (parciales, ignoran borradas)
CREATE UNIQUE INDEX IF NOT EXISTS uq_resenas_general_por_pedido
    ON resenas (pedido_id)
    WHERE tipo = 'general' AND deleted_at IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_resenas_producto_por_pedido
    ON resenas (pedido_id, producto_id)
    WHERE tipo = 'producto' AND deleted_at IS NULL;

-- Stats por producto (promedio / total / distribucion) — solo visibles.
CREATE INDEX IF NOT EXISTS idx_resenas_producto_visibles
    ON resenas (producto_id)
    WHERE tipo = 'producto' AND estado = 'publicada' AND deleted_at IS NULL;

-- 5. Descartar el pedido de reseña (re-prompt estilo Uber)
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS resena_descartada boolean NOT NULL DEFAULT false;

COMMIT;

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP INDEX IF EXISTS uq_resenas_general_por_pedido;
-- DROP INDEX IF EXISTS uq_resenas_producto_por_pedido;
-- DROP INDEX IF EXISTS idx_resenas_producto_visibles;
-- ALTER TABLE resenas DROP CONSTRAINT IF EXISTS chk_resenas_tipo_producto;
-- ALTER TABLE resenas DROP CONSTRAINT IF EXISTS chk_resenas_tipo;
-- ALTER TABLE resenas DROP COLUMN IF EXISTS tipo;
-- ALTER TABLE resenas DROP COLUMN IF EXISTS deleted_at;
-- ALTER TABLE resenas ALTER COLUMN estado DROP DEFAULT;
-- -- (producto_id vuelve a NOT NULL solo si no hay generales:)
-- -- ALTER TABLE resenas ALTER COLUMN producto_id SET NOT NULL;
-- ALTER TABLE pedidos DROP COLUMN IF EXISTS resena_descartada;
-- COMMIT;
