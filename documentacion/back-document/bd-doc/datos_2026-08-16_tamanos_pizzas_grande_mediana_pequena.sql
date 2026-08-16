-- Datos (no esquema): agrega tamanos Grande/Mediana/Pequena a las pizzas que
-- todavia no tenian tamanos configurados. Esquema producto_tamanos ya existia
-- (sin cambios de estructura, solo INSERT de filas).
--
-- Regla acordada con el usuario: Grande = precio_base de la pizza, Mediana =
-- precio_base - 2000, Pequena = precio_base - 4000. Pulgadas de referencia:
-- Grande 12", Mediana 8", Pequena 6" (van en el campo "descripcion", que el
-- frontend ya muestra junto al nombre: "Grande — 12\"").
--
-- Alcance: SOLO las 8 pizzas reales (categoria "Pizza") que no tenian ningun
-- tamano. No se tocaron las 6 pizzas que ya usaban el esquema viejo
-- Regular/Personal (Pancetta, Pepperoni, Tres Carnes, Salame, Vegetariana,
-- White/Red Rooster) ni los productos "(archivado - prueba)".

INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, descripcion, created_at, updated_at)
SELECT p.id, t.nombre, p.precio_base - t.delta, t.orden, true, t.descripcion, now(), now()
FROM productos p
JOIN categorias c ON c.id = p.categoria_id
CROSS JOIN (VALUES
  ('Grande',  0,    1, '12"'),
  ('Mediana', 2000, 2, '8"'),
  ('Pequeña', 4000, 3, '6"')
) AS t(nombre, delta, orden, descripcion)
WHERE c.nombre = 'Pizza'
  AND p.deleted_at IS NULL
  AND p.nombre NOT ILIKE '%archivado%'
  AND p.id IN (65, 67, 72, 71, 64, 63, 75, 69);
