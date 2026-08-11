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
-- menu real. Si en tu BD ya corriste ese seed, este script no va a encontrar
-- esos nombres y no actualiza nada (no rompe nada, pero tampoco hace nada
-- para esos 6). Los 2 de Guayabo (instancia_id=3) no los toca ese seed, asi
-- que esos si quedan siempre actualizados. Resolver con el equipo cual de
-- los dos catalogos de La Fortuna es el que queda.

UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480111/rooster-pizza/productos/relu4wffrvhcjrsrkcvi.jpg' WHERE nombre = 'Costilla La Fortuna' AND instancia_id = 1;
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480116/rooster-pizza/productos/jxdxnckssdtdilsgzloi.jpg' WHERE nombre = 'Pasta Fortuna' AND instancia_id = 1;
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480122/rooster-pizza/productos/hfsx70wuozs8luimsbut.jpg' WHERE nombre = 'Pizza Fortuna' AND instancia_id = 1;
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480125/rooster-pizza/productos/g9i16qbfw8iet0ddxcyk.jpg' WHERE nombre = 'Pizza Guayabo' AND instancia_id = 3;
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480132/rooster-pizza/productos/dq9hoqh3klcam6kyc76k.jpg' WHERE nombre = 'Grill Guayabo' AND instancia_id = 3;
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481003/rooster-pizza/productos/jido251grdnm4n7kt4pa.jpg' WHERE nombre = 'Papas La Fortuna' AND instancia_id = 1; -- foto generica, reemplazar
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480996/rooster-pizza/productos/qbfuwzxfsugkgzoe4y2q.jpg' WHERE nombre = 'Hamburguesa La Fortuna' AND instancia_id = 1; -- foto generica, reemplazar
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481000/rooster-pizza/productos/xjcslzfek6npxhruenul.jpg' WHERE nombre = 'Nachos La Fortuna' AND instancia_id = 1; -- foto generica, reemplazar

-- Verificacion
SELECT id, nombre, instancia_id, imagen_url FROM productos ORDER BY id;
