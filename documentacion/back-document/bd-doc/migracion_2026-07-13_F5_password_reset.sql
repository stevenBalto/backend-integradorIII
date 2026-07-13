-- =====================================================================
--  MIGRACION F5  -  Reset de contraseña por correo (¿Olvidaste tu contraseña?)
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-13
-- ---------------------------------------------------------------------
--  Crea la tabla de tokens de restablecimiento. Sirve para AMBAS identidades
--  (users y superadministradores) gracias a la columna actor_type.
--  El token se guarda HASHEADO; en el correo viaja el token en claro.
--  Re-ejecutable.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         bigserial PRIMARY KEY,
    email      varchar(150) NOT NULL,
    token      varchar(255) NOT NULL,          -- hash del token
    actor_type varchar(30)  NOT NULL,          -- 'User' | 'SuperAdministrador'
    created_at timestamp
);

CREATE INDEX IF NOT EXISTS idx_prt_email ON password_reset_tokens (email);

COMMIT;

-- =====================================================================
--  ROLLBACK: DROP TABLE IF EXISTS password_reset_tokens;
-- =====================================================================
