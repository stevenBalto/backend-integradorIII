-- =====================================================================
--  MIGRACION - Modulo Notificaciones (admin) : tabla notificaciones
--  Rooster Pizza & Grill - Proyecto Integrador III
--  Fecha: 2026-07-22
-- ---------------------------------------------------------------------
--  QUE HACE (aditivo, NO rompe lo existente):
--    Crea `notificaciones`: bandeja persistente de avisos para los
--    administradores. Hoy el unico disparador es "pedido_nuevo" (se
--    inserta 1 fila cuando un cliente crea un pedido), pero `tipo` deja
--    la puerta abierta a futuros avisos (stock bajo, reseña nueva, etc).
--
--    Aislada por instancia: se guarda con el `instancia_id` del pedido,
--    asi cada sucursal ve solo SUS notificaciones (consistente con el
--    multi-tenant del proyecto). El frontend admin la consulta por polling
--    (~15-20s) para el "tiempo real" y muestra un toast global + badge.
--
--    Campos que cubren el requerimiento:
--      - ID/codigo del pedido .......... pedido_id  (+ data->codigo)
--      - Nombre del cliente ............ data->nombre_cliente (+ mensaje)
--      - Fecha y hora .................. created_at
--      - Estado inicial del pedido ..... data->estado_inicial
--      - Abrir el pedido ............... pedido_id (FK, ON DELETE SET NULL)
--      - Estado de lectura ............. leida / leida_en
--
--  Re-ejecutable: usa IF NOT EXISTS / guards, se puede correr 2 veces
--  sin error.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS notificaciones (
    id            bigserial     PRIMARY KEY,
    instancia_id  bigint        NOT NULL,
    tipo          varchar(40)   NOT NULL DEFAULT 'pedido_nuevo',
    pedido_id     bigint,
    titulo        varchar(120)  NOT NULL,
    mensaje       varchar(255)  NOT NULL,
    data          jsonb,
    leida         boolean       NOT NULL DEFAULT false,
    leida_en      timestamp,
    created_at    timestamp,
    updated_at    timestamp
);

-- FKs (guardadas para poder re-ejecutar sin duplicar la restriccion)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_notificaciones_instancia'
    ) THEN
        ALTER TABLE notificaciones
            ADD CONSTRAINT fk_notificaciones_instancia
            FOREIGN KEY (instancia_id) REFERENCES instancias(id);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_notificaciones_pedido'
    ) THEN
        ALTER TABLE notificaciones
            ADD CONSTRAINT fk_notificaciones_pedido
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL;
    END IF;
END $$;

-- Indices: la consulta tipica es "no leidas de mi instancia, mas recientes".
CREATE INDEX IF NOT EXISTS idx_notificaciones_instancia ON notificaciones(instancia_id);
CREATE INDEX IF NOT EXISTS idx_notificaciones_no_leidas ON notificaciones(instancia_id, leida, created_at DESC);

COMMIT;

-- =====================================================================
--  ROLLBACK - descomentar y correr solo si hace falta:
-- =====================================================================
-- BEGIN;
-- DROP TABLE IF EXISTS notificaciones;
-- COMMIT;
