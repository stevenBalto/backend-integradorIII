-- =====================================================================
--  MIGRACION F1  -  Superadministradores (identidad global aislada)
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-12   |   Depende de: F0 (tabla instancias)
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. Crea la tabla `superadministradores` (login/tabla aparte de users).
--    2. Ahora que existe, agrega la FK instancias.creada_por -> superadministradores.
--
--  Referencia de diseno: ../ARQUITECTURA-SUPERADMIN-MULTITENANT.md (Fase F1).
--  El login aislado y el CRUD son codigo Laravel (van aparte de este SQL).
--  Re-ejecutable: IF NOT EXISTS / guards.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Tabla superadministradores
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS superadministradores (
    id               bigserial PRIMARY KEY,
    nombre           varchar(120) NOT NULL,
    usuario          varchar(60)  NOT NULL UNIQUE,
    email            varchar(150) NOT NULL UNIQUE,
    password         varchar(255) NOT NULL,
    activo           boolean      NOT NULL DEFAULT true,
    ultimo_acceso_en timestamp,
    created_at       timestamp,
    updated_at       timestamp,
    deleted_at       timestamp
);

-- ---------------------------------------------------------------------
-- 2. FK pendiente de F0: instancias.creada_por -> superadministradores(id)
-- ---------------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_instancias_creada_por') THEN
        ALTER TABLE instancias
            ADD CONSTRAINT fk_instancias_creada_por
            FOREIGN KEY (creada_por) REFERENCES superadministradores(id) ON DELETE SET NULL;
    END IF;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional, correr aparte):
--    SELECT * FROM superadministradores;
--    SELECT conname FROM pg_constraint WHERE conname = 'fk_instancias_creada_por';
-- =====================================================================

-- =====================================================================
--  ROLLBACK (deshacer F1) - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- ALTER TABLE instancias DROP CONSTRAINT IF EXISTS fk_instancias_creada_por;
-- DROP TABLE IF EXISTS superadministradores;
-- COMMIT;
-- =====================================================================
