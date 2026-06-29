-- =====================================================================
--  ROOSTER PIZZA & GRILL  -  SCRIPT DDL (PostgreSQL)
--  Proyecto Integrador III  -  Entregable #3
--  Reconstruido fielmente a partir del ERD (modelo fisico) de pgAdmin.
--  21 tablas | 28 relaciones 1-M (FKs via ALTER TABLE al final).
-- =====================================================================
--
--  ALCANCE DE LA RECONSTRUCCION
--  ----------------------------------------------------------------
--  Reproducido EXACTO desde el ERD:
--     - Nombres de tablas y columnas
--     - Tipos de dato (incluye precision: numeric(10,2), varchar(n)...)
--     - Llaves primarias (bigserial)
--     - Llaves foraneas (28) y restricciones UNIQUE (icono azul del ERD)
--
--  INFERIDO con convenciones estandar de Laravel (NO visible en un ERD,
--  verificar contra las migraciones originales del equipo):
--     - NOT NULL / nulabilidad de cada columna
--     - Valores por defecto (DEFAULT) en booleanos y contadores
--     - Acciones ON DELETE de las llaves foraneas
--
--  Las columnas created_at / updated_at / deleted_at se dejan NULL
--  porque Laravel (timestamps() y softDeletes()) las crea anulables.
-- =====================================================================


-- =====================================================================
--  CATALOGO BASE
-- =====================================================================

CREATE TABLE roles (
    id          bigserial PRIMARY KEY,
    nombre      varchar(30)  NOT NULL UNIQUE,
    descripcion varchar(120),
    created_at  timestamp,
    updated_at  timestamp
);

