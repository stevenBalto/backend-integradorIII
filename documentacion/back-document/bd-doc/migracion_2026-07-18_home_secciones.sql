-- Secciones de curacion del Home cliente (Populares / Lo nuevo), ademas de "destacado" que ya existia.
-- Aplicada manualmente (NO via php artisan migrate, la BD se mantiene por SQL directo).

ALTER TABLE productos ADD COLUMN IF NOT EXISTS popular boolean NOT NULL DEFAULT false;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS nuevo boolean NOT NULL DEFAULT false;
