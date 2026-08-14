-- Actualizacion 2026-08-13 -- Fotos oficiales de TODO el menu real (21 de 26 productos)
-- Reemplaza este archivo el anterior (actualizacion_2026-08-12_fotos_menu_real.sql).
-- Correr DESPUES de migracion_2026-08-12_categorias_menu_real.sql y del seed real de Steven.
-- Sin foto (quedan con la del seed de Steven): Carne 3 Quesos, Cerveza Premium, Gaseosas,
-- Natural Smoothies, Pizza Lomito Rooster.

DO $$
DECLARE
    v_filas int;
BEGIN
    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686591/rooster-pizza/productos/xbswogse4fnmpo52ccqt.jpg' WHERE nombre = 'Churrasco Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Churrasco Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686595/rooster-pizza/productos/jvkxq4gkij8v4dl3iaio.jpg' WHERE nombre = 'Costilla Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Costilla Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686598/rooster-pizza/productos/zkhrec5sw7lcge2gwpav.jpg' WHERE nombre = 'Fire Rooster Pizza' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Fire Rooster Pizza: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686600/rooster-pizza/productos/mrlej4qhiszkdmb5pbbo.jpg' WHERE nombre = 'Jamón & Hongos' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Jamón & Hongos: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686602/rooster-pizza/productos/ofavvhnxhrxdrd0ls2mx.jpg' WHERE nombre = 'Lomito Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Lomito Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686604/rooster-pizza/productos/pukt6whnoykilk0dfhfe.jpg' WHERE nombre = 'Pancetta Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pancetta Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686607/rooster-pizza/productos/rwuparo1u0zdasksprtr.jpg' WHERE nombre = 'Lomito Salsa de Hongos' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Lomito Salsa de Hongos: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686609/rooster-pizza/productos/pif5onppnozpsgo7v1zu.jpg' WHERE nombre = 'Lomito / Camarones Salsa Rosada' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Lomito / Camarones Salsa Rosada: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686611/rooster-pizza/productos/b17sd0ahcmnhyaof5m2k.jpg' WHERE nombre = 'Pollo Ajillo Chile' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pollo Ajillo Chile: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686613/rooster-pizza/productos/vh1w2oimqch3qkq7fx6t.jpg' WHERE nombre = 'Pollo Pesto Pistacho' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pollo Pesto Pistacho: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686617/rooster-pizza/productos/u6uyglgfno9shr3wyyse.jpg' WHERE nombre = 'Brazileña Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Brazileña Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686620/rooster-pizza/productos/evmphakhjqqsevbhnswc.jpg' WHERE nombre = 'Camarones Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Camarones Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686623/rooster-pizza/productos/vsupyaxgywctxcuzkcsk.jpg' WHERE nombre = 'Hawaiana Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Hawaiana Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686626/rooster-pizza/productos/udvsfi3bx0xqvf0eqxk0.jpg' WHERE nombre = 'Margarita Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Margarita Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686628/rooster-pizza/productos/hfn3rumugrqlsqthrm8m.jpg' WHERE nombre = 'Pepperoni Pizza' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pepperoni Pizza: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686631/rooster-pizza/productos/nm9mcxl8n5jrzstg8euj.jpg' WHERE nombre = 'Prosciutto Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Prosciutto Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686633/rooster-pizza/productos/jfh2ofwcwajabt6kpmv8.jpg' WHERE nombre = 'Salame Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Salame Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686635/rooster-pizza/productos/rput1hggk3qypf1eh403.jpg' WHERE nombre = 'Tres Carnes Pizza' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Tres Carnes Pizza: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686638/rooster-pizza/productos/ag8oz6vcaltegowpsj83.jpg' WHERE nombre = 'Vegetariana Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Vegetariana Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686641/rooster-pizza/productos/ix8vhidtfonkz47axaos.jpg' WHERE nombre = 'White / Red Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'White / Red Rooster: % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786686643/rooster-pizza/productos/ddpedr8iq5ymjlwutmcw.jpg' WHERE nombre = 'Ribeye Rooster' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Ribeye Rooster: % fila(s)', v_filas;

END $$;

SELECT id, nombre, instancia_id, (imagen_url IS NOT NULL) AS tiene_foto FROM productos WHERE instancia_id = 1 ORDER BY nombre;
