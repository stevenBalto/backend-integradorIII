-- =====================================================================
--  MIGRACION - Modulo Pedidos: tamanos de producto + codigo/pago + sucursal
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-16
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. Crea `producto_tamanos` (tamanos opcionales por producto, ej.
--       Personal/Mediana/Grande para pizzas, cada uno con su propio precio).
--    2. Agrega a `detalle_pedido` referencia + snapshot del tamano elegido
--       (sigue la convencion de "precios se congelan": si el tamano se
--       borra/renombra despues, el pedido historico sigue mostrando lo real).
--    3. Agrega a `pedidos`: codigo de seguimiento (unico, publico), y
--       pagado/pagado_en (pago en caja, reemplaza el uso de metodos_pago/
--       pagos que no encajan para "efectivo sin metodo guardado").
--    4. Siembra 1 sucursal para la instancia 1 (hoy `sucursales` esta vacia
--       por completo, bloqueando el selector de sucursal del carrito).
--       `extras`/`detalle_pedido_extras` NO se tocan: se reutilizan tal cual
--       (ya existian, ligadas a categoria_id) como "acompanamientos".
--
--  Referencia: documentacion/back-document/HiloActualBack.md (sesion 2026-07-16).
--  Re-ejecutable: usa IF NOT EXISTS / guards, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Tamanos de producto
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_tamanos (
    id          bigserial PRIMARY KEY,
    producto_id bigint        NOT NULL,
    nombre      varchar(40)   NOT NULL,
    precio      numeric(10,2) NOT NULL,
    orden       integer       NOT NULL DEFAULT 0,
    activo      boolean       NOT NULL DEFAULT true,
    created_at  timestamp,
    updated_at  timestamp,
    deleted_at  timestamp
);

CREATE INDEX IF NOT EXISTS idx_producto_tamanos_producto ON producto_tamanos (producto_id);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_pt_producto') THEN
        ALTER TABLE producto_tamanos
            ADD CONSTRAINT fk_pt_producto
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. detalle_pedido: referencia + snapshot del tamano elegido
-- ---------------------------------------------------------------------
ALTER TABLE detalle_pedido
    ADD COLUMN IF NOT EXISTS producto_tamano_id bigint,
    ADD COLUMN IF NOT EXISTS tamano_nombre varchar(40);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_dp_tamano') THEN
        ALTER TABLE detalle_pedido
            ADD CONSTRAINT fk_dp_tamano
            FOREIGN KEY (producto_tamano_id) REFERENCES producto_tamanos(id) ON DELETE SET NULL;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 3. pedidos: codigo de seguimiento + pago en caja
-- ---------------------------------------------------------------------
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS codigo varchar(12),
    ADD COLUMN IF NOT EXISTS pagado boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS pagado_en timestamp;

-- La tabla esta vacia hoy (0 filas) asi que no hace falta backfill antes
-- de endurecer a NOT NULL + UNIQUE. Si en el futuro esto se re-corre con
-- filas ya existentes, revisar antes de este bloque.
DO $$
BEGIN
    IF (SELECT count(*) FROM pedidos WHERE codigo IS NULL) = 0 THEN
        ALTER TABLE pedidos ALTER COLUMN codigo SET NOT NULL;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_pedidos_codigo') THEN
        ALTER TABLE pedidos ADD CONSTRAINT uq_pedidos_codigo UNIQUE (codigo);
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 4. Siembra: 1 sucursal para la instancia 1 (desbloquea el selector)
-- ---------------------------------------------------------------------
INSERT INTO sucursales (nombre, direccion, telefono, activa, instancia_id, created_at, updated_at)
SELECT 'Rooster Pizza & Grill - La Fortuna', 'La Fortuna, San Carlos, Alajuela', NULL, true, 1, now(), now()
WHERE NOT EXISTS (SELECT 1 FROM sucursales WHERE instancia_id = 1);

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT column_name FROM information_schema.columns WHERE table_name='producto_tamanos';
--    SELECT column_name FROM information_schema.columns WHERE table_name='detalle_pedido' AND column_name LIKE '%tamano%';
--    SELECT codigo, pagado, pagado_en FROM pedidos LIMIT 5;
--    SELECT * FROM sucursales;
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DELETE FROM sucursales WHERE instancia_id = 1 AND nombre = 'Rooster Pizza & Grill - La Fortuna';
-- ALTER TABLE pedidos DROP CONSTRAINT IF EXISTS uq_pedidos_codigo;
-- ALTER TABLE pedidos DROP COLUMN IF EXISTS codigo;
-- ALTER TABLE pedidos DROP COLUMN IF EXISTS pagado;
-- ALTER TABLE pedidos DROP COLUMN IF EXISTS pagado_en;
-- ALTER TABLE detalle_pedido DROP CONSTRAINT IF EXISTS fk_dp_tamano;
-- ALTER TABLE detalle_pedido DROP COLUMN IF EXISTS producto_tamano_id;
-- ALTER TABLE detalle_pedido DROP COLUMN IF EXISTS tamano_nombre;
-- DROP TABLE IF EXISTS producto_tamanos;
-- COMMIT;
-- =====================================================================