CREATE TABLE sucursales (
    id         bigserial PRIMARY KEY,
    nombre     varchar(120) NOT NULL,
    direccion  varchar(200) NOT NULL,
    telefono   varchar(20),
    latitud    numeric(9,6),
    longitud   numeric(9,6),
    activa     boolean      NOT NULL DEFAULT true,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE configuraciones (
    id          bigserial PRIMARY KEY,
    clave       varchar(80)  NOT NULL UNIQUE,
    valor       text,
    descripcion varchar(200),
    created_at  timestamp,
    updated_at  timestamp
);

CREATE TABLE faqs (
    id         bigserial PRIMARY KEY,
    pregunta   varchar(200) NOT NULL,
    respuesta  text         NOT NULL,
    orden      integer      NOT NULL DEFAULT 0,
    activa     boolean      NOT NULL DEFAULT true,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE categorias (
    id          bigserial PRIMARY KEY,
    nombre      varchar(60)  NOT NULL,
    descripcion varchar(150),
    orden       integer      NOT NULL DEFAULT 0,
    activa      boolean      NOT NULL DEFAULT true,
    created_at  timestamp,
    updated_at  timestamp
);


-- =====================================================================
--  USUARIOS
-- =====================================================================

CREATE TABLE users (
    id             bigserial PRIMARY KEY,
    role_id        bigint       NOT NULL,
    sucursal_id    bigint,
    nombre         varchar(120) NOT NULL,
    email          varchar(150) NOT NULL UNIQUE,
    password       varchar(255) NOT NULL,
    telefono       varchar(20),
    puntos_balance integer      NOT NULL DEFAULT 0,
    activo         boolean      NOT NULL DEFAULT true,
    created_at     timestamp,
    updated_at     timestamp,
    deleted_at     timestamp
);

-- AJUSTE 2026-06-28 (aprobado): alineada a Laravel Sanctum (tabla polimorfica).
-- Antes usaba user_id (FK a users); Sanctum requiere tokenable_type + tokenable_id.
CREATE TABLE personal_access_tokens (
    id             bigserial PRIMARY KEY,
    tokenable_type varchar(255) NOT NULL,
    tokenable_id   bigint       NOT NULL,
    name           varchar(100) NOT NULL,
    token          varchar(64)  NOT NULL UNIQUE,
    abilities      text,
    last_used_at   timestamp,
    expires_at     timestamp,
    created_at     timestamp,
    updated_at     timestamp
);
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
    ON personal_access_tokens (tokenable_type, tokenable_id);


-- =====================================================================
--  PRODUCTOS Y EXTRAS
-- =====================================================================

CREATE TABLE productos (
    id           bigserial PRIMARY KEY,
    categoria_id bigint        NOT NULL,
    nombre       varchar(120)  NOT NULL,
    descripcion  text,
    precio_base  numeric(10,2) NOT NULL,
    imagen_url   varchar(255),
    disponible   boolean       NOT NULL DEFAULT true,
    destacado    boolean       NOT NULL DEFAULT false,
    created_at   timestamp,
    updated_at   timestamp,
    deleted_at   timestamp
);

CREATE TABLE extras (
    id           bigserial PRIMARY KEY,
    categoria_id bigint        NOT NULL,
    nombre       varchar(80)   NOT NULL,
    precio       numeric(10,2) NOT NULL,
    disponible   boolean       NOT NULL DEFAULT true,
    created_at   timestamp,
    updated_at   timestamp
);


-- =====================================================================
--  OFERTAS
-- =====================================================================

CREATE TABLE ofertas (
    id             bigserial PRIMARY KEY,
    nombre         varchar(120)  NOT NULL,
    descripcion    text,
    tipo_descuento varchar(20)   NOT NULL,
    valor          numeric(10,2) NOT NULL,
    fecha_inicio   date,
    fecha_fin      date,
    activa         boolean       NOT NULL DEFAULT true,
    created_at     timestamp,
    updated_at     timestamp
);

CREATE TABLE oferta_producto (
    id          bigserial PRIMARY KEY,
    oferta_id   bigint NOT NULL,
    producto_id bigint NOT NULL
);


-- =====================================================================
--  CUPONES
-- =====================================================================

CREATE TABLE cupones (
    id            bigserial PRIMARY KEY,
    codigo        varchar(40)   NOT NULL UNIQUE,
    tipo          varchar(20)   NOT NULL,
    valor         numeric(10,2) NOT NULL,
    monto_minimo  numeric(10,2),
    fecha_inicio  date,
    fecha_fin     date,
    usos_max      integer,
    usos_actuales integer       NOT NULL DEFAULT 0,
    activo        boolean       NOT NULL DEFAULT true,
    created_at    timestamp,
    updated_at    timestamp
);


-- =====================================================================
--  METODOS DE PAGO
-- =====================================================================

CREATE TABLE metodos_pago (
    id             bigserial PRIMARY KEY,
    user_id        bigint      NOT NULL,
    tipo           varchar(20) NOT NULL,
    alias          varchar(60),
    ultimos4       char(4),
    token          varchar(255),
    predeterminado boolean     NOT NULL DEFAULT false,
    created_at     timestamp,
    updated_at     timestamp,
    deleted_at     timestamp
);


-- =====================================================================
--  PEDIDOS
-- =====================================================================

CREATE TABLE pedidos (
    id             bigserial PRIMARY KEY,
    cliente_id     bigint        NOT NULL,
    sucursal_id    bigint        NOT NULL,
    cupon_id       bigint,
    modalidad      varchar(20)   NOT NULL,
    estado         varchar(20)   NOT NULL,
    subtotal       numeric(10,2) NOT NULL,
    descuento      numeric(10,2) NOT NULL DEFAULT 0,
    total          numeric(10,2) NOT NULL,
    puntos_ganados integer       NOT NULL DEFAULT 0,
    notas          varchar(300),
    created_at     timestamp,
    updated_at     timestamp
);

CREATE TABLE detalle_pedido (
    id              bigserial PRIMARY KEY,
    pedido_id       bigint        NOT NULL,
    producto_id     bigint        NOT NULL,
    cantidad        integer       NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal        numeric(10,2) NOT NULL,
    notas           varchar(200)
);

CREATE TABLE detalle_pedido_extras (
    id                bigserial PRIMARY KEY,
    detalle_pedido_id bigint        NOT NULL,
    extra_id          bigint        NOT NULL,
    precio            numeric(10,2) NOT NULL
);

CREATE TABLE cupon_uso (
    id        bigserial PRIMARY KEY,
    cupon_id  bigint NOT NULL,
    user_id   bigint NOT NULL,
    pedido_id bigint NOT NULL,
    fecha     timestamp
);


-- =====================================================================
--  PAGOS E HISTORIAL
-- =====================================================================

CREATE TABLE pagos (
    id             bigserial PRIMARY KEY,
    pedido_id      bigint        NOT NULL,
    metodo_pago_id bigint        NOT NULL,
    monto          numeric(10,2) NOT NULL,
    estado         varchar(20)   NOT NULL,
    referencia     varchar(100),
    fecha_pago     timestamp,
    created_at     timestamp,
    updated_at     timestamp
);

CREATE TABLE pedido_historial_estado (
    id           bigserial PRIMARY KEY,
    pedido_id    bigint      NOT NULL,
    estado       varchar(20) NOT NULL,
    comentario   varchar(200),
    cambiado_por bigint,
    creado_en    timestamp
);


-- =====================================================================
--  RESENAS Y PUNTOS
-- =====================================================================

CREATE TABLE resenas (
    id              bigserial PRIMARY KEY,
    user_id         bigint      NOT NULL,
    producto_id     bigint      NOT NULL,
    pedido_id       bigint      NOT NULL,
    calificacion    smallint    NOT NULL,
    comentario      text,
    estado          varchar(20) NOT NULL,
    respuesta_admin text,
    respondido_por  bigint,
    created_at      timestamp,
    updated_at      timestamp
);

CREATE TABLE puntos_movimientos (
    id          bigserial PRIMARY KEY,
    user_id     bigint       NOT NULL,
    pedido_id   bigint,
    tipo        varchar(20)  NOT NULL,
    puntos      integer      NOT NULL,
    descripcion varchar(150),
    creado_en   timestamp
);


-- =====================================================================
--  LLAVES FORANEAS  -  28 relaciones 1-M
--  (Las acciones ON DELETE son convencion Laravel; verificar.)
-- =====================================================================

ALTER TABLE users
    ADD CONSTRAINT fk_users_role     FOREIGN KEY (role_id)     REFERENCES roles(id),
    ADD CONSTRAINT fk_users_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL;

-- personal_access_tokens: SIN FK (Sanctum usa relacion polimorfica tokenable_*).
-- Ajuste aprobado 2026-06-28. Total de FK pasa de 28 a 27.

ALTER TABLE productos
    ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id);

ALTER TABLE extras
    ADD CONSTRAINT fk_extras_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id);

