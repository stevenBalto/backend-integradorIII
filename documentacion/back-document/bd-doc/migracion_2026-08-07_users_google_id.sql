-- ============================================================================
-- Migracion 2026-08-07 — Inicio de sesion con Google (columna users.google_id)
--
-- Aprobada explicitamente por el usuario (regla de esquema: ninguna columna
-- nueva sin su OK). Es la UNICA columna que agrega el login con Google.
--
-- Por que google_id y no vincular solo por email:
--   El email identifica a la persona, pero no al proveedor. Con google_id la
--   cuenta queda atada al identificador estable de Google (el "sub" del token),
--   que no cambia aunque el usuario cambie su correo en Google.
--
-- Nota sobre users.password (NOT NULL): NO se toca. Un usuario que entra solo
-- con Google se crea con un hash aleatorio que nunca se usa para autenticar.
-- Hacer password nullable obligaria a revisar todo el flujo de login, expiracion
-- y cambio obligatorio de contrasena, y no aporta nada aca.
-- ============================================================================

ALTER TABLE public.users
    ADD COLUMN IF NOT EXISTS google_id character varying(64);

COMMENT ON COLUMN public.users.google_id IS
    'Identificador estable de la cuenta de Google (claim "sub" del ID token). NULL si el usuario no vinculo Google.';

-- Indice UNICO parcial: dos usuarios no pueden compartir la misma cuenta de
-- Google, pero si puede haber muchos usuarios con google_id NULL (los que
-- entran con email y contrasena).
CREATE UNIQUE INDEX IF NOT EXISTS users_google_id_unique
    ON public.users (google_id)
    WHERE google_id IS NOT NULL;
