-- ============================================================================
-- Migracion 2026-08-12 -- Categorias del menu real (Pizza/Grill/Pastas/Bebidas)
--
-- El seed_2026-08-09_productos_reales_rooster.sql asume que ya existen estas
-- 4 categorias para instancia_id=1, pero nunca quedo un script que las creara
-- -- se hicieron a mano en al menos una BD del equipo. Este script las crea
-- si no existen, para que cualquiera pueda correr el seed real sin fallar con
-- "No se encontraron las 4 categorias base".
-- Re-ejecutable: si ya tenes 'Pizza' en instancia_id=1, no hace nada.
-- ============================================================================

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM categorias WHERE nombre = 'Pizza' AND instancia_id = 1) THEN
        INSERT INTO categorias (nombre, descripcion, orden, activa, instancia_id, created_at, updated_at) VALUES
            ('Pizza',   NULL, 1, true, 1, now(), now()),
            ('Grill',   NULL, 2, true, 1, now(), now()),
            ('Pastas',  NULL, 3, true, 1, now(), now()),
            ('Bebidas', NULL, 4, true, 1, now(), now());
        RAISE NOTICE 'Categorias Pizza/Grill/Pastas/Bebidas creadas para instancia_id=1.';
    ELSE
        RAISE NOTICE 'Ya existian, no se hizo nada.';
    END IF;
END $$;

SELECT id, nombre, instancia_id FROM categorias WHERE instancia_id = 1 ORDER BY id;
