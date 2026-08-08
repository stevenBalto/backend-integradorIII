-- =====================================================================
--  MIGRACION  -  alcance por cliente especifico en ofertas y cupones
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-07
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Soporta que una oferta/cupon sea visible para TODOS los clientes
--    (default, comportamiento actual) o solo para clientes ESPECIFICOS
--    elegidos a mano desde el admin.
--
--    1. Columna `alcance` en `ofertas` y `cupones`
--       varchar(20) NOT NULL DEFAULT 'todos', CHECK ('todos','especifico').
--       El DEFAULT hace que todas las filas existentes queden como 'todos',
--       o sea que nada cambia de comportamiento al aplicarla.
--    2. Tablas puente `oferta_cliente` y `cupon_cliente` (M-N contra
--       `users`), con UNIQUE para que no se pueda asignar dos veces el
--       mismo cliente y ON DELETE CASCADE en ambos lados.
--
--  POR QUE ESTE ARCHIVO EXISTE:
--    La feature se desarrollo el 2026-08-07 y quedo reflejada en el dump
--    `rooster_pizza_bd.sql`, pero nunca se dejo el .sql incremental en
--    esta carpeta. Sin el, una BD local al dia por migraciones revienta
--    con: ERROR: column "alcance" of relation "ofertas" does not exist.
--    El DDL de aca se extrajo del dump para que ambos queden identicos.
--
--  Re-ejecutable: usa IF NOT EXISTS y guardas DO $$ para los constraints
--  (Postgres no soporta ADD CONSTRAINT IF NOT EXISTS).
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. alcance en ofertas
-- ---------------------------------------------------------------------
ALTER TABLE ofertas
    ADD COLUMN IF NOT EXISTS alcance varchar(20) NOT NULL DEFAULT 'todos';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'ofertas_alcance_check'
    ) THEN
        ALTER TABLE ofertas
            ADD CONSTRAINT ofertas_alcance_check
            CHECK (alcance IN ('todos', 'especifico'));
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. alcance en cupones
-- ---------------------------------------------------------------------
ALTER TABLE cupones
    ADD COLUMN IF NOT EXISTS alcance varchar(20) NOT NULL DEFAULT 'todos';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'cupones_alcance_check'
    ) THEN
        ALTER TABLE cupones
            ADD CONSTRAINT cupones_alcance_check
            CHECK (alcance IN ('todos', 'especifico'));
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 3. Tabla puente oferta_cliente
--    Sin timestamps a proposito: es una tabla puente pura (asi esta en el
--    dump y asi la consulta el modelo).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS oferta_cliente (
    id         bigserial PRIMARY KEY,
    oferta_id  bigint NOT NULL,
    cliente_id bigint NOT NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'oferta_cliente_unique') THEN
        ALTER TABLE oferta_cliente
            ADD CONSTRAINT oferta_cliente_unique UNIQUE (oferta_id, cliente_id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_oc_oferta') THEN
        ALTER TABLE oferta_cliente
            ADD CONSTRAINT fk_oc_oferta FOREIGN KEY (oferta_id)
            REFERENCES ofertas(id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_oc_cliente') THEN
        ALTER TABLE oferta_cliente
            ADD CONSTRAINT fk_oc_cliente FOREIGN KEY (cliente_id)
            REFERENCES users(id) ON DELETE CASCADE;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 4. Tabla puente cupon_cliente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cupon_cliente (
    id         bigserial PRIMARY KEY,
    cupon_id   bigint NOT NULL,
    cliente_id bigint NOT NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupon_cliente_unique') THEN
        ALTER TABLE cupon_cliente
            ADD CONSTRAINT cupon_cliente_unique UNIQUE (cupon_id, cliente_id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cc_cupon') THEN
        ALTER TABLE cupon_cliente
            ADD CONSTRAINT fk_cc_cupon FOREIGN KEY (cupon_id)
            REFERENCES cupones(id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cc_cliente') THEN
        ALTER TABLE cupon_cliente
            ADD CONSTRAINT fk_cc_cliente FOREIGN KEY (cliente_id)
            REFERENCES users(id) ON DELETE CASCADE;
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT table_name, column_name, column_default, is_nullable
--      FROM information_schema.columns
--      WHERE table_name IN ('ofertas', 'cupones') AND column_name = 'alcance';
--
--    SELECT table_name FROM information_schema.tables
--      WHERE table_name IN ('oferta_cliente', 'cupon_cliente');
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta.
--  OJO: dropear las puente borra las asignaciones de clientes especificos.
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS oferta_cliente;
-- DROP TABLE IF EXISTS cupon_cliente;
-- ALTER TABLE ofertas DROP CONSTRAINT IF EXISTS ofertas_alcance_check;
-- ALTER TABLE cupones DROP CONSTRAINT IF EXISTS cupones_alcance_check;
-- ALTER TABLE ofertas DROP COLUMN IF EXISTS alcance;
-- ALTER TABLE cupones DROP COLUMN IF EXISTS alcance;
-- COMMIT;
-- =====================================================================
