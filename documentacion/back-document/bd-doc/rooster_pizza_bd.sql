--
-- PostgreSQL database dump
--

\restrict QiEERqU6nTXd5tOTjSP4aBLYugqINCIUB6r6ucOdt1Gbm4MTCuwHDVrJaST2z1z

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
    instancia_id bigint
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
    notas character varying(200),
    producto_tamano_id bigint,
    tamano_nombre character varying(40)
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
    categoria_id bigint,
    nombre character varying(80) NOT NULL,
    precio numeric(10,2) NOT NULL,
    disponible boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    instancia_id bigint NOT NULL,
    es_general boolean DEFAULT false NOT NULL,
    imagen_url character varying(255),
    CONSTRAINT chk_extras_general_xor_categoria CHECK ((((es_general = true) AND (categoria_id IS NULL)) OR ((es_general = false) AND (categoria_id IS NOT NULL))))
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
    instancia_id bigint
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
    instancia_id bigint NOT NULL,
    codigo character varying(12) NOT NULL,
    pagado boolean DEFAULT false NOT NULL,
    pagado_en timestamp without time zone,
    nombre_cliente character varying(120)
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
    tokenable_id bigint CONSTRAINT personal_access_tokens_user_id_not_null NOT NULL,
    name character varying(100) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp without time zone,
    expires_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    tokenable_type character varying(255) NOT NULL
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
-- Name: producto_extras; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_extras (
    id bigint NOT NULL,
    producto_id bigint NOT NULL,
    extra_id bigint NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: producto_extras_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.producto_extras_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: producto_extras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.producto_extras_id_seq OWNED BY public.producto_extras.id;


--
-- Name: producto_tamanos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_tamanos (
    id bigint NOT NULL,
    producto_id bigint NOT NULL,
    nombre character varying(40) NOT NULL,
    precio numeric(10,2) NOT NULL,
    orden integer DEFAULT 0 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    descripcion character varying(60)
);


--
-- Name: producto_tamanos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.producto_tamanos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: producto_tamanos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.producto_tamanos_id_seq OWNED BY public.producto_tamanos.id;


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
    instancia_id bigint NOT NULL,
    popular boolean DEFAULT false NOT NULL,
    nuevo boolean DEFAULT false NOT NULL
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
-- Name: producto_extras id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_extras ALTER COLUMN id SET DEFAULT nextval('public.producto_extras_id_seq'::regclass);


--
-- Name: producto_tamanos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_tamanos ALTER COLUMN id SET DEFAULT nextval('public.producto_tamanos_id_seq'::regclass);


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
-- Name: producto_extras producto_extras_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_extras
    ADD CONSTRAINT producto_extras_pkey PRIMARY KEY (id);


--
-- Name: producto_tamanos producto_tamanos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_tamanos
    ADD CONSTRAINT producto_tamanos_pkey PRIMARY KEY (id);


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
-- Name: pedidos uq_pedidos_codigo; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pedidos
    ADD CONSTRAINT uq_pedidos_codigo UNIQUE (codigo);


--
-- Name: producto_extras uq_producto_extras; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_extras
    ADD CONSTRAINT uq_producto_extras UNIQUE (producto_id, extra_id);


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
-- Name: idx_producto_extras_extra; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_producto_extras_extra ON public.producto_extras USING btree (extra_id);


--
-- Name: idx_producto_extras_producto; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_producto_extras_producto ON public.producto_extras USING btree (producto_id);


--
-- Name: idx_producto_tamanos_producto; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_producto_tamanos_producto ON public.producto_tamanos USING btree (producto_id);


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
-- Name: detalle_pedido fk_dp_tamano; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_pedido
    ADD CONSTRAINT fk_dp_tamano FOREIGN KEY (producto_tamano_id) REFERENCES public.producto_tamanos(id) ON DELETE SET NULL;


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
-- Name: producto_extras fk_pe_extra; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_extras
    ADD CONSTRAINT fk_pe_extra FOREIGN KEY (extra_id) REFERENCES public.extras(id) ON DELETE CASCADE;


--
-- Name: producto_extras fk_pe_producto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_extras
    ADD CONSTRAINT fk_pe_producto FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


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
-- Name: producto_tamanos fk_pt_producto; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_tamanos
    ADD CONSTRAINT fk_pt_producto FOREIGN KEY (producto_id) REFERENCES public.productos(id) ON DELETE CASCADE;


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

\unrestrict QiEERqU6nTXd5tOTjSP4aBLYugqINCIUB6r6ucOdt1Gbm4MTCuwHDVrJaST2z1z

