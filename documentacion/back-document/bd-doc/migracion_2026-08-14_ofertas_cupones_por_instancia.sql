-- =====================================================================
--  MIGRACION  -  ofertas y cupones dejan de ser nacionales, pasan a
--                 pertenecer a una instancia
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-14
-- ---------------------------------------------------------------------
--  POR QUE:
--    Decision de negocio anterior (migracion_2026-07-13_F7): ofertas y
--    cupones eran GLOBALES/nacionales, instancia_id nullable, todas las
--    instancias veian las mismas. Se revierte esa decision: cada
--    instancia nueva tiene que poder arrancar SIN ofertas/cupones (o con
--    una copia de otra instancia si el superadmin lo pide al crearla) en
--    vez de heredar automaticamente las de todo el sistema.
--
--  QUE HACE:
--    1. Las ofertas/cupones existentes (instancia_id NULL, todas las de
--       antes de este cambio) quedan asignadas a la instancia 1
--       (Rooster Pizza & Grill - la unica instancia real hasta ahora).
--    2. instancia_id pasa a NOT NULL en ambas tablas (ya tenian el indice
--       y el FK a instancias desde F0_multitenant, solo faltaba el NOT
--       NULL que F7 le habia quitado a proposito).
--    3. cupones.codigo: la unicidad era GLOBAL (UNIQUE(codigo) solo).
--       Con cupones por instancia, dos negocios distintos tienen que
--       poder usar el mismo codigo (ej. "BIENVENIDO10" en ambos) sin
--       chocar, y clonar un cupon de otra instancia (feature de
--       "cargar catalogo" al crear una instancia) tampoco puede chocar
--       contra si mismo. Se cambia a UNIQUE(instancia_id, codigo).
--
--  Re-ejecutable: los UPDATE son idempotentes (solo tocan filas NULL);
--  los ALTER/CONSTRAINT usan guardas para no fallar si ya se aplicaron.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Backfill: ofertas/cupones huerfanos (instancia_id NULL) -> instancia 1
-- ---------------------------------------------------------------------
UPDATE ofertas SET instancia_id = 1 WHERE instancia_id IS NULL;
UPDATE cupones SET instancia_id = 1 WHERE instancia_id IS NULL;

-- ---------------------------------------------------------------------
-- 2. instancia_id NOT NULL
-- ---------------------------------------------------------------------
ALTER TABLE ofertas ALTER COLUMN instancia_id SET NOT NULL;
ALTER TABLE cupones ALTER COLUMN instancia_id SET NOT NULL;

-- ---------------------------------------------------------------------
-- 3. cupones.codigo: unicidad global -> unicidad por instancia
-- ---------------------------------------------------------------------
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupones_codigo_key') THEN
        ALTER TABLE cupones DROP CONSTRAINT cupones_codigo_key;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cupones_instancia_codigo_key') THEN
        ALTER TABLE cupones
            ADD CONSTRAINT cupones_instancia_codigo_key UNIQUE (instancia_id, codigo);
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT table_name, column_name, is_nullable
--      FROM information_schema.columns
--      WHERE table_name IN ('ofertas', 'cupones') AND column_name = 'instancia_id';
--
--    SELECT conname FROM pg_constraint
--      WHERE conname IN ('cupones_codigo_key', 'cupones_instancia_codigo_key');
--
--    SELECT instancia_id, count(*) FROM ofertas GROUP BY instancia_id;
--    SELECT instancia_id, count(*) FROM cupones GROUP BY instancia_id;
-- =====================================================================

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta. OJO: vuelve a
--  hacer nacionales las ofertas/cupones de TODAS las instancias.
-- =====================================================================
-- BEGIN;
-- ALTER TABLE cupones DROP CONSTRAINT IF EXISTS cupones_instancia_codigo_key;
-- ALTER TABLE cupones ADD CONSTRAINT cupones_codigo_key UNIQUE (codigo);
-- ALTER TABLE ofertas ALTER COLUMN instancia_id DROP NOT NULL;
-- ALTER TABLE cupones ALTER COLUMN instancia_id DROP NOT NULL;
-- COMMIT;
-- =====================================================================
