-- =====================================================================
--  MIGRACION F0  -  Multi-Tenant: base de instancias
--  Rooster Pizza & Grill  -  Proyecto Integrador III
--  Fecha: 2026-07-12
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    1. Crea la tabla `instancias` (el tenant).
--    2. Crea la Instancia 1 = "Rooster Pizza & Grill - La Fortuna".
--    3. Agrega `instancia_id` a `users` y a las tablas operativas raiz.
--    4. Backfillea TODOS los datos actuales a la Instancia 1.
--    5. Pone `instancia_id` NOT NULL + FK + indice donde corresponde.
--
--  Referencia de diseno: ../ARQUITECTURA-SUPERADMIN-MULTITENANT.md (Fase F0).
--  NOTA: no incluye el Global Scope (eso es codigo Laravel, va aparte).
--        Tras esta migracion el login y el catalogo siguen IGUAL.
--
--  COMO CORRERLO EN pgADMIN:
--    - Conectate a la base `rooster_pizza`.
--    - Abri este archivo en el Query Tool y ejecuta TODO (F5).
--    - Todo va dentro de una transaccion: si algo falla, no se aplica nada.
--
--  Re-ejecutable: usa IF NOT EXISTS / guards, se puede correr 2 veces sin error.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Tabla `instancias` (tenant)
--    `creada_por` referencia a superadministradores; esa tabla se crea en F1,
--    por eso aqui queda como bigint sin FK todavia (la FK se agrega en F1).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS instancias (
    id               bigserial PRIMARY KEY,
    nombre           varchar(120) NOT NULL,
    correo_principal varchar(150),
    estado           varchar(20)  NOT NULL DEFAULT 'activa',  -- activa|inactiva|suspendida
    creada_por       bigint,                                  -- FK -> superadministradores(id) en F1
    created_at       timestamp,
    updated_at       timestamp,
    deleted_at       timestamp
);

-- ---------------------------------------------------------------------
-- 2. Instancia inicial (id = 1). El sistema de hoy ES esta instancia.
-- ---------------------------------------------------------------------
INSERT INTO instancias (id, nombre, correo_principal, estado, created_at, updated_at)
VALUES (1, 'Rooster Pizza & Grill - La Fortuna', 'contacto@roosterpizza.cr', 'activa', now(), now())
ON CONFLICT (id) DO NOTHING;

-- Sincroniza la secuencia para que el proximo INSERT no choque con id=1.
SELECT setval('instancias_id_seq', GREATEST((SELECT COALESCE(MAX(id), 1) FROM instancias), 1));

