-- =====================================================================
--  MIGRACION  -  foto de perfil del usuario, guardada EN LA BASE
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-08-16
-- ---------------------------------------------------------------------
--  POR QUE:
--    El cliente pidio poder cambiar el icono fijo de usuario por una
--    foto propia. A diferencia de las imagenes de productos/ofertas
--    (que van a Cloudinary y son publicas por naturaleza: cualquiera
--    que vea el menu las ve), la foto de perfil es un dato PERSONAL:
--    solo la puede ver el propio usuario autenticado. Una URL de
--    Cloudinary es publica para quien la adivine o la reenvie, asi que
--    no sirve para este caso -> la imagen se guarda en la BD y se sirve
--    por un endpoint que exige el token del dueno.
--
--  POR QUE TABLA APARTE Y NO UNA COLUMNA EN users:
--    Eloquent hace "SELECT *" por defecto. Si el bytea viviera en
--    users, cada User::find(), cada login, cada listado de clientes del
--    admin y cada pedido que carga su cliente arrastraria la imagen
--    completa aunque nadie la pida. Con la tabla aparte el blob solo se
--    lee cuando se pide la foto explicitamente.
--
--  QUE HACE:
--    Crea usuario_fotos (1 fila por usuario como maximo).
--      - user_id es PK **y** FK: la PK garantiza "una sola foto por
--        usuario" sin necesidad de un UNIQUE extra.
--      - ON DELETE CASCADE: si se borra el usuario, su foto se va con
--        el. Es dato personal, no debe sobrevivir a la cuenta.
--      - contenido bytea: la imagen ya normalizada por el backend
--        (recorte cuadrado + resize a 512px + re-encode JPEG/PNG con
--        GD). No se guarda el archivo crudo que sube el telefono.
--      - tamano_bytes: para auditar/limitar sin tener que leer el blob.
--
--    Tablas: 35 -> 36. APROBADO explicitamente por el usuario el
--    2026-08-16 antes de crear el archivo.
--
--  COMO SE CORRE (NO usar php artisan migrate, ver COMO-CORRER.md):
--    psql -U postgres -d rooster_pizza -f migracion_2026-08-16_usuario_fotos.sql
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS usuario_fotos (
    user_id      bigint       NOT NULL,
    contenido    bytea        NOT NULL,
    mime         varchar(40)  NOT NULL,
    tamano_bytes integer      NOT NULL,
    created_at   timestamp    NULL,
    updated_at   timestamp    NULL,

    CONSTRAINT usuario_fotos_pkey PRIMARY KEY (user_id),

    CONSTRAINT fk_usuario_fotos_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,

    -- Solo formatos que el backend sabe re-encodear con GD.
    CONSTRAINT chk_usuario_fotos_mime
        CHECK (mime IN ('image/jpeg', 'image/png', 'image/webp')),

    -- Tope duro de 3 MB: el backend ya redimensiona a 512px (queda muy
    -- por debajo), esto es la red de seguridad a nivel de esquema.
    CONSTRAINT chk_usuario_fotos_tamano
        CHECK (tamano_bytes > 0 AND tamano_bytes <= 3145728)
);

COMMENT ON TABLE usuario_fotos IS
    'Foto de perfil del usuario. Dato PRIVADO: solo se sirve al propio dueno autenticado (GET /api/cuenta/foto). No va a Cloudinary por eso.';

COMMENT ON COLUMN usuario_fotos.contenido IS
    'Imagen ya normalizada por el backend (cuadrada, max 512px, re-encodeada con GD). No es el archivo crudo subido.';

COMMIT;