ALTER TABLE oferta_producto
    ADD CONSTRAINT fk_op_oferta   FOREIGN KEY (oferta_id)   REFERENCES ofertas(id)   ON DELETE CASCADE,
    ADD CONSTRAINT fk_op_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;

ALTER TABLE metodos_pago
    ADD CONSTRAINT fk_mp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE pedidos
    ADD CONSTRAINT fk_pedidos_cliente  FOREIGN KEY (cliente_id)  REFERENCES users(id),
    ADD CONSTRAINT fk_pedidos_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    ADD CONSTRAINT fk_pedidos_cupon    FOREIGN KEY (cupon_id)    REFERENCES cupones(id) ON DELETE SET NULL;

ALTER TABLE detalle_pedido
    ADD CONSTRAINT fk_dp_pedido   FOREIGN KEY (pedido_id)   REFERENCES pedidos(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_dp_producto FOREIGN KEY (producto_id) REFERENCES productos(id);

ALTER TABLE detalle_pedido_extras
    ADD CONSTRAINT fk_dpe_detalle FOREIGN KEY (detalle_pedido_id) REFERENCES detalle_pedido(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_dpe_extra   FOREIGN KEY (extra_id)          REFERENCES extras(id);

ALTER TABLE cupon_uso
    ADD CONSTRAINT fk_cu_cupon  FOREIGN KEY (cupon_id)  REFERENCES cupones(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_cu_user   FOREIGN KEY (user_id)   REFERENCES users(id),
    ADD CONSTRAINT fk_cu_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE;

ALTER TABLE pagos
    ADD CONSTRAINT fk_pagos_pedido FOREIGN KEY (pedido_id)      REFERENCES pedidos(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_pagos_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id);

ALTER TABLE pedido_historial_estado
    ADD CONSTRAINT fk_phe_pedido FOREIGN KEY (pedido_id)    REFERENCES pedidos(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_phe_user   FOREIGN KEY (cambiado_por) REFERENCES users(id)   ON DELETE SET NULL;

ALTER TABLE resenas
    ADD CONSTRAINT fk_resenas_user     FOREIGN KEY (user_id)        REFERENCES users(id),
    ADD CONSTRAINT fk_resenas_producto FOREIGN KEY (producto_id)    REFERENCES productos(id),
    ADD CONSTRAINT fk_resenas_pedido   FOREIGN KEY (pedido_id)      REFERENCES pedidos(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_resenas_resp     FOREIGN KEY (respondido_por) REFERENCES users(id)   ON DELETE SET NULL;

ALTER TABLE puntos_movimientos
    ADD CONSTRAINT fk_pm_user   FOREIGN KEY (user_id)   REFERENCES users(id),
    ADD CONSTRAINT fk_pm_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL;

-- =====================================================================
--  FIN DEL SCRIPT  -  21 tablas, 28 llaves foraneas.
-- =====================================================================
