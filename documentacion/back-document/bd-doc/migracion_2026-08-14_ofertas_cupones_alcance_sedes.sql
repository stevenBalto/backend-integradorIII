-- =====================================================================
--  MIGRACION  -  alcance por sede en ofertas y cupones
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-14
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Una oferta/cupon sigue siendo del NEGOCIO completo (instancia_id, ya
--    migrado en migracion_2026-08-14_ofertas_cupones_por_instancia.sql) —
--    visible/administrable desde cualquier sede. Lo que se agrega es la
--    posibilidad de restringir en CUALES sedes se puede CANJEAR:
--
--    1. Columna `alcance_sedes` en `ofertas` y `cupones`.
--       varchar(20) NOT NULL DEFAULT 'todas', CHECK ('todas','especifica').
--       El DEFAULT deja todo lo existente como 'todas' (comportamiento
--       actual: se puede canjear en cualquier sede del negocio).
--    2. Tablas puente `oferta_sucursal` y `cupon_sucursal` (M-N contra
--       `sucursales`, NO contra `instancias` — es la sede puntual, no el
--       negocio completo), con UNIQUE por par y ON DELETE CASCADE.
--       Mismo patron que `oferta_cliente`/`cupon_cliente` (alcance por
--       cliente especifico), pero por sede.
--
--  Re-ejecutable: usa IF NOT EXISTS y guardas DO $$ para los constraints.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. alcance_sedes en ofertas
-- ---------------------------------------------------------------------
ALTER TABLE ofertas
    ADD COLUMN IF NOT EXISTS alcance_sedes varchar(20) NOT NULL DEFAULT 'todas';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'ofertas_alcance_sedes_check'
    ) THEN
        ALTER TABLE ofertas
            ADD CONSTRAINT ofertas_alcance_sedes_check
            CHECK (alcance_sedes IN ('todas', 'especifica'));
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. alcance_sedes en cupones
-- ---------------------------------------------------------------------
ALTER TABLE cupones
    ADD COLUMN IF NOT EXISTS alcance_sedes varchar(20) NOT NULL DEFAULT 'todas';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'cupones_alcance_sedes_check'
    ) THEN
        ALTER TABLE cupones
            ADD CONSTRAINT cupones_alcance_sedes_check
            CHECK (alcance_sedes IN ('todas', 'especifica'));
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 3. Tabla puente oferta_sucursal
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS oferta_sucursal (
    id          bigserial PRIMARY KEY,
    oferta_id   bigint NOT NULL,
    sucursal_id bigint NOT NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'oferta_sucursal_unique') THEN
        ALTER TABLE oferta_sucursal
            ADD CONSTRAINT oferta_sucursal_unique UNIQUE (oferta_id, sucursal_id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_os_oferta') THEN
        ALTER TABLE oferta_sucursal
            ADD CONSTRAINT fk_os_oferta FOREIGN KEY (oferta_id)
            REFERENCES ofertas(id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_os_sucursal') THEN
        ALTER TABLE oferta_sucursal
            ADD CONSTRAINT fk_os_sucursal FOREIGN KEY (sucursal_id)
            REFERENCES sucursales(id) ON DELETE CASCADE;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 4. Tabla puente cupon_sucursal
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cupon_sucursal (
    id          bigserial PRIMARY KEY,
    cupon_id    bigint NOT NULL,
    sucursal_id bigint NOT NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupon_sucursal_unique') THEN
        ALTER TABLE cupon_sucursal
            ADD CONSTRAINT cupon_sucursal_unique UNIQUE (cupon_id, sucursal_id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cs_cupon') THEN
        ALTER TABLE cupon_sucursal
            ADD CONSTRAINT fk_cs_cupon FOREIGN KEY (cupon_id)
            REFERENCES cupones(id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cs_sucursal') THEN
        ALTER TABLE cupon_sucursal
            ADD CONSTRAINT fk_cs_sucursal FOREIGN KEY (sucursal_id)
            REFERENCES sucursales(id) ON DELETE CASCADE;
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT table_name, column_name, column_default
--      FROM information_schema.columns
--      WHERE table_name IN ('ofertas', 'cupones') AND column_name = 'alcance_sedes';
--
--    SELECT table_name FROM information_schema.tables
--      WHERE table_name IN ('oferta_sucursal', 'cupon_sucursal');
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta.
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS oferta_sucursal;
-- DROP TABLE IF EXISTS cupon_sucursal;
-- ALTER TABLE ofertas DROP CONSTRAINT IF EXISTS ofertas_alcance_sedes_check;
-- ALTER TABLE cupones DROP CONSTRAINT IF EXISTS cupones_alcance_sedes_check;
-- ALTER TABLE ofertas DROP COLUMN IF EXISTS alcance_sedes;
-- ALTER TABLE cupones DROP COLUMN IF EXISTS alcance_sedes;
-- COMMIT;
-- =====================================================================
