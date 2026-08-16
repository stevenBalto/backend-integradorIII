-- =====================================================================
--  MIGRACION  -  Sincronizar BD local con cambios ya aprobados
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-07
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Aplica a esta BD local los cambios documentados en
--    HiloActualBack.md sesion "2026-08-07 (cont.) - Ofertas/Cupones:
--    alcance por cliente especifico + fix de columna faltante", ya
--    aprobados explicitamente y aplicados en la BD local de otro
--    integrante del equipo, pero que a esta BD local nunca llegaron
--    (no se aplican por `php artisan migrate`, siguen la regla del
--    proyecto de SQL directo documentado).
--
--    1. `ofertas`/`cupones`: columna `alcance` ('todos'|'especifico').
--    2. `ofertas`/`cupones`: columna `imagen_url` (bug preexistente,
--       aprobada 2026-08-03, nunca aplicada en ninguna BD local hasta
--       la sesion del 08-07).
--    3. Tablas puente nuevas `oferta_cliente` y `cupon_cliente`
--       (mismo patron que `oferta_producto`).
--    4. `usuario_modulo.permiso` (mismo contenido que
--       `migracion_2026-08-03_usuario_modulo_permiso.sql`, por si esa
--       tampoco se habia corrido en esta BD).
--
--  Re-ejecutable: usa IF NOT EXISTS / guards en todo.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. ofertas.alcance / cupones.alcance
-- ---------------------------------------------------------------------
ALTER TABLE ofertas ADD COLUMN IF NOT EXISTS alcance varchar(20) NOT NULL DEFAULT 'todos';
ALTER TABLE cupones ADD COLUMN IF NOT EXISTS alcance varchar(20) NOT NULL DEFAULT 'todos';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ofertas_alcance_check') THEN
        ALTER TABLE ofertas ADD CONSTRAINT ofertas_alcance_check
            CHECK (alcance IN ('todos', 'especifico'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupones_alcance_check') THEN
        ALTER TABLE cupones ADD CONSTRAINT cupones_alcance_check
            CHECK (alcance IN ('todos', 'especifico'));
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. ofertas.imagen_url / cupones.imagen_url
-- ---------------------------------------------------------------------
ALTER TABLE ofertas ADD COLUMN IF NOT EXISTS imagen_url varchar(255);
ALTER TABLE cupones ADD COLUMN IF NOT EXISTS imagen_url varchar(255);

-- ---------------------------------------------------------------------
-- 3. Tablas puente oferta_cliente / cupon_cliente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS oferta_cliente (
    id bigserial PRIMARY KEY,
    oferta_id bigint NOT NULL,
    cliente_id bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS cupon_cliente (
    id bigserial PRIMARY KEY,
    cupon_id bigint NOT NULL,
    cliente_id bigint NOT NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'oferta_cliente_unique') THEN
        ALTER TABLE oferta_cliente ADD CONSTRAINT oferta_cliente_unique UNIQUE (oferta_id, cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_oc_oferta') THEN
        ALTER TABLE oferta_cliente ADD CONSTRAINT fk_oc_oferta
            FOREIGN KEY (oferta_id) REFERENCES ofertas(id) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_oc_cliente') THEN
        ALTER TABLE oferta_cliente ADD CONSTRAINT fk_oc_cliente
            FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupon_cliente_unique') THEN
        ALTER TABLE cupon_cliente ADD CONSTRAINT cupon_cliente_unique UNIQUE (cupon_id, cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cc_cupon') THEN
        ALTER TABLE cupon_cliente ADD CONSTRAINT fk_cc_cupon
            FOREIGN KEY (cupon_id) REFERENCES cupones(id) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cc_cliente') THEN
        ALTER TABLE cupon_cliente ADD CONSTRAINT fk_cc_cliente
            FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE;
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 4. usuario_modulo.permiso (por si migracion_2026-08-03 tampoco corrio aqui)
-- ---------------------------------------------------------------------
ALTER TABLE usuario_modulo ADD COLUMN IF NOT EXISTS permiso varchar(20) NOT NULL DEFAULT 'lectura';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_usuario_modulo_permiso') THEN
        ALTER TABLE usuario_modulo
            ADD CONSTRAINT chk_usuario_modulo_permiso
            CHECK (permiso IN ('lectura', 'editor'));
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT column_name FROM information_schema.columns
--      WHERE table_name IN ('ofertas','cupones') AND column_name IN ('alcance','imagen_url');
--    SELECT column_name FROM information_schema.columns
--      WHERE table_name = 'usuario_modulo' AND column_name = 'permiso';
--    SELECT tablename FROM pg_tables WHERE tablename IN ('oferta_cliente','cupon_cliente');
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS oferta_cliente;
-- DROP TABLE IF EXISTS cupon_cliente;
-- ALTER TABLE ofertas DROP CONSTRAINT IF EXISTS ofertas_alcance_check;
-- ALTER TABLE cupones DROP CONSTRAINT IF EXISTS cupones_alcance_check;
-- ALTER TABLE ofertas DROP COLUMN IF EXISTS alcance;
-- ALTER TABLE cupones DROP COLUMN IF EXISTS alcance;
-- ALTER TABLE ofertas DROP COLUMN IF EXISTS imagen_url;
-- ALTER TABLE cupones DROP COLUMN IF EXISTS imagen_url;
-- ALTER TABLE usuario_modulo DROP CONSTRAINT IF EXISTS chk_usuario_modulo_permiso;
-- ALTER TABLE usuario_modulo DROP COLUMN IF EXISTS permiso;
-- COMMIT;
-- =====================================================================
