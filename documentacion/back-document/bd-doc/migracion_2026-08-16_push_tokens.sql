-- =====================================================================
--  MIGRACION - Notificaciones push (FCM) : tabla push_tokens
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-08-16
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Crea `push_tokens`: el registro de dispositivos a los que hay que
--    mandarles una notificacion push. La app Android (Capacitor + Firebase
--    Cloud Messaging) le entrega al backend el token que le dio FCM al
--    instalarse, y el backend lo guarda contra el usuario logueado.
--
--    Hoy el unico disparador es el cambio de estado de un pedido: cuando
--    un admin lo pasa a en_proceso/listo/entregado/cancelado, el cliente
--    dueno del pedido recibe el aviso en el telefono aunque tenga la app
--    cerrada (a diferencia de `notificaciones`, que es la bandeja del
--    ADMIN y se consulta por polling estando adentro del panel).
--
--    Columnas:
--      - user_id ...... duenio del dispositivo. ON DELETE CASCADE: si se
--                       borra el usuario, sus tokens se van con el (no
--                       tiene sentido conservarlos, y evita mandarle push
--                       a una cuenta que ya no existe).
--      - token ........ el registration token que emite FCM. Es por
--                       INSTALACION, no por usuario: un mismo usuario con
--                       telefono y tablet tiene 2 filas.
--      - plataforma ... 'android' hoy; el CHECK deja 'ios' listo para
--                       cuando se compile para iPhone.
--
--    UNIQUE(user_id, token): el cliente reenvia su token en cada arranque
--    de la app (FCM puede rotarlo), asi que el registro es un upsert. Sin
--    esta restriccion se acumularian duplicados y al usuario le llegaria
--    la MISMA notificacion varias veces.
--
--    No lleva `instancia_id`: el aislamiento multi-tenant ya lo da
--    `users.instancia_id` a traves de la FK (un token siempre se alcanza
--    desde su usuario, nunca se consulta la tabla suelta).
--
--  Re-ejecutable: usa IF NOT EXISTS, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS push_tokens (
    id          bigserial     PRIMARY KEY,
    user_id     bigint        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       varchar(255)  NOT NULL,
    plataforma  varchar(20)   NOT NULL DEFAULT 'android' CHECK (plataforma IN ('android','ios')),
    created_at  timestamp,
    updated_at  timestamp,
    UNIQUE (user_id, token)
);

-- La consulta tipica es "dame todos los tokens de este usuario" (al momento
-- de mandarle el push), asi que el indice va por user_id.
CREATE INDEX IF NOT EXISTS idx_push_tokens_user ON push_tokens(user_id);

COMMIT;

-- =====================================================================
--  VERIFICACION - correr despues de aplicar:
-- =====================================================================
-- \d push_tokens

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS push_tokens;
-- COMMIT;
