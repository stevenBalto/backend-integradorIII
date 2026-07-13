-- =====================================================================
--  MIGRACION F3  -  Usuarios (CRUD admin) + permisos por modulo
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-13   |   Depende de: F0 (instancia_id en users)
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. Agrega a `users`: usuario, password_temporal, cambio_password_obligatorio,
--       password_expira_en, dias_expiracion_password, ultimo_acceso_en.
--    2. Crea `modulos` (catalogo) + lo siembra con los modulos reales del panel.
--    3. Crea `usuario_modulo` (a que modulos puede entrar cada usuario;
--       permisos ALMACENADOS INDIVIDUALMENTE, no dependen solo del rol).
--
--  Referencia de diseno: ../ARQUITECTURA-SUPERADMIN-MULTITENANT.md (F3/F4).
--  Re-ejecutable: IF NOT EXISTS / guards / ON CONFLICT.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Columnas nuevas de `users`
-- ---------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS usuario                     varchar(60),
    ADD COLUMN IF NOT EXISTS password_temporal           boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS cambio_password_obligatorio boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS password_expira_en          date,
    ADD COLUMN IF NOT EXISTS dias_expiracion_password    integer,
    ADD COLUMN IF NOT EXISTS ultimo_acceso_en            timestamp;

-- usuario unico (permite varios NULL; los usuarios viejos quedan sin username).
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'users_usuario_unique') THEN
        ALTER TABLE users ADD CONSTRAINT users_usuario_unique UNIQUE (usuario);
    END IF;
END $$;

-- ---------------------------------------------------------------------
-- 2. Catalogo de modulos (los del panel admin real)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modulos (
    id     bigserial PRIMARY KEY,
    clave  varchar(50) NOT NULL UNIQUE,
    nombre varchar(80) NOT NULL,
    orden  integer     NOT NULL DEFAULT 0,
    activo boolean     NOT NULL DEFAULT true
);

INSERT INTO modulos (clave, nombre, orden) VALUES
    ('dashboard',      'Dashboard',          1),
    ('pedidos',        'Pedidos',            2),
    ('menu',           'Menú',               3),
    ('ofertas',        'Ofertas y cupones',  4),
    ('usuarios',       'Usuarios y roles',   5),
    ('analiticas',     'Analíticas',         6),
    ('notificaciones', 'Notificaciones',     7),
    ('resenas',        'Reseñas',            8),
    ('configuracion',  'Configuración',      9)
ON CONFLICT (clave) DO NOTHING;

-- ---------------------------------------------------------------------
-- 3. Permisos por usuario: a que modulos puede entrar (individual).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario_modulo (
    id        bigserial PRIMARY KEY,
    user_id   bigint NOT NULL,
    modulo_id bigint NOT NULL,
    UNIQUE (user_id, modulo_id)
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_usuario_modulo_user') THEN
        ALTER TABLE usuario_modulo
            ADD CONSTRAINT fk_usuario_modulo_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_usuario_modulo_modulo') THEN
        ALTER TABLE usuario_modulo
            ADD CONSTRAINT fk_usuario_modulo_modulo
            FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_usuario_modulo_user ON usuario_modulo (user_id);

COMMIT;

-- =====================================================================
--  VERIFICACION (opcional):
--    SELECT clave, nombre FROM modulos ORDER BY orden;
--    SELECT column_name FROM information_schema.columns
--      WHERE table_name='users' AND column_name IN
--      ('usuario','password_temporal','cambio_password_obligatorio');
-- =====================================================================

-- =====================================================================
--  ROLLBACK (deshacer F3) - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS usuario_modulo;
-- DROP TABLE IF EXISTS modulos;
-- ALTER TABLE users DROP CONSTRAINT IF EXISTS users_usuario_unique;
-- ALTER TABLE users
--     DROP COLUMN IF EXISTS usuario,
--     DROP COLUMN IF EXISTS password_temporal,
--     DROP COLUMN IF EXISTS cambio_password_obligatorio,
--     DROP COLUMN IF EXISTS password_expira_en,
--     DROP COLUMN IF EXISTS dias_expiracion_password,
--     DROP COLUMN IF EXISTS ultimo_acceso_en;
-- COMMIT;
-- =====================================================================
