-- Actualiza los productos del menu con fotos (subidas a la cuenta compartida
-- de Cloudinary). Correr una sola vez contra tu base de datos local para que
-- se vean las fotos nuevas en el catalogo.
--
-- Nota: Pizza Fortuna y Pizza Guayabo usan la misma foto (misma pizza fisica
-- en las tomas originales). Hamburguesa La Fortuna, Nachos La Fortuna y Papas
-- La Fortuna NO tenian foto oficial disponible todavia -> se les puso una
-- foto generica de referencia para que ningun producto quede sin imagen;
-- reemplazar por la foto real del plato en cuanto se tenga.

UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480111/rooster-pizza/productos/relu4wffrvhcjrsrkcvi.jpg' WHERE id = 8; -- Costilla La Fortuna
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480116/rooster-pizza/productos/jxdxnckssdtdilsgzloi.jpg' WHERE id = 5; -- Pasta Fortuna
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480122/rooster-pizza/productos/hfsx70wuozs8luimsbut.jpg' WHERE id = 4; -- Pizza Fortuna
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480125/rooster-pizza/productos/g9i16qbfw8iet0ddxcyk.jpg' WHERE id = 6; -- Pizza Guayabo
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480132/rooster-pizza/productos/dq9hoqh3klcam6kyc76k.jpg' WHERE id = 7; -- Grill Guayabo
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481003/rooster-pizza/productos/jido251grdnm4n7kt4pa.jpg' WHERE id = 9; -- Papas La Fortuna (foto generica, reemplazar)
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786480996/rooster-pizza/productos/qbfuwzxfsugkgzoe4y2q.jpg' WHERE id = 10; -- Hamburguesa La Fortuna (foto generica, reemplazar)
UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786481000/rooster-pizza/productos/xjcslzfek6npxhruenul.jpg' WHERE id = 11; -- Nachos La Fortuna (foto generica, reemplazar)

-- Verificacion
SELECT id, nombre, imagen_url FROM productos ORDER BY id;
