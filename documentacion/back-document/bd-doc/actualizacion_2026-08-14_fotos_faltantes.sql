-- Actualizacion 2026-08-14 -- Fotos faltantes: Pizza Lomito Rooster y las 3 bebidas
-- (Cerveza Premium, Gaseosas, Natural Smoothies). Correr DESPUES de
-- actualizacion_2026-08-13_menu_completo.sql.

DO $$
DECLARE
    v_filas int;
BEGIN
    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786725655/rooster-pizza/productos/qgdlslmhaz01jaqyrvgz.jpg' WHERE nombre = 'Cerveza Premium' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Cerveza Premium: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786725657/rooster-pizza/productos/hfrqwbbbtpwz9izxpixc.jpg' WHERE nombre = 'Gaseosas' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Gaseosas: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786725659/rooster-pizza/productos/qkpyklmsfuryrxlblniv.jpg' WHERE nombre = 'Pizza Lomito Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pizza Lomito Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786725661/rooster-pizza/productos/hwbzb9fhgf6b3lavswmo.jpg' WHERE nombre = 'Natural Smoothies (Fruta de Temporada)' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Natural Smoothies (Fruta de Temporada): % fila(s)', v_filas;

END $$;

SELECT id, nombre, instancia_id, (imagen_url IS NOT NULL) AS tiene_foto FROM productos WHERE instancia_id = 1 ORDER BY nombre;
