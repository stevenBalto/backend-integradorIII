-- =====================================================================
--  MIGRACION 2026-07-13 — Modulo Inventario (insumos/materia prima)
--  Agrega 2 tablas nuevas para el panel admin "Inventario": insumos
--  (ingredientes/carnes, NO productos del menu) e insumo_movimientos
--  (historial de tomas fisicas / ajustes de cantidad).
--  Aprobado explicitamente por el usuario (2026-07-13).
-- =====================================================================

-- ── FORWARD ──────────────────────────────────────────────────────────

CREATE TABLE insumos (
    id              bigserial PRIMARY KEY,
    nombre          varchar(120)  NOT NULL,
    unidad_medida   varchar(20)   NOT NULL,
    cantidad_actual numeric(10,2) NOT NULL DEFAULT 0,
    stock_minimo    numeric(10,2),
    created_at      timestamp,
    updated_at      timestamp,
    deleted_at      timestamp
);

CREATE TABLE insumo_movimientos (
    id                bigserial PRIMARY KEY,
    insumo_id         bigint        NOT NULL,
    user_id           bigint,
    tipo              varchar(20)   NOT NULL DEFAULT 'toma_fisica',
    cantidad_anterior numeric(10,2) NOT NULL,
    cantidad_nueva    numeric(10,2) NOT NULL,
    diferencia        numeric(10,2) NOT NULL,
    nota              varchar(255),
    created_at        timestamp
);

CREATE INDEX idx_insumo_movimientos_insumo_id ON insumo_movimientos(insumo_id);

ALTER TABLE insumo_movimientos
    ADD CONSTRAINT fk_im_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id),
    ADD CONSTRAINT fk_im_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE SET NULL;

-- ── ROLLBACK ─────────────────────────────────────────────────────────
-- ALTER TABLE insumo_movimientos DROP CONSTRAINT fk_im_insumo, DROP CONSTRAINT fk_im_user;
-- DROP INDEX idx_insumo_movimientos_insumo_id;
-- DROP TABLE insumo_movimientos;
-- DROP TABLE insumos;
