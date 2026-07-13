--
-- PostgreSQL database dump
--

\restrict 5LWvt20jLIHppcCvTWRcFxeTeTSvV5R7wh6GaA2hVMh910QjrCyyNnr06iCxzI0

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.usuario_modulo DROP CONSTRAINT IF EXISTS fk_usuario_modulo_user;
ALTER TABLE IF EXISTS ONLY public.usuario_modulo DROP CONSTRAINT IF EXISTS fk_usuario_modulo_modulo;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS fk_users_sucursal;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS fk_users_role;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS fk_users_instancia;
ALTER TABLE IF EXISTS ONLY public.sucursales DROP CONSTRAINT IF EXISTS fk_sucursales_instancia;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS fk_resenas_user;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS fk_resenas_resp;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS fk_resenas_producto;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS fk_resenas_pedido;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS fk_resenas_instancia;
ALTER TABLE IF EXISTS ONLY public.puntos_movimientos DROP CONSTRAINT IF EXISTS fk_puntos_movimientos_instancia;
ALTER TABLE IF EXISTS ONLY public.productos DROP CONSTRAINT IF EXISTS fk_productos_instancia;
ALTER TABLE IF EXISTS ONLY public.productos DROP CONSTRAINT IF EXISTS fk_productos_categoria;
ALTER TABLE IF EXISTS ONLY public.puntos_movimientos DROP CONSTRAINT IF EXISTS fk_pm_user;
ALTER TABLE IF EXISTS ONLY public.puntos_movimientos DROP CONSTRAINT IF EXISTS fk_pm_pedido;
ALTER TABLE IF EXISTS ONLY public.pedido_historial_estado DROP CONSTRAINT IF EXISTS fk_phe_user;
ALTER TABLE IF EXISTS ONLY public.pedido_historial_estado DROP CONSTRAINT IF EXISTS fk_phe_pedido;
ALTER TABLE IF EXISTS ONLY public.pedidos DROP CONSTRAINT IF EXISTS fk_pedidos_sucursal;
ALTER TABLE IF EXISTS ONLY public.pedidos DROP CONSTRAINT IF EXISTS fk_pedidos_instancia;
ALTER TABLE IF EXISTS ONLY public.pedidos DROP CONSTRAINT IF EXISTS fk_pedidos_cupon;
ALTER TABLE IF EXISTS ONLY public.pedidos DROP CONSTRAINT IF EXISTS fk_pedidos_cliente;
ALTER TABLE IF EXISTS ONLY public.pagos DROP CONSTRAINT IF EXISTS fk_pagos_pedido;
ALTER TABLE IF EXISTS ONLY public.pagos DROP CONSTRAINT IF EXISTS fk_pagos_metodo;
ALTER TABLE IF EXISTS ONLY public.oferta_producto DROP CONSTRAINT IF EXISTS fk_op_producto;
ALTER TABLE IF EXISTS ONLY public.oferta_producto DROP CONSTRAINT IF EXISTS fk_op_oferta;
ALTER TABLE IF EXISTS ONLY public.ofertas DROP CONSTRAINT IF EXISTS fk_ofertas_instancia;
ALTER TABLE IF EXISTS ONLY public.metodos_pago DROP CONSTRAINT IF EXISTS fk_mp_user;
ALTER TABLE IF EXISTS ONLY public.metodos_pago DROP CONSTRAINT IF EXISTS fk_metodos_pago_instancia;
ALTER TABLE IF EXISTS ONLY public.insumos DROP CONSTRAINT IF EXISTS fk_insumos_instancia;
ALTER TABLE IF EXISTS ONLY public.instancias DROP CONSTRAINT IF EXISTS fk_instancias_creada_por;
ALTER TABLE IF EXISTS ONLY public.insumo_movimientos DROP CONSTRAINT IF EXISTS fk_im_user;
ALTER TABLE IF EXISTS ONLY public.insumo_movimientos DROP CONSTRAINT IF EXISTS fk_im_insumo;
ALTER TABLE IF EXISTS ONLY public.faqs DROP CONSTRAINT IF EXISTS fk_faqs_instancia;
ALTER TABLE IF EXISTS ONLY public.extras DROP CONSTRAINT IF EXISTS fk_extras_instancia;
ALTER TABLE IF EXISTS ONLY public.extras DROP CONSTRAINT IF EXISTS fk_extras_categoria;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido_extras DROP CONSTRAINT IF EXISTS fk_dpe_extra;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido_extras DROP CONSTRAINT IF EXISTS fk_dpe_detalle;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido DROP CONSTRAINT IF EXISTS fk_dp_producto;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido DROP CONSTRAINT IF EXISTS fk_dp_pedido;
ALTER TABLE IF EXISTS ONLY public.cupones DROP CONSTRAINT IF EXISTS fk_cupones_instancia;
ALTER TABLE IF EXISTS ONLY public.cupon_uso DROP CONSTRAINT IF EXISTS fk_cu_user;
ALTER TABLE IF EXISTS ONLY public.cupon_uso DROP CONSTRAINT IF EXISTS fk_cu_pedido;
ALTER TABLE IF EXISTS ONLY public.cupon_uso DROP CONSTRAINT IF EXISTS fk_cu_cupon;
ALTER TABLE IF EXISTS ONLY public.configuraciones DROP CONSTRAINT IF EXISTS fk_configuraciones_instancia;
ALTER TABLE IF EXISTS ONLY public.categorias DROP CONSTRAINT IF EXISTS fk_categorias_instancia;
DROP INDEX IF EXISTS public.personal_access_tokens_tokenable_type_tokenable_id_index;
DROP INDEX IF EXISTS public.idx_usuario_modulo_user;
DROP INDEX IF EXISTS public.idx_users_instancia;
DROP INDEX IF EXISTS public.idx_sucursales_instancia;
DROP INDEX IF EXISTS public.idx_resenas_instancia;
DROP INDEX IF EXISTS public.idx_puntos_movimientos_instancia;
DROP INDEX IF EXISTS public.idx_prt_email;
DROP INDEX IF EXISTS public.idx_productos_instancia;
DROP INDEX IF EXISTS public.idx_pedidos_instancia;
DROP INDEX IF EXISTS public.idx_ofertas_instancia;
DROP INDEX IF EXISTS public.idx_metodos_pago_instancia;
DROP INDEX IF EXISTS public.idx_insumos_instancia;
DROP INDEX IF EXISTS public.idx_insumo_movimientos_insumo_id;
DROP INDEX IF EXISTS public.idx_faqs_instancia;
DROP INDEX IF EXISTS public.idx_extras_instancia;
DROP INDEX IF EXISTS public.idx_cupones_instancia;
DROP INDEX IF EXISTS public.idx_configuraciones_instancia;
DROP INDEX IF EXISTS public.idx_categorias_instancia;
ALTER TABLE IF EXISTS ONLY public.usuario_modulo DROP CONSTRAINT IF EXISTS usuario_modulo_user_id_modulo_id_key;
ALTER TABLE IF EXISTS ONLY public.usuario_modulo DROP CONSTRAINT IF EXISTS usuario_modulo_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_usuario_unique;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_key;
ALTER TABLE IF EXISTS ONLY public.superadministradores DROP CONSTRAINT IF EXISTS superadministradores_usuario_key;
ALTER TABLE IF EXISTS ONLY public.superadministradores DROP CONSTRAINT IF EXISTS superadministradores_pkey;
ALTER TABLE IF EXISTS ONLY public.superadministradores DROP CONSTRAINT IF EXISTS superadministradores_email_key;
ALTER TABLE IF EXISTS ONLY public.sucursales DROP CONSTRAINT IF EXISTS sucursales_pkey;
ALTER TABLE IF EXISTS ONLY public.roles DROP CONSTRAINT IF EXISTS roles_pkey;
ALTER TABLE IF EXISTS ONLY public.roles DROP CONSTRAINT IF EXISTS roles_nombre_key;
ALTER TABLE IF EXISTS ONLY public.resenas DROP CONSTRAINT IF EXISTS resenas_pkey;
ALTER TABLE IF EXISTS ONLY public.puntos_movimientos DROP CONSTRAINT IF EXISTS puntos_movimientos_pkey;
ALTER TABLE IF EXISTS ONLY public.productos DROP CONSTRAINT IF EXISTS productos_pkey;
ALTER TABLE IF EXISTS ONLY public.personal_access_tokens DROP CONSTRAINT IF EXISTS personal_access_tokens_token_key;
ALTER TABLE IF EXISTS ONLY public.personal_access_tokens DROP CONSTRAINT IF EXISTS personal_access_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.pedidos DROP CONSTRAINT IF EXISTS pedidos_pkey;
ALTER TABLE IF EXISTS ONLY public.pedido_historial_estado DROP CONSTRAINT IF EXISTS pedido_historial_estado_pkey;
ALTER TABLE IF EXISTS ONLY public.password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.pagos DROP CONSTRAINT IF EXISTS pagos_pkey;
ALTER TABLE IF EXISTS ONLY public.ofertas DROP CONSTRAINT IF EXISTS ofertas_pkey;
ALTER TABLE IF EXISTS ONLY public.oferta_producto DROP CONSTRAINT IF EXISTS oferta_producto_pkey;
ALTER TABLE IF EXISTS ONLY public.modulos DROP CONSTRAINT IF EXISTS modulos_pkey;
ALTER TABLE IF EXISTS ONLY public.modulos DROP CONSTRAINT IF EXISTS modulos_clave_key;
ALTER TABLE IF EXISTS ONLY public.metodos_pago DROP CONSTRAINT IF EXISTS metodos_pago_pkey;
ALTER TABLE IF EXISTS ONLY public.insumos DROP CONSTRAINT IF EXISTS insumos_pkey;
ALTER TABLE IF EXISTS ONLY public.insumo_movimientos DROP CONSTRAINT IF EXISTS insumo_movimientos_pkey;
ALTER TABLE IF EXISTS ONLY public.instancias DROP CONSTRAINT IF EXISTS instancias_pkey;
ALTER TABLE IF EXISTS ONLY public.faqs DROP CONSTRAINT IF EXISTS faqs_pkey;
ALTER TABLE IF EXISTS ONLY public.extras DROP CONSTRAINT IF EXISTS extras_pkey;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido DROP CONSTRAINT IF EXISTS detalle_pedido_pkey;
ALTER TABLE IF EXISTS ONLY public.detalle_pedido_extras DROP CONSTRAINT IF EXISTS detalle_pedido_extras_pkey;
ALTER TABLE IF EXISTS ONLY public.cupones DROP CONSTRAINT IF EXISTS cupones_pkey;
ALTER TABLE IF EXISTS ONLY public.cupones DROP CONSTRAINT IF EXISTS cupones_codigo_key;
ALTER TABLE IF EXISTS ONLY public.cupon_uso DROP CONSTRAINT IF EXISTS cupon_uso_pkey;
ALTER TABLE IF EXISTS ONLY public.configuraciones DROP CONSTRAINT IF EXISTS configuraciones_pkey;
ALTER TABLE IF EXISTS ONLY public.configuraciones DROP CONSTRAINT IF EXISTS configuraciones_clave_key;
ALTER TABLE IF EXISTS ONLY public.categorias DROP CONSTRAINT IF EXISTS categorias_pkey;
ALTER TABLE IF EXISTS public.usuario_modulo ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.superadministradores ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.sucursales ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.roles ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.resenas ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.puntos_movimientos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.productos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.personal_access_tokens ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pedidos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pedido_historial_estado ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.password_reset_tokens ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pagos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.ofertas ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.oferta_producto ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.modulos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.metodos_pago ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.insumos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.insumo_movimientos ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.instancias ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.faqs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.extras ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.detalle_pedido_extras ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.detalle_pedido ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cupones ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cupon_uso ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.configuraciones ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.categorias ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.usuario_modulo_id_seq;
DROP TABLE IF EXISTS public.usuario_modulo;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.superadministradores_id_seq;
DROP TABLE IF EXISTS public.superadministradores;
DROP SEQUENCE IF EXISTS public.sucursales_id_seq;
DROP TABLE IF EXISTS public.sucursales;
DROP SEQUENCE IF EXISTS public.roles_id_seq;
DROP TABLE IF EXISTS public.roles;
DROP SEQUENCE IF EXISTS public.resenas_id_seq;
DROP TABLE IF EXISTS public.resenas;
DROP SEQUENCE IF EXISTS public.puntos_movimientos_id_seq;
DROP TABLE IF EXISTS public.puntos_movimientos;
DROP SEQUENCE IF EXISTS public.productos_id_seq;
DROP TABLE IF EXISTS public.productos;
DROP SEQUENCE IF EXISTS public.personal_access_tokens_id_seq;
DROP TABLE IF EXISTS public.personal_access_tokens;
DROP SEQUENCE IF EXISTS public.pedidos_id_seq;
DROP TABLE IF EXISTS public.pedidos;
DROP SEQUENCE IF EXISTS public.pedido_historial_estado_id_seq;
DROP TABLE IF EXISTS public.pedido_historial_estado;
DROP SEQUENCE IF EXISTS public.password_reset_tokens_id_seq;
DROP TABLE IF EXISTS public.password_reset_tokens;
DROP SEQUENCE IF EXISTS public.pagos_id_seq;
DROP TABLE IF EXISTS public.pagos;
DROP SEQUENCE IF EXISTS public.ofertas_id_seq;
DROP TABLE IF EXISTS public.ofertas;
DROP SEQUENCE IF EXISTS public.oferta_producto_id_seq;
DROP TABLE IF EXISTS public.oferta_producto;
DROP SEQUENCE IF EXISTS public.modulos_id_seq;
DROP TABLE IF EXISTS public.modulos;
DROP SEQUENCE IF EXISTS public.metodos_pago_id_seq;
DROP TABLE IF EXISTS public.metodos_pago;
DROP SEQUENCE IF EXISTS public.insumos_id_seq;
DROP TABLE IF EXISTS public.insumos;
DROP SEQUENCE IF EXISTS public.insumo_movimientos_id_seq;
DROP TABLE IF EXISTS public.insumo_movimientos;
DROP SEQUENCE IF EXISTS public.instancias_id_seq;
DROP TABLE IF EXISTS public.instancias;
DROP SEQUENCE IF EXISTS public.faqs_id_seq;
DROP TABLE IF EXISTS public.faqs;
DROP SEQUENCE IF EXISTS public.extras_id_seq;
DROP TABLE IF EXISTS public.extras;
DROP SEQUENCE IF EXISTS public.detalle_pedido_id_seq;
DROP SEQUENCE IF EXISTS public.detalle_pedido_extras_id_seq;
DROP TABLE IF EXISTS public.detalle_pedido_extras;
DROP TABLE IF EXISTS public.detalle_pedido;
DROP SEQUENCE IF EXISTS public.cupones_id_seq;
DROP TABLE IF EXISTS public.cupones;
DROP SEQUENCE IF EXISTS public.cupon_uso_id_seq;
DROP TABLE IF EXISTS public.cupon_uso;
DROP SEQUENCE IF EXISTS public.configuraciones_id_seq;
DROP TABLE IF EXISTS public.configuraciones;
DROP SEQUENCE IF EXISTS public.categorias_id_seq;
DROP TABLE IF EXISTS public.categorias;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: categorias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categorias (
    id bigint NOT NULL,
    nombre character varying(60) NOT NULL,
    descripcion character varying(150),
    orden integer DEFAULT 0 NOT NULL,
    activa boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: categorias_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categorias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categorias_id_seq OWNED BY public.categorias.id;


--
-- Name: configuraciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.configuraciones (
    id bigint NOT NULL,
    clave character varying(80) NOT NULL,
    valor text,
    descripcion character varying(200),
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint
);


--
-- Name: configuraciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.configuraciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: configuraciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.configuraciones_id_seq OWNED BY public.configuraciones.id;


--
-- Name: cupon_uso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cupon_uso (
    id bigint NOT NULL,
    cupon_id bigint NOT NULL,
    user_id bigint NOT NULL,
    pedido_id bigint NOT NULL,
    fecha timestamp without time zone
);


--
-- Name: cupon_uso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cupon_uso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cupon_uso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cupon_uso_id_seq OWNED BY public.cupon_uso.id;


--
-- Name: cupones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cupones (
    id bigint NOT NULL,
    codigo character varying(40) NOT NULL,
    tipo character varying(20) NOT NULL,
    valor numeric(10,2) NOT NULL,
    monto_minimo numeric(10,2),
    fecha_inicio date,
    fecha_fin date,
    usos_max integer,
    usos_actuales integer DEFAULT 0 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: cupones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cupones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cupones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cupones_id_seq OWNED BY public.cupones.id;


--
-- Name: detalle_pedido; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detalle_pedido (
    id bigint NOT NULL,
    pedido_id bigint NOT NULL,
    producto_id bigint NOT NULL,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    notas character varying(200)
);


--
-- Name: detalle_pedido_extras; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detalle_pedido_extras (
    id bigint NOT NULL,
    detalle_pedido_id bigint NOT NULL,
    extra_id bigint NOT NULL,
    precio numeric(10,2) NOT NULL
);


--
-- Name: detalle_pedido_extras_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.detalle_pedido_extras_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: detalle_pedido_extras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.detalle_pedido_extras_id_seq OWNED BY public.detalle_pedido_extras.id;


--
-- Name: detalle_pedido_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.detalle_pedido_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: detalle_pedido_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.detalle_pedido_id_seq OWNED BY public.detalle_pedido.id;


--
-- Name: extras; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.extras (
    id bigint NOT NULL,
    categoria_id bigint NOT NULL,
    nombre character varying(80) NOT NULL,
    precio numeric(10,2) NOT NULL,
    disponible boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: extras_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.extras_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: extras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.extras_id_seq OWNED BY public.extras.id;


--
-- Name: faqs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.faqs (
    id bigint NOT NULL,
    pregunta character varying(200) NOT NULL,
    respuesta text NOT NULL,
    orden integer DEFAULT 0 NOT NULL,
    activa boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: faqs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.faqs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: faqs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.faqs_id_seq OWNED BY public.faqs.id;


--
-- Name: instancias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instancias (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    correo_principal character varying(150),
    estado character varying(20) DEFAULT 'activa'::character varying NOT NULL,
    creada_por bigint,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


--
-- Name: instancias_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instancias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instancias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instancias_id_seq OWNED BY public.instancias.id;


--
-- Name: insumo_movimientos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.insumo_movimientos (
    id bigint NOT NULL,
    insumo_id bigint NOT NULL,
    user_id bigint,
    tipo character varying(20) DEFAULT 'toma_fisica'::character varying NOT NULL,
    cantidad_anterior numeric(10,2) NOT NULL,
    cantidad_nueva numeric(10,2) NOT NULL,
    diferencia numeric(10,2) NOT NULL,
    nota character varying(255),
    created_at timestamp without time zone
);


--
-- Name: insumo_movimientos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.insumo_movimientos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: insumo_movimientos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.insumo_movimientos_id_seq OWNED BY public.insumo_movimientos.id;


--
-- Name: insumos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.insumos (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    unidad_medida character varying(20) NOT NULL,
    cantidad_actual numeric(10,2) DEFAULT 0 NOT NULL,
    stock_minimo numeric(10,2),
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: insumos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.insumos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: insumos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.insumos_id_seq OWNED BY public.insumos.id;


--
-- Name: metodos_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.metodos_pago (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    tipo character varying(20) NOT NULL,
    alias character varying(60),
    ultimos4 character(4),
    token character varying(255),
    predeterminado boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: metodos_pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.metodos_pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: metodos_pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.metodos_pago_id_seq OWNED BY public.metodos_pago.id;


--
-- Name: modulos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modulos (
    id bigint NOT NULL,
    clave character varying(50) NOT NULL,
    nombre character varying(80) NOT NULL,
    orden integer DEFAULT 0 NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: modulos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modulos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modulos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modulos_id_seq OWNED BY public.modulos.id;


--
-- Name: oferta_producto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oferta_producto (
    id bigint NOT NULL,
    oferta_id bigint NOT NULL,
    producto_id bigint NOT NULL
);


--
-- Name: oferta_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oferta_producto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oferta_producto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oferta_producto_id_seq OWNED BY public.oferta_producto.id;


--
-- Name: ofertas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ofertas (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    descripcion text,
    tipo_descuento character varying(20) NOT NULL,
    valor numeric(10,2) NOT NULL,
    fecha_inicio date,
    fecha_fin date,
    activa boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: ofertas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ofertas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ofertas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ofertas_id_seq OWNED BY public.ofertas.id;


--
-- Name: pagos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pagos (
    id bigint NOT NULL,
    pedido_id bigint NOT NULL,
    metodo_pago_id bigint NOT NULL,
    monto numeric(10,2) NOT NULL,
    estado character varying(20) NOT NULL,
    referencia character varying(100),
    fecha_pago timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: pagos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pagos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pagos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pagos_id_seq OWNED BY public.pagos.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    id bigint NOT NULL,
    email character varying(150) NOT NULL,
    token character varying(255) NOT NULL,
    actor_type character varying(30) NOT NULL,
    created_at timestamp without time zone
);


--
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.password_reset_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.password_reset_tokens_id_seq OWNED BY public.password_reset_tokens.id;


--
-- Name: pedido_historial_estado; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pedido_historial_estado (
    id bigint NOT NULL,
    pedido_id bigint NOT NULL,
    estado character varying(20) NOT NULL,
    comentario character varying(200),
    cambiado_por bigint,
    creado_en timestamp without time zone
);


--
-- Name: pedido_historial_estado_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pedido_historial_estado_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pedido_historial_estado_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pedido_historial_estado_id_seq OWNED BY public.pedido_historial_estado.id;


--
-- Name: pedidos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pedidos (
    id bigint NOT NULL,
    cliente_id bigint NOT NULL,
    sucursal_id bigint NOT NULL,
    cupon_id bigint,
    modalidad character varying(20) NOT NULL,
    estado character varying(20) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    descuento numeric(10,2) DEFAULT 0 NOT NULL,
    total numeric(10,2) NOT NULL,
    puntos_ganados integer DEFAULT 0 NOT NULL,
    notas character varying(300),
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: pedidos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pedidos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pedidos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pedidos_id_seq OWNED BY public.pedidos.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp without time zone,
    expires_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: productos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.productos (
    id bigint NOT NULL,
    categoria_id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    descripcion text,
    precio_base numeric(10,2) NOT NULL,
    imagen_url character varying(255),
    disponible boolean DEFAULT true NOT NULL,
    destacado boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: productos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.productos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: productos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.productos_id_seq OWNED BY public.productos.id;


--
-- Name: puntos_movimientos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.puntos_movimientos (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    pedido_id bigint,
    tipo character varying(20) NOT NULL,
    puntos integer NOT NULL,
    descripcion character varying(150),
    creado_en timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: puntos_movimientos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.puntos_movimientos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: puntos_movimientos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.puntos_movimientos_id_seq OWNED BY public.puntos_movimientos.id;


--
-- Name: resenas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.resenas (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    producto_id bigint NOT NULL,
    pedido_id bigint NOT NULL,
    calificacion smallint NOT NULL,
    comentario text,
    estado character varying(20) NOT NULL,
    respuesta_admin text,
    respondido_por bigint,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: resenas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.resenas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: resenas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.resenas_id_seq OWNED BY public.resenas.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    nombre character varying(30) NOT NULL,
    descripcion character varying(120),
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sucursales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sucursales (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    direccion character varying(200) NOT NULL,
    telefono character varying(20),
    latitud numeric(9,6),
    longitud numeric(9,6),
    activa boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL
);


--
-- Name: sucursales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sucursales_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sucursales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sucursales_id_seq OWNED BY public.sucursales.id;


--
-- Name: superadministradores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.superadministradores (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    usuario character varying(60) NOT NULL,
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    ultimo_acceso_en timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone
);


--
-- Name: superadministradores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.superadministradores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: superadministradores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.superadministradores_id_seq OWNED BY public.superadministradores.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    sucursal_id bigint,
    nombre character varying(120) NOT NULL,
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    telefono character varying(20),
    puntos_balance integer DEFAULT 0 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    instancia_id bigint NOT NULL,
    usuario character varying(60),
    password_temporal boolean DEFAULT false NOT NULL,
    cambio_password_obligatorio boolean DEFAULT false NOT NULL,
    password_expira_en date,
    dias_expiracion_password integer,
    ultimo_acceso_en timestamp without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: usuario_modulo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario_modulo (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    modulo_id bigint NOT NULL
);


--
-- Name: usuario_modulo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuario_modulo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuario_modulo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuario_modulo_id_seq OWNED BY public.usuario_modulo.id;


--
-- Name: categorias id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias ALTER COLUMN id SET DEFAULT nextval('public.categorias_id_seq'::regclass);


--
-- Name: configuraciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuraciones ALTER COLUMN id SET DEFAULT nextval('public.configuraciones_id_seq'::regclass);


--
-- Name: cupon_uso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupon_uso ALTER COLUMN id SET DEFAULT nextval('public.cupon_uso_id_seq'::regclass);


--
-- Name: cupones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupones ALTER COLUMN id SET DEFAULT nextval('public.cupones_id_seq'::regclass);


--
-- Name: detalle_pedido id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido ALTER COLUMN id SET DEFAULT nextval('public.detalle_pedido_id_seq'::regclass);


--
-- Name: detalle_pedido_extras id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido_extras ALTER COLUMN id SET DEFAULT nextval('public.detalle_pedido_extras_id_seq'::regclass);


--
-- Name: extras id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.extras ALTER COLUMN id SET DEFAULT nextval('public.extras_id_seq'::regclass);


--
-- Name: faqs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs ALTER COLUMN id SET DEFAULT nextval('public.faqs_id_seq'::regclass);


--
-- Name: instancias id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instancias ALTER COLUMN id SET DEFAULT nextval('public.instancias_id_seq'::regclass);


--
-- Name: insumo_movimientos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumo_movimientos ALTER COLUMN id SET DEFAULT nextval('public.insumo_movimientos_id_seq'::regclass);


--
-- Name: insumos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumos ALTER COLUMN id SET DEFAULT nextval('public.insumos_id_seq'::regclass);


--
-- Name: metodos_pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodos_pago ALTER COLUMN id SET DEFAULT nextval('public.metodos_pago_id_seq'::regclass);


--
-- Name: modulos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modulos ALTER COLUMN id SET DEFAULT nextval('public.modulos_id_seq'::regclass);


--
-- Name: oferta_producto id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oferta_producto ALTER COLUMN id SET DEFAULT nextval('public.oferta_producto_id_seq'::regclass);


--
-- Name: ofertas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas ALTER COLUMN id SET DEFAULT nextval('public.ofertas_id_seq'::regclass);


--
-- Name: pagos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id SET DEFAULT nextval('public.pagos_id_seq'::regclass);


--
-- Name: password_reset_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens ALTER COLUMN id SET DEFAULT nextval('public.password_reset_tokens_id_seq'::regclass);


--
-- Name: pedido_historial_estado id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedido_historial_estado ALTER COLUMN id SET DEFAULT nextval('public.pedido_historial_estado_id_seq'::regclass);


--
-- Name: pedidos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos ALTER COLUMN id SET DEFAULT nextval('public.pedidos_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: productos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos ALTER COLUMN id SET DEFAULT nextval('public.productos_id_seq'::regclass);


--
-- Name: puntos_movimientos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.puntos_movimientos ALTER COLUMN id SET DEFAULT nextval('public.puntos_movimientos_id_seq'::regclass);


--
-- Name: resenas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas ALTER COLUMN id SET DEFAULT nextval('public.resenas_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: sucursales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sucursales ALTER COLUMN id SET DEFAULT nextval('public.sucursales_id_seq'::regclass);


--
-- Name: superadministradores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadministradores ALTER COLUMN id SET DEFAULT nextval('public.superadministradores_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: usuario_modulo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_modulo ALTER COLUMN id SET DEFAULT nextval('public.usuario_modulo_id_seq'::regclass);


--
-- Data for Name: categorias; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.categorias (id, nombre, descripcion, orden, activa, created_at, updated_at, instancia_id) FROM stdin;
3	Menu Fortuna	demo	1	t	2026-07-13 08:22:40.960659	2026-07-13 08:22:40.960659	1
4	Menu Guayabo	demo	1	t	2026-07-13 08:22:40.960659	2026-07-13 08:22:40.960659	3
\.


--
-- Data for Name: configuraciones; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.configuraciones (id, clave, valor, descripcion, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: cupon_uso; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cupon_uso (id, cupon_id, user_id, pedido_id, fecha) FROM stdin;
\.


--
-- Data for Name: cupones; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cupones (id, codigo, tipo, valor, monto_minimo, fecha_inicio, fecha_fin, usos_max, usos_actuales, activo, created_at, updated_at, instancia_id) FROM stdin;
1	FORTUNA10	porcentaje	10.00	\N	\N	\N	\N	0	t	2026-07-13 14:26:06	2026-07-13 14:26:06	1
\.


--
-- Data for Name: detalle_pedido; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.detalle_pedido (id, pedido_id, producto_id, cantidad, precio_unitario, subtotal, notas) FROM stdin;
\.


--
-- Data for Name: detalle_pedido_extras; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.detalle_pedido_extras (id, detalle_pedido_id, extra_id, precio) FROM stdin;
\.


--
-- Data for Name: extras; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.extras (id, categoria_id, nombre, precio, disponible, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: faqs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.faqs (id, pregunta, respuesta, orden, activa, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: instancias; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.instancias (id, nombre, correo_principal, estado, creada_por, created_at, updated_at, deleted_at) FROM stdin;
1	Rooster Pizza & Grill - La Fortuna	contacto@roosterpizza.cr	activa	\N	2026-07-12 20:07:59.493162	2026-07-12 20:07:59.493162	\N
2	Liberia	liberia@rooster.com	activa	1	2026-07-13 01:51:47	2026-07-13 01:51:47	\N
3	Guayabo	guayabo@rooster.com	activa	1	2026-07-13 01:52:37	2026-07-13 02:00:07	\N
\.


--
-- Data for Name: insumo_movimientos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.insumo_movimientos (id, insumo_id, user_id, tipo, cantidad_anterior, cantidad_nueva, diferencia, nota, created_at) FROM stdin;
\.


--
-- Data for Name: insumos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.insumos (id, nombre, unidad_medida, cantidad_actual, stock_minimo, created_at, updated_at, deleted_at, instancia_id) FROM stdin;
1	Carne	kg	50.00	10.00	2026-07-13 14:52:32	2026-07-13 14:52:32	\N	1
2	Queso	kg	30.00	5.00	2026-07-13 14:52:33	2026-07-13 14:52:33	\N	3
\.


--
-- Data for Name: metodos_pago; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.metodos_pago (id, user_id, tipo, alias, ultimos4, token, predeterminado, created_at, updated_at, deleted_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: modulos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.modulos (id, clave, nombre, orden, activo) FROM stdin;
1	dashboard	Dashboard	1	t
2	pedidos	Pedidos	2	t
3	menu	Menú	3	t
4	ofertas	Ofertas y cupones	4	t
5	usuarios	Usuarios y roles	5	t
6	analiticas	Analíticas	6	t
7	notificaciones	Notificaciones	7	t
8	resenas	Reseñas	8	t
9	configuracion	Configuración	9	t
\.


--
-- Data for Name: oferta_producto; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.oferta_producto (id, oferta_id, producto_id) FROM stdin;
\.


--
-- Data for Name: ofertas; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.ofertas (id, nombre, descripcion, tipo_descuento, valor, fecha_inicio, fecha_fin, activa, created_at, updated_at, instancia_id) FROM stdin;
2	2x1 Fortuna	\N	porcentaje	50.00	\N	\N	t	2026-07-13 14:26:05	2026-07-13 14:26:05	1
\.


--
-- Data for Name: pagos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pagos (id, pedido_id, metodo_pago_id, monto, estado, referencia, fecha_pago, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (id, email, token, actor_type, created_at) FROM stdin;
4	jesusvegas7891@gmail.com	$2y$12$f/wArnB5gzHAUl7RB.Ny0Ool/luDEOT5VBAn.VHQgJuG42BMi27ti	User	2026-07-13 08:18:01
\.


--
-- Data for Name: pedido_historial_estado; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pedido_historial_estado (id, pedido_id, estado, comentario, cambiado_por, creado_en) FROM stdin;
\.


--
-- Data for Name: pedidos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pedidos (id, cliente_id, sucursal_id, cupon_id, modalidad, estado, subtotal, descuento, total, puntos_ganados, notas, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
1	App\\Models\\User	1	auth	dece224820b0165b41d4b9952bde073a64051cc30a9ae3dae0aef6ebad398a7d	["*"]	\N	\N	2026-07-12 18:30:35	2026-07-12 18:30:35
29	App\\Models\\User	1	auth	babf5ac1fa8384ffc081cffddb0a3c06e477b7b4896c1b39e848bbdcba25fc71	["*"]	2026-07-13 02:20:53	\N	2026-07-13 02:20:53	2026-07-13 02:20:53
3	App\\Models\\User	1	auth	5be47888a45d19da1f76a867906d76a09de3f970e4b001f0812ba462eb4e0a81	["*"]	\N	\N	2026-07-12 20:08:21	2026-07-12 20:08:21
50	App\\Models\\User	1	auth	88c3b17689a9871cf11cd84a6cf1021d79f06d137f651bb179c5e1e11186246a	["*"]	2026-07-13 14:52:33	\N	2026-07-13 14:52:30	2026-07-13 14:52:33
5	App\\Models\\User	1	auth	4f6aa407a1392b8d3711cfc02323254d206b34d339b44375eaa113f89f4dbad2	["*"]	2026-07-12 20:34:13	\N	2026-07-12 20:34:13	2026-07-12 20:34:13
4	App\\Models\\SuperAdministrador	1	superadmin	76f31b1923ee0457e1d411298ec0b141232b5fe8ef6302ecc7749250dd672b82	["*"]	2026-07-12 20:34:13	\N	2026-07-12 20:34:12	2026-07-12 20:34:13
6	App\\Models\\User	1	auth	1d5f8a9601a109b87ae98d101f2e218dc3d0abceb363d8c1fdbe66344c54f38e	["*"]	\N	\N	2026-07-12 20:34:14	2026-07-12 20:34:14
30	App\\Models\\User	5	auth	a879075b1a66da8e6e655e732c64705e31fc25a11f7240e6702bbcbe71d731db	["*"]	2026-07-13 02:20:55	\N	2026-07-13 02:20:54	2026-07-13 02:20:55
7	App\\Models\\SuperAdministrador	1	superadmin	a00748f8e766e1ed3d9d723cc229ffeb553c0f042a6144057b47959328e28f03	["*"]	2026-07-12 20:34:36	\N	2026-07-12 20:34:34	2026-07-12 20:34:36
17	App\\Models\\User	1	auth	9f77327c7fb7f6f611e97bc4fd51db0de4076a951f02d4a60624cd4cf926679b	["*"]	2026-07-13 01:16:36	\N	2026-07-13 01:16:33	2026-07-13 01:16:36
9	App\\Models\\SuperAdministrador	1	superadmin	32f41b1cd7e81b7933941d0a61e85f4e59b871330c2d29583a2c6f307cd078ad	["*"]	\N	\N	2026-07-12 20:56:43	2026-07-12 20:56:43
49	App\\Models\\User	1	auth	016801dae76261bb3c0a6737205d337a3459e119e21356f19c20af2a5297634f	["*"]	2026-07-13 14:26:06	\N	2026-07-13 14:26:04	2026-07-13 14:26:06
31	App\\Models\\User	5	auth	c41ae46cc02fc6be518f3634bbfc809cc92ef74bb5a986b337d5a49ad6cb3977	["*"]	2026-07-13 02:21:53	\N	2026-07-13 02:21:52	2026-07-13 02:21:53
20	App\\Models\\User	1	auth	88d28e33623140ba76a15e403f84bafa1dcfeadc0560c4de490afbe8f505b6fa	["*"]	2026-07-13 02:03:36	\N	2026-07-13 01:24:30	2026-07-13 02:03:36
11	App\\Models\\SuperAdministrador	1	superadmin	8183396b06b4f45a673c5a443781c93c21cc5866eaa9bb5881407056a55224f4	["*"]	\N	\N	2026-07-13 00:46:10	2026-07-13 00:46:10
12	App\\Models\\User	1	auth	6be6ce9a3531e4df7fc60a38d7f82c451e0162d4398effc138c0160f95781b5a	["*"]	\N	\N	2026-07-13 00:46:11	2026-07-13 00:46:11
10	App\\Models\\SuperAdministrador	1	superadmin	680cda55b17f0d0286a557666e1483cfd68bbe412be0d602d54fe27ef6eb1268	["*"]	2026-07-13 00:46:57	\N	2026-07-12 20:58:52	2026-07-13 00:46:57
21	App\\Models\\SuperAdministrador	1	superadmin	8003c75b0a50a8697bd999a2ffbc382418a5fe64a3566afeb52f5ed145b11e51	["*"]	2026-07-13 01:51:47	\N	2026-07-13 01:51:46	2026-07-13 01:51:47
15	App\\Models\\User	1	auth	6ac4b1df27adac1b09dffda1d1ebe9e80da073e7bc5aa28824133e5c067aa714	["*"]	2026-07-13 01:10:56	\N	2026-07-13 01:10:55	2026-07-13 01:10:56
16	App\\Models\\User	1	auth	c91204fad6b28d8aa22af19d0748bb9159754b53e049bc2b8bb4fc144a83cd92	["*"]	2026-07-13 01:11:13	\N	2026-07-13 01:11:13	2026-07-13 01:11:13
22	App\\Models\\SuperAdministrador	1	superadmin	6dc79e6691f1ae9dc37d9455e086c6506ab8cea0198aa641534e645f9e04750f	["*"]	2026-07-13 01:52:38	\N	2026-07-13 01:52:36	2026-07-13 01:52:38
35	App\\Models\\SuperAdministrador	1	superadmin	134a4ff52116410d6af7a62dcbaa516b1f7ad7169682e18f46dd7c31b18f1016	["*"]	2026-07-13 02:51:02	\N	2026-07-13 02:50:59	2026-07-13 02:51:02
24	App\\Models\\User	5	auth	65386d0c6e0fb12bb007bb4fb31bbbb950680ea0e32f9f1f48a0584ca0208c41	["*"]	2026-07-13 01:53:05	\N	2026-07-13 01:53:05	2026-07-13 01:53:05
23	App\\Models\\SuperAdministrador	1	superadmin	0ce59f7179279ef6e3d19a5e1a0c206365ef06b5dd7bb3070c0a3b5d4e28c969	["*"]	2026-07-13 01:53:05	\N	2026-07-13 01:53:04	2026-07-13 01:53:05
33	App\\Models\\User	1	auth	5042a947292ad7af2c8d04cf5617db4f60956fd7e10883d6798c6967b786d737	["*"]	2026-07-13 02:27:03	\N	2026-07-13 02:26:59	2026-07-13 02:27:03
25	App\\Models\\SuperAdministrador	1	superadmin	4cc4eaac5282e54f433961ea5ad01f354ae0a4238d02b2f785d8a7338f98984b	["*"]	2026-07-13 01:53:52	\N	2026-07-13 01:53:51	2026-07-13 01:53:52
39	App\\Models\\User	8	auth	dff0248ce9e96e40cdb40073a19a60a7e542e9a20792e62cf0ee86c4c9470c7e	["*"]	2026-07-13 08:05:57	\N	2026-07-13 08:05:53	2026-07-13 08:05:57
36	App\\Models\\User	6	auth	1ec01b6e5e53b8c9ce559068c55a4fdf1e1f335baeb8c6d3b15b6155a553a27f	["*"]	\N	\N	2026-07-13 07:43:38	2026-07-13 07:43:38
40	App\\Models\\User	8	auth	229e3b45f12f9c7a5a73cbe402c492d5e0fdca7d9f5b0f343360fb775aec9891	["*"]	2026-07-13 08:06:01	\N	2026-07-13 08:06:00	2026-07-13 08:06:01
34	App\\Models\\SuperAdministrador	1	superadmin	5d8086a4f2998e133ea1403dc8b3d6c90030948e2d60477fdd5be5a0df8d01d4	["*"]	2026-07-13 07:48:03	\N	2026-07-13 02:30:44	2026-07-13 07:48:03
46	App\\Models\\User	1	auth	14cdfb9858d1e50dd9aef9b1bda018050da8d711edd0289a09c908388b3df6b6	["*"]	2026-07-13 08:29:36	\N	2026-07-13 08:29:34	2026-07-13 08:29:36
47	App\\Models\\User	5	auth	07cafe01a94c4df32fb387672849dd67c2dc8cbb6247f61bb3a0b86c450106fb	["*"]	2026-07-13 08:29:37	\N	2026-07-13 08:29:35	2026-07-13 08:29:37
43	App\\Models\\User	1	auth	8427af64d35df17b3015121fd59426745b3777dfe90e20af7172eb6930de7ba3	["*"]	2026-07-13 08:23:08	\N	2026-07-13 08:23:03	2026-07-13 08:23:08
44	App\\Models\\User	5	auth	217165391daa01b813b812f00009c0b5a8b680c0b2de67db475bd907f1da408f	["*"]	2026-07-13 08:23:09	\N	2026-07-13 08:23:06	2026-07-13 08:23:09
48	App\\Models\\User	1	auth	276e46ce156b009df5a4904e8541c521f04a71178cf9da0bfd95839e151d4823	["*"]	2026-07-13 09:02:12	\N	2026-07-13 09:02:11	2026-07-13 09:02:12
51	App\\Models\\User	5	auth	cf8cdc7f34b0822c348c3b11c258c22e8f98559f24e081ef0977aef1f91f6a60	["*"]	2026-07-13 14:52:34	\N	2026-07-13 14:52:31	2026-07-13 14:52:34
52	App\\Models\\User	1	auth	1bcc7e461705e11db246d6f3a41e910bc8348351b798ce8201592c20c7466861	["*"]	2026-07-13 14:55:11	\N	2026-07-13 14:55:08	2026-07-13 14:55:11
\.


--
-- Data for Name: productos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.productos (id, categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, created_at, updated_at, deleted_at, instancia_id) FROM stdin;
4	3	Pizza Fortuna	\N	5500.00	\N	t	f	2026-07-13 08:23:04	2026-07-13 08:23:04	\N	1
5	3	Pasta Fortuna	\N	4800.00	\N	t	f	2026-07-13 08:23:05	2026-07-13 08:23:05	\N	1
6	4	Pizza Guayabo	\N	6000.00	\N	t	f	2026-07-13 08:23:07	2026-07-13 08:23:07	\N	3
7	4	Grill Guayabo	\N	7200.00	\N	t	t	2026-07-13 08:23:08	2026-07-13 08:32:46	\N	3
\.


--
-- Data for Name: puntos_movimientos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.puntos_movimientos (id, user_id, pedido_id, tipo, puntos, descripcion, creado_en, instancia_id) FROM stdin;
\.


--
-- Data for Name: resenas; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.resenas (id, user_id, producto_id, pedido_id, calificacion, comentario, estado, respuesta_admin, respondido_por, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.roles (id, nombre, descripcion, created_at, updated_at) FROM stdin;
1	super_admin	Acceso total: todas las sucursales y configuracion global.	2026-07-12 18:27:28	2026-07-12 18:27:28
2	admin_sede	Administra unicamente su sucursal asignada.	2026-07-12 18:27:28	2026-07-12 18:27:28
3	cliente	Cliente final: sus pedidos, cupones y perfil.	2026-07-12 18:27:28	2026-07-12 18:27:28
\.


--
-- Data for Name: sucursales; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sucursales (id, nombre, direccion, telefono, latitud, longitud, activa, created_at, updated_at, instancia_id) FROM stdin;
\.


--
-- Data for Name: superadministradores; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.superadministradores (id, nombre, usuario, email, password, activo, ultimo_acceso_en, created_at, updated_at, deleted_at) FROM stdin;
3	Bryan Vega	brvega	bryan@rooster.com	$2y$12$atahu6ymPp3.subAgOqrXe29XIEz7zu3LjCQM7CZce/5Mew3vHt9C	t	\N	2026-07-13 00:49:36	2026-07-13 00:49:36	\N
1	Super Rooster	super	super@rooster.com	$2y$12$32Onr2fIyQ0pWge0aI6BQecPa0GYixBJOzTUsWSspsSqsQR7ntwKq	t	2026-07-13 02:50:59	2026-07-12 20:33:09	2026-07-13 02:50:59	\N
4	Temp Editado	temptest	temp@rooster.com	$2y$12$y3KwcrGzgux17dR1.y9VAu.ISkFMDfVr22eHFvsUn4ktvR2m0ps7C	f	\N	2026-07-13 02:51:00	2026-07-13 02:51:02	2026-07-13 02:51:02
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, role_id, sucursal_id, nombre, email, password, telefono, puntos_balance, activo, created_at, updated_at, deleted_at, instancia_id, usuario, password_temporal, cambio_password_obligatorio, password_expira_en, dias_expiracion_password, ultimo_acceso_en) FROM stdin;
1	1	\N	Admin Rooster	admin@rooster.com	$2y$12$zEX.Bqq60BNY67238HIyOu07Acu1fPdCJjZ4PufEtofUT4xdJYfza	\N	0	t	2026-07-12 18:27:28	2026-07-12 18:27:28	\N	1	\N	f	f	\N	\N	\N
2	2	\N	Carlos Cajero	carlos@rooster.com	$2y$12$Fs7rTrSMmgFhHCkB46zz/.HsbnstYsu81nGiChYc02pPHtkrhgdBe	8888-1111	0	t	2026-07-13 01:10:56	2026-07-13 01:10:56	\N	1	ccajero	t	t	\N	\N	\N
3	2	\N	Bryan Vega	bryan@rooster.com	$2y$12$Ho1w1BfuLMJQBR8z3g6V9.tn.26Ro1rBwQ96snppI8q6X3Vf2.cN6	33213212	0	t	2026-07-13 01:19:18	2026-07-13 01:19:18	\N	1	brvega	t	t	\N	\N	\N
4	1	\N	Administrador Liberia	liberia@rooster.com	$2y$12$zOHb.J.wyB8txdhZwf1x2eiLYkJxyp76myZaS21GgoA5nzuRl0Eom	\N	0	t	2026-07-13 01:51:47	2026-07-13 01:51:47	\N	2	liberia_156	t	t	\N	\N	\N
6	3	\N	Jesus Prueba	jesusvegas7891@gmail.com	$2y$12$q0jNLEvIHbfohXZtuQo82eLl3o25koDBo68en7FuEXWbF7XDwr19a	\N	0	t	2026-07-13 07:43:34	2026-07-13 07:53:12	\N	1	jesusp	f	f	\N	\N	\N
7	2	\N	Pepe Mora	anuelmorera@gmail.com	$2y$12$hF6XouQf5b5JSrGwpUtaH.4pEbevf0MKW9qN95ls49N0BEFhCvybe	65732881	0	t	2026-07-13 07:55:24	2026-07-13 07:55:43	2026-07-13 07:55:43	1	pmora	t	t	\N	\N	\N
8	2	\N	Usuario Temporal	tempuser@rooster.com	$2y$12$PnuMvygi7Iy.CYrWrvY56ubghu5mK0n2ql6LKI540fJZWEkTlOUNO	\N	0	t	2026-07-13 08:05:48	2026-07-13 08:12:12	2026-07-13 08:12:12	1	utemp	f	f	\N	\N	\N
9	3	\N	Johan Morales	jmorales@gmail.com	$2y$12$Sv62kRJqDZ4ZDg9vM.U0g..7nwA0NFho0UZaee3F883Bajk1CIpfC	88776677	0	t	2026-07-13 08:13:27	2026-07-13 08:14:56	\N	1	jmorales	f	f	\N	\N	\N
5	1	\N	Administrador Guayabo	guayabo@rooster.com	$2y$12$us6vFLXqV/T/kqsuJrhYzOeBECJwWAQTZ7AYW3/22W/s6U6U1rwAK	\N	0	t	2026-07-13 01:52:37	2026-07-13 08:22:40	\N	3	guayabo_806	f	f	\N	\N	\N
\.


--
-- Data for Name: usuario_modulo; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.usuario_modulo (id, user_id, modulo_id) FROM stdin;
1	2	1
2	2	2
3	2	3
4	3	1
5	3	3
6	3	5
7	7	1
8	7	2
9	7	5
10	7	9
\.


--
-- Name: categorias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categorias_id_seq', 4, true);


--
-- Name: configuraciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.configuraciones_id_seq', 1, false);


--
-- Name: cupon_uso_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cupon_uso_id_seq', 1, false);


--
-- Name: cupones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cupones_id_seq', 1, true);


--
-- Name: detalle_pedido_extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.detalle_pedido_extras_id_seq', 1, false);


--
-- Name: detalle_pedido_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.detalle_pedido_id_seq', 1, false);


--
-- Name: extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.extras_id_seq', 1, false);


--
-- Name: faqs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.faqs_id_seq', 1, false);


--
-- Name: instancias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.instancias_id_seq', 3, true);


--
-- Name: insumo_movimientos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.insumo_movimientos_id_seq', 1, false);


--
-- Name: insumos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.insumos_id_seq', 2, true);


--
-- Name: metodos_pago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.metodos_pago_id_seq', 1, false);


--
-- Name: modulos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.modulos_id_seq', 9, true);


--
-- Name: oferta_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.oferta_producto_id_seq', 1, false);


--
-- Name: ofertas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ofertas_id_seq', 2, true);


--
-- Name: pagos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pagos_id_seq', 1, false);


--
-- Name: password_reset_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.password_reset_tokens_id_seq', 4, true);


--
-- Name: pedido_historial_estado_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pedido_historial_estado_id_seq', 1, false);


--
-- Name: pedidos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pedidos_id_seq', 1, false);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 52, true);


--
-- Name: productos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.productos_id_seq', 7, true);


--
-- Name: puntos_movimientos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.puntos_movimientos_id_seq', 1, false);


--
-- Name: resenas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.resenas_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 3, true);


--
-- Name: sucursales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sucursales_id_seq', 1, false);


--
-- Name: superadministradores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.superadministradores_id_seq', 4, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 9, true);


--
-- Name: usuario_modulo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.usuario_modulo_id_seq', 10, true);


--
-- Name: categorias categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT categorias_pkey PRIMARY KEY (id);


--
-- Name: configuraciones configuraciones_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuraciones
    ADD CONSTRAINT configuraciones_clave_key UNIQUE (clave);


--
-- Name: configuraciones configuraciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuraciones
    ADD CONSTRAINT configuraciones_pkey PRIMARY KEY (id);


--
-- Name: cupon_uso cupon_uso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupon_uso
    ADD CONSTRAINT cupon_uso_pkey PRIMARY KEY (id);


--
-- Name: cupones cupones_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupones
    ADD CONSTRAINT cupones_codigo_key UNIQUE (codigo);


--
-- Name: cupones cupones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupones
    ADD CONSTRAINT cupones_pkey PRIMARY KEY (id);


--
-- Name: detalle_pedido_extras detalle_pedido_extras_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido_extras
    ADD CONSTRAINT detalle_pedido_extras_pkey PRIMARY KEY (id);


--
-- Name: detalle_pedido detalle_pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido
    ADD CONSTRAINT detalle_pedido_pkey PRIMARY KEY (id);


--
-- Name: extras extras_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT extras_pkey PRIMARY KEY (id);


--
-- Name: faqs faqs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs
    ADD CONSTRAINT faqs_pkey PRIMARY KEY (id);


--
-- Name: instancias instancias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instancias
    ADD CONSTRAINT instancias_pkey PRIMARY KEY (id);


--
-- Name: insumo_movimientos insumo_movimientos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumo_movimientos
    ADD CONSTRAINT insumo_movimientos_pkey PRIMARY KEY (id);


--
-- Name: insumos insumos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumos
    ADD CONSTRAINT insumos_pkey PRIMARY KEY (id);


--
-- Name: metodos_pago metodos_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodos_pago
    ADD CONSTRAINT metodos_pago_pkey PRIMARY KEY (id);


--
-- Name: modulos modulos_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modulos
    ADD CONSTRAINT modulos_clave_key UNIQUE (clave);


--
-- Name: modulos modulos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modulos
    ADD CONSTRAINT modulos_pkey PRIMARY KEY (id);


--
-- Name: oferta_producto oferta_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oferta_producto
    ADD CONSTRAINT oferta_producto_pkey PRIMARY KEY (id);


--
-- Name: ofertas ofertas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas
    ADD CONSTRAINT ofertas_pkey PRIMARY KEY (id);


--
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (id);


--
-- Name: pedido_historial_estado pedido_historial_estado_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedido_historial_estado
    ADD CONSTRAINT pedido_historial_estado_pkey PRIMARY KEY (id);


--
-- Name: pedidos pedidos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT pedidos_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_key UNIQUE (token);


--
-- Name: productos productos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT productos_pkey PRIMARY KEY (id);


--
-- Name: puntos_movimientos puntos_movimientos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.puntos_movimientos
    ADD CONSTRAINT puntos_movimientos_pkey PRIMARY KEY (id);


--
-- Name: resenas resenas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT resenas_pkey PRIMARY KEY (id);


--
-- Name: roles roles_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_key UNIQUE (nombre);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sucursales sucursales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT sucursales_pkey PRIMARY KEY (id);


--
-- Name: superadministradores superadministradores_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadministradores
    ADD CONSTRAINT superadministradores_email_key UNIQUE (email);


--
-- Name: superadministradores superadministradores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadministradores
    ADD CONSTRAINT superadministradores_pkey PRIMARY KEY (id);


--
-- Name: superadministradores superadministradores_usuario_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.superadministradores
    ADD CONSTRAINT superadministradores_usuario_key UNIQUE (usuario);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_usuario_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_usuario_unique UNIQUE (usuario);


--
-- Name: usuario_modulo usuario_modulo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_modulo
    ADD CONSTRAINT usuario_modulo_pkey PRIMARY KEY (id);


--
-- Name: usuario_modulo usuario_modulo_user_id_modulo_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_modulo
    ADD CONSTRAINT usuario_modulo_user_id_modulo_id_key UNIQUE (user_id, modulo_id);


--
-- Name: idx_categorias_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_categorias_instancia ON public.categorias USING btree (instancia_id);


--
-- Name: idx_configuraciones_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_configuraciones_instancia ON public.configuraciones USING btree (instancia_id);


--
-- Name: idx_cupones_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cupones_instancia ON public.cupones USING btree (instancia_id);


--
-- Name: idx_extras_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_extras_instancia ON public.extras USING btree (instancia_id);


--
-- Name: idx_faqs_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_faqs_instancia ON public.faqs USING btree (instancia_id);


--
-- Name: idx_insumo_movimientos_insumo_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_insumo_movimientos_insumo_id ON public.insumo_movimientos USING btree (insumo_id);


--
-- Name: idx_insumos_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_insumos_instancia ON public.insumos USING btree (instancia_id);


--
-- Name: idx_metodos_pago_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_metodos_pago_instancia ON public.metodos_pago USING btree (instancia_id);


--
-- Name: idx_ofertas_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ofertas_instancia ON public.ofertas USING btree (instancia_id);


--
-- Name: idx_pedidos_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pedidos_instancia ON public.pedidos USING btree (instancia_id);


--
-- Name: idx_productos_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_productos_instancia ON public.productos USING btree (instancia_id);


--
-- Name: idx_prt_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prt_email ON public.password_reset_tokens USING btree (email);


--
-- Name: idx_puntos_movimientos_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_puntos_movimientos_instancia ON public.puntos_movimientos USING btree (instancia_id);


--
-- Name: idx_resenas_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_resenas_instancia ON public.resenas USING btree (instancia_id);


--
-- Name: idx_sucursales_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sucursales_instancia ON public.sucursales USING btree (instancia_id);


--
-- Name: idx_users_instancia; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_instancia ON public.users USING btree (instancia_id);


--
-- Name: idx_usuario_modulo_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_usuario_modulo_user ON public.usuario_modulo USING btree (user_id);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: categorias fk_categorias_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categorias
    ADD CONSTRAINT fk_categorias_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: configuraciones fk_configuraciones_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuraciones
    ADD CONSTRAINT fk_configuraciones_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: cupon_uso fk_cu_cupon; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupon_uso
    ADD CONSTRAINT fk_cu_cupon FOREIGN KEY (cupon_id) REFERENCES public.cupones(id) ON DELETE CASCADE;


--
-- Name: cupon_uso fk_cu_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupon_uso
    ADD CONSTRAINT fk_cu_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE CASCADE;


--
-- Name: cupon_uso fk_cu_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupon_uso
    ADD CONSTRAINT fk_cu_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: cupones fk_cupones_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cupones
    ADD CONSTRAINT fk_cupones_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: detalle_pedido fk_dp_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido
    ADD CONSTRAINT fk_dp_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE CASCADE;


--
-- Name: detalle_pedido fk_dp_producto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido
    ADD CONSTRAINT fk_dp_producto FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: detalle_pedido_extras fk_dpe_detalle; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido_extras
    ADD CONSTRAINT fk_dpe_detalle FOREIGN KEY (detalle_pedido_id) REFERENCES public.detalle_pedido(id) ON DELETE CASCADE;


--
-- Name: detalle_pedido_extras fk_dpe_extra; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido_extras
    ADD CONSTRAINT fk_dpe_extra FOREIGN KEY (extra_id) REFERENCES public.extras(id);


--
-- Name: extras fk_extras_categoria; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT fk_extras_categoria FOREIGN KEY (categoria_id) REFERENCES public.categorias(id);


--
-- Name: extras fk_extras_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT fk_extras_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: faqs fk_faqs_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs
    ADD CONSTRAINT fk_faqs_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: insumo_movimientos fk_im_insumo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumo_movimientos
    ADD CONSTRAINT fk_im_insumo FOREIGN KEY (insumo_id) REFERENCES public.insumos(id);


--
-- Name: insumo_movimientos fk_im_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumo_movimientos
    ADD CONSTRAINT fk_im_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: instancias fk_instancias_creada_por; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instancias
    ADD CONSTRAINT fk_instancias_creada_por FOREIGN KEY (creada_por) REFERENCES public.superadministradores(id) ON DELETE SET NULL;


--
-- Name: insumos fk_insumos_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.insumos
    ADD CONSTRAINT fk_insumos_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: metodos_pago fk_metodos_pago_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodos_pago
    ADD CONSTRAINT fk_metodos_pago_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: metodos_pago fk_mp_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodos_pago
    ADD CONSTRAINT fk_mp_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ofertas fk_ofertas_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ofertas
    ADD CONSTRAINT fk_ofertas_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: oferta_producto fk_op_oferta; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oferta_producto
    ADD CONSTRAINT fk_op_oferta FOREIGN KEY (oferta_id) REFERENCES public.ofertas(id) ON DELETE CASCADE;


--
-- Name: oferta_producto fk_op_producto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oferta_producto
    ADD CONSTRAINT fk_op_producto FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


--
-- Name: pagos fk_pagos_metodo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT fk_pagos_metodo FOREIGN KEY (metodo_pago_id) REFERENCES public.metodos_pago(id);


--
-- Name: pagos fk_pagos_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT fk_pagos_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE CASCADE;


--
-- Name: pedidos fk_pedidos_cliente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES public.users(id);


--
-- Name: pedidos fk_pedidos_cupon; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_cupon FOREIGN KEY (cupon_id) REFERENCES public.cupones(id) ON DELETE SET NULL;


--
-- Name: pedidos fk_pedidos_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: pedidos fk_pedidos_sucursal; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT fk_pedidos_sucursal FOREIGN KEY (sucursal_id) REFERENCES public.sucursales(id);


--
-- Name: pedido_historial_estado fk_phe_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedido_historial_estado
    ADD CONSTRAINT fk_phe_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE CASCADE;


--
-- Name: pedido_historial_estado fk_phe_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedido_historial_estado
    ADD CONSTRAINT fk_phe_user FOREIGN KEY (cambiado_por) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: puntos_movimientos fk_pm_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.puntos_movimientos
    ADD CONSTRAINT fk_pm_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE SET NULL;


--
-- Name: puntos_movimientos fk_pm_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.puntos_movimientos
    ADD CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: productos fk_productos_categoria; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES public.categorias(id);


--
-- Name: productos fk_productos_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.productos
    ADD CONSTRAINT fk_productos_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: puntos_movimientos fk_puntos_movimientos_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.puntos_movimientos
    ADD CONSTRAINT fk_puntos_movimientos_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: resenas fk_resenas_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT fk_resenas_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: resenas fk_resenas_pedido; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT fk_resenas_pedido FOREIGN KEY (pedido_id) REFERENCES public.pedidos(id) ON DELETE CASCADE;


--
-- Name: resenas fk_resenas_producto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT fk_resenas_producto FOREIGN KEY (producto_id) REFERENCES public.productos(id);


--
-- Name: resenas fk_resenas_resp; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT fk_resenas_resp FOREIGN KEY (respondido_por) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: resenas fk_resenas_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenas
    ADD CONSTRAINT fk_resenas_user FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: sucursales fk_sucursales_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT fk_sucursales_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: users fk_users_instancia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT fk_users_instancia FOREIGN KEY (instancia_id) REFERENCES public.instancias(id);


--
-- Name: users fk_users_role; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES public.roles(id);


--
-- Name: users fk_users_sucursal; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT fk_users_sucursal FOREIGN KEY (sucursal_id) REFERENCES public.sucursales(id) ON DELETE SET NULL;


--
-- Name: usuario_modulo fk_usuario_modulo_modulo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_modulo
    ADD CONSTRAINT fk_usuario_modulo_modulo FOREIGN KEY (modulo_id) REFERENCES public.modulos(id) ON DELETE CASCADE;


--
-- Name: usuario_modulo fk_usuario_modulo_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_modulo
    ADD CONSTRAINT fk_usuario_modulo_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 5LWvt20jLIHppcCvTWRcFxeTeTSvV5R7wh6GaA2hVMh910QjrCyyNnr06iCxzI0

