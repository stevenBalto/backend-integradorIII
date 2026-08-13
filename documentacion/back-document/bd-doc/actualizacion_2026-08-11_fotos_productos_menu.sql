-- Actualiza los productos del menu con fotos (subidas a la cuenta compartida
-- de Cloudinary). Correr una sola vez contra tu base de datos local para que
-- se vean las fotos nuevas en el catalogo. Actualiza por nombre+instancia
-- (no por id) porque el id puede variar entre las BD locales de cada quien.
--
-- Nota: Pizza Fortuna y Pizza Guayabo usan la misma foto (misma pizza fisica
-- en las tomas originales). Hamburguesa La Fortuna, Nachos La Fortuna y Papas
-- La Fortuna NO tenian foto oficial disponible todavia -> se les puso una
-- foto generica de referencia para que ningun producto quede sin imagen;
-- reemplazar por la foto real del plato en cuanto se tenga.
--
-- OJO - choca con documentacion/back-document/bd-doc/seed_2026-08-09_productos_reales_rooster.sql:
-- ese seed reemplaza (borra o archiva) TODOS los productos de instancia_id=1
-- sin pedidos/reseñas -- incluye Pizza/Pasta/Costilla/Hamburguesa/Nachos/Papas
-- "La Fortuna" de aqui -- y trae su propio set de fotos oficiales para el
-- menu real. Si en tu BD ya corriste ese seed, este script va a avisar con
-- un NOTICE "0 filas" para cada uno de esos 6 -- no es un error, es que esos
-- nombres ya no existen en tu BD. Los 2 de Guayabo (instancia_id=3) no los
-- toca ese seed, asi que esos si deberian dar siempre 1 fila. Si ves "0 filas"
-- en Guayabo tambien, ahi si hay un problema real (revisar con quien lo corrio
-- que la BD tenga esos 2 productos y esos nombres exactos).
--
-- Como leer el resultado: cada linea de abajo imprime cuantas filas afecto.
-- "1 fila" = encontro el producto y le puso la foto. "0 filas" = no existe
-- ese producto con ese nombre exacto + esa instancia en tu BD (ver nota arriba).

DO $$
DECLARE
    v_filas int;
BEGIN
    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480111/rooster-pizza/productos/relu4wffrvhcjrsrkcvi.jpg' WHERE nombre = 'Costilla La Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Costilla La Fortuna (instancia 1): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480116/rooster-pizza/productos/jxdxnckssdtdilsgzloi.jpg' WHERE nombre = 'Pasta Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pasta Fortuna (instancia 1): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480122/rooster-pizza/productos/hfsx70wuozs8luimsbut.jpg' WHERE nombre = 'Pizza Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pizza Fortuna (instancia 1): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481003/rooster-pizza/productos/jido251grdnm4n7kt4pa.jpg' WHERE nombre = 'Papas La Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Papas La Fortuna (instancia 1, foto generica): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480996/rooster-pizza/productos/qbfuwzxfsugkgzoe4y2q.jpg' WHERE nombre = 'Hamburguesa La Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Hamburguesa La Fortuna (instancia 1, foto generica): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481000/rooster-pizza/productos/xjcslzfek6npxhruenul.jpg' WHERE nombre = 'Nachos La Fortuna' AND instancia_id = 1;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Nachos La Fortuna (instancia 1, foto generica): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480125/rooster-pizza/productos/g9i16qbfw8iet0ddxcyk.jpg' WHERE nombre = 'Pizza Guayabo' AND instancia_id = 3;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Pizza Guayabo (instancia 3): % fila(s)', v_filas;

    UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480132/rooster-pizza/productos/dq9hoqh3klcam6kyc76k.jpg' WHERE nombre = 'Grill Guayabo' AND instancia_id = 3;
    GET DIAGNOSTICS v_filas = ROW_COUNT;
    RAISE NOTICE 'Grill Guayabo (instancia 3): % fila(s)', v_filas;
END $$;

-- Verificacion: mostra todos los productos y si tienen foto o no.
SELECT id, nombre, instancia_id, (imagen_url IS NOT NULL) AS tiene_foto, imagen_url FROM productos ORDER BY id;