-- ---------------------------------------------------------------------
-- 3. `users`: columnas nuevas de esta fase (solo el tenant).
--    El resto de columnas (usuario, password_temporal, etc.) llegan en F3/F4.
-- ---------------------------------------------------------------------
ALTER TABLE users ADD COLUMN IF NOT EXISTS instancia_id bigint;
UPDATE users SET instancia_id = 1 WHERE instancia_id IS NULL;
ALTER TABLE users ALTER COLUMN instancia_id SET NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_users_instancia') THEN
        ALTER TABLE users
            ADD CONSTRAINT fk_users_instancia
            FOREIGN KEY (instancia_id) REFERENCES instancias(id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_users_instancia ON users (instancia_id);

-- ---------------------------------------------------------------------
-- 4. `configuraciones`: tenant NULLABLE (NULL = configuracion global a futuro).
--    Las configuraciones actuales son del negocio, se asignan a la Instancia 1.
-- ---------------------------------------------------------------------
ALTER TABLE configuraciones ADD COLUMN IF NOT EXISTS instancia_id bigint;
UPDATE configuraciones SET instancia_id = 1 WHERE instancia_id IS NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_configuraciones_instancia') THEN
        ALTER TABLE configuraciones
            ADD CONSTRAINT fk_configuraciones_instancia
            FOREIGN KEY (instancia_id) REFERENCES instancias(id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_configuraciones_instancia ON configuraciones (instancia_id);

-- ---------------------------------------------------------------------
-- 5. Tablas operativas raiz: instancia_id NOT NULL + FK + indice.
--    Se recorren en un solo bloque para no repetir 11 veces el mismo SQL.
--    Las tablas HIJAS (detalle_pedido, oferta_producto, cupon_uso, pagos,
--    detalle_pedido_extras, pedido_historial_estado) NO llevan instancia_id:
--    heredan el tenant a traves de su tabla padre.
-- ---------------------------------------------------------------------
DO $$
DECLARE
    t   text;
    fk  text;
    tablas text[] := ARRAY[
        'sucursales', 'categorias', 'productos', 'extras', 'ofertas',
        'cupones', 'pedidos', 'resenas', 'puntos_movimientos',
        'metodos_pago', 'faqs'
    ];
BEGIN
    FOREACH t IN ARRAY tablas LOOP
        -- 5a. columna
        EXECUTE format('ALTER TABLE %I ADD COLUMN IF NOT EXISTS instancia_id bigint', t);
        -- 5b. backfill a la instancia 1
        EXECUTE format('UPDATE %I SET instancia_id = 1 WHERE instancia_id IS NULL', t);
        -- 5c. NOT NULL
        EXECUTE format('ALTER TABLE %I ALTER COLUMN instancia_id SET NOT NULL', t);
        -- 5d. FK (con guard para ser re-ejecutable)
        fk := 'fk_' || t || '_instancia';
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = fk) THEN
            EXECUTE format(
                'ALTER TABLE %I ADD CONSTRAINT %I FOREIGN KEY (instancia_id) REFERENCES instancias(id)',
                t, fk
            );
        END IF;
        -- 5e. indice
        EXECUTE format('CREATE INDEX IF NOT EXISTS idx_%s_instancia ON %I (instancia_id)', t, t);
    END LOOP;
END $$;

COMMIT;

-- =====================================================================
--  VERIFICACION (correr aparte despues del COMMIT, opcional):
-- =====================================================================
--   SELECT * FROM instancias;
--   SELECT id, nombre, email, instancia_id FROM users;
--   SELECT relname
--     FROM pg_stat_user_tables
--    WHERE relname IN ('sucursales','categorias','productos','extras','ofertas',
--                      'cupones','pedidos','resenas','puntos_movimientos',
--                      'metodos_pago','faqs','users','configuraciones');
--   -- y en pgAdmin: cada tabla debe tener ahora la columna instancia_id.


-- =====================================================================
--  ROLLBACK  (DESHACER la F0)  -  ejecutar SOLO si necesitas revertir.
--  Descomenta este bloque y corrilo. Quita FKs, columnas, indices y la tabla.
-- =====================================================================
-- BEGIN;
-- DO $$
-- DECLARE
--     t text;
--     tablas text[] := ARRAY[
--         'sucursales','categorias','productos','extras','ofertas','cupones',
--         'pedidos','resenas','puntos_movimientos','metodos_pago','faqs'
--     ];
-- BEGIN
--     FOREACH t IN ARRAY tablas LOOP
--         EXECUTE format('ALTER TABLE %I DROP CONSTRAINT IF EXISTS fk_%s_instancia', t, t);
--         EXECUTE format('DROP INDEX IF EXISTS idx_%s_instancia', t);
--         EXECUTE format('ALTER TABLE %I DROP COLUMN IF EXISTS instancia_id', t);
--     END LOOP;
-- END $$;
-- ALTER TABLE users            DROP CONSTRAINT IF EXISTS fk_users_instancia;
-- DROP INDEX IF EXISTS idx_users_instancia;
-- ALTER TABLE users            DROP COLUMN IF EXISTS instancia_id;
-- ALTER TABLE configuraciones  DROP CONSTRAINT IF EXISTS fk_configuraciones_instancia;
-- DROP INDEX IF EXISTS idx_configuraciones_instancia;
-- ALTER TABLE configuraciones  DROP COLUMN IF EXISTS instancia_id;
-- DROP TABLE IF EXISTS instancias;
-- COMMIT;
-- =====================================================================
