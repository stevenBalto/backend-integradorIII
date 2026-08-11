-- ============================================================================
-- Seed 2026-08-09 — Productos reales de Rooster Pizza & Grill (menu oficial)
--
-- Reemplaza los productos de PRUEBA (Pizza Test, VERIFICACION DELETE, etc.)
-- por el menu real: 4 Carnes, 14 Pizzas (con tamano Regular/Personal via
-- producto_tamanos), 5 Pastas y 3 Bebidas. Datos y fotos tomados del menu
-- publico del negocio; las imagenes ya estan subidas a Cloudinary
-- (carpeta rooster-pizza/productos) con las credenciales compartidas del
-- equipo, asi que las URLs de abajo funcionan igual para todos.
--
-- Seguro de correr en cualquier BD del equipo (dev local, cada quien la suya):
--   - NO borra productos de prueba que tengan pedidos asociados
--     (detalle_pedido.producto_id) NI reseñas (resenas.producto_id): el precio
--     queda congelado en el detalle (regla de esquema) y las reseñas son datos
--     de usuario, asi que borrarlos rompe la FK. Esos se ARCHIVAN
--     (disponible=false, nombre con sufijo "(archivado - prueba)") en vez
--     de borrarse.
--   - Los productos de prueba SIN pedidos NI reseñas si se borran.
--   - Idempotente-friendly: si ya corriste este script antes, no vuelve a
--     insertar duplicados (verifica por nombre + instancia_id).
-- ============================================================================

DO $$
DECLARE
    v_cat_pizza    bigint;
    v_cat_grill    bigint;
    v_cat_pastas   bigint;
    v_cat_bebidas  bigint;
    v_instancia    bigint := 1;
    v_id           bigint;
    v_ids_borrables bigint[];
BEGIN
    SELECT id INTO v_cat_pizza   FROM categorias WHERE nombre = 'Pizza'   AND instancia_id = v_instancia;
    SELECT id INTO v_cat_grill   FROM categorias WHERE nombre = 'Grill'   AND instancia_id = v_instancia;
    SELECT id INTO v_cat_pastas  FROM categorias WHERE nombre = 'Pastas'  AND instancia_id = v_instancia;
    SELECT id INTO v_cat_bebidas FROM categorias WHERE nombre = 'Bebidas' AND instancia_id = v_instancia;

    IF v_cat_pizza IS NULL OR v_cat_grill IS NULL OR v_cat_pastas IS NULL OR v_cat_bebidas IS NULL THEN
        RAISE EXCEPTION 'No se encontraron las 4 categorias base (Pizza/Grill/Pastas/Bebidas) para instancia_id=%. Revisa tu BD antes de correr este seed.', v_instancia;
    END IF;

    -- Si ya se corrio este seed antes en esta BD, no insertar de nuevo.
    -- Si te faltan las fotos de bebidas (corriste una version anterior de este
    -- mismo script, antes de que se agregaran), este bloque las completa igual.
    IF EXISTS (SELECT 1 FROM productos WHERE nombre = 'Costilla Rooster' AND instancia_id = v_instancia) THEN
        UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300007/rooster-pizza/productos/huc4dqzrdyiqnh9eibhf.jpg', updated_at = now()
            WHERE nombre = 'Natural Smoothies (Fruta de Temporada)' AND instancia_id = v_instancia AND imagen_url IS NULL;
        UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300010/rooster-pizza/productos/lpregevp8tzyad2oepo0.jpg', updated_at = now()
            WHERE nombre = 'Gaseosas' AND instancia_id = v_instancia AND imagen_url IS NULL;
        UPDATE productos SET imagen_url = 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300017/rooster-pizza/productos/zw4ftjyhff5joyrgvkbw.jpg', updated_at = now()
            WHERE nombre = 'Cerveza Premium' AND instancia_id = v_instancia AND imagen_url IS NULL;
        RAISE NOTICE 'El seed de productos reales ya fue aplicado en esta BD (se completaron fotos de bebidas si faltaban).';
        RETURN;
    END IF;

    -- 1) Limpiar productos de prueba, salvo los que tengan pedidos O reseñas
    --    asociadas (ambas FK romperian el DELETE; esos se archivan mas abajo).
    SELECT array_agg(p.id) INTO v_ids_borrables
    FROM productos p
    WHERE p.instancia_id = v_instancia
      AND NOT EXISTS (SELECT 1 FROM detalle_pedido dp WHERE dp.producto_id = p.id)
      AND NOT EXISTS (SELECT 1 FROM resenas r WHERE r.producto_id = p.id);

    IF v_ids_borrables IS NOT NULL THEN
        DELETE FROM producto_extras  WHERE producto_id = ANY(v_ids_borrables);
        DELETE FROM producto_tamanos WHERE producto_id = ANY(v_ids_borrables);
        DELETE FROM oferta_producto  WHERE producto_id = ANY(v_ids_borrables);
        DELETE FROM productos        WHERE id = ANY(v_ids_borrables);
    END IF;

    UPDATE productos p
    SET disponible = false,
        nombre = p.nombre || ' (archivado - prueba)',
        updated_at = now()
    WHERE p.instancia_id = v_instancia
      AND (EXISTS (SELECT 1 FROM detalle_pedido dp WHERE dp.producto_id = p.id)
           OR EXISTS (SELECT 1 FROM resenas r WHERE r.producto_id = p.id))
      AND p.nombre NOT LIKE '%(archivado - prueba)';

    -- 2) Carnes
    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at) VALUES
        (v_cat_grill, 'Costilla Rooster',  NULL, 8500,  'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299496/rooster-pizza/productos/oevmrdmz0uow48hcbtfy.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_grill, 'Ribeye Rooster',    NULL, 12950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299497/rooster-pizza/productos/aihysntl2ttijmoqtdjj.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_grill, 'Lomito Rooster',    NULL, 10950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299498/rooster-pizza/productos/rybxmsl8va9aldmisnxd.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_grill, 'Churrasco Rooster', NULL, 11950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299499/rooster-pizza/productos/fmmpj3yrijfdvx17quno.png', true, false, false, false, v_instancia, now(), now());

    -- 3) Pizzas (cada una con tamano Regular + Personal, salvo Pizza Lomito Rooster)
    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'White / Red Rooster', 'Base de salsa roja, pollo, tres quesos, hongos, cebolla, chile dulce.', 8950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299500/rooster-pizza/productos/pemwtfirhgplh0ttfps8.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8950, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Margarita Rooster', 'Tomate fresco, albahaca y tres tipos de queso.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299501/rooster-pizza/productos/t89xbcjqj1kjhxggwksw.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Jamón & Hongos', 'Jamón y hongos frescos.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299502/rooster-pizza/productos/kizurifktf6unlpyhm3w.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Brazileña Rooster', 'Carne, salame, hongos, tomate, cebolla, chile dulce.', 8950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299503/rooster-pizza/productos/zdwgf0ytqaxjzjfkewcm.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8950, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Vegetariana Rooster', 'Berenjena, zuquini, hongos, tomate, cebolla, chile dulce.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299504/rooster-pizza/productos/fcsgb143ybv05auqcejk.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Camarones Rooster', 'Camarones, tres quesos, cebolla, hongos.', 9500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299504/rooster-pizza/productos/g1wdntgsoahimwfz6v6i.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 9500, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Salame Rooster', 'Salame, cebolla y chile dulce.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299505/rooster-pizza/productos/x9jnafvq6vedwyxzwaho.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Prosciutto Rooster', 'Prosciutto, tres quesos, arúgula.', 9500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299507/rooster-pizza/productos/wmwkagzk8cqw1lu33idm.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 9500, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Pancetta Rooster', 'Jamón, pancetta, salame, cebolla morada, tomate cherry.', 9500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299508/rooster-pizza/productos/pnbkwepjrdlt6rxkvmjr.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 9500, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Hawaiana Rooster', 'Jamón de la casa, piña, tres tipos de queso.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299509/rooster-pizza/productos/pkib8rvjsuhunsfnm35n.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Fire Rooster Pizza', 'Carne, jamón, hongos, chile dulce, chile, jalapeño.', 8950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299510/rooster-pizza/productos/sq4esyxxcwf8bsqshq2c.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8950, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Pepperoni Pizza', 'Pepperoni, hongos, cebolla.', 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299511/rooster-pizza/productos/t0v0in9irg1kjm40vrvw.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 8500, 1, true, now(), now()),
        (v_id, 'Personal', 6000, 2, true, now(), now());

    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at)
        VALUES (v_cat_pizza, 'Tres Carnes Pizza', 'Prosciutto, jamón, pepperoni, chile, aceitunas negras, cebolla.', 9500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299512/rooster-pizza/productos/bo3fqodvdsyqdxvvv9b9.png', true, false, false, false, v_instancia, now(), now())
        RETURNING id INTO v_id;
    INSERT INTO producto_tamanos (producto_id, nombre, precio, orden, activo, created_at, updated_at) VALUES
        (v_id, 'Regular', 9500, 1, true, now(), now()),
        (v_id, 'Personal', 6500, 2, true, now(), now());

    -- Pizza Lomito Rooster: unico precio, sin tamano Personal en el menu original.
    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at) VALUES
        (v_cat_pizza, 'Pizza Lomito Rooster', 'Lomito, chile, cebolla.', 10500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299513/rooster-pizza/productos/pjqtot9aklapdojtbqf4.png', true, false, false, false, v_instancia, now(), now());

    -- 4) Pastas
    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at) VALUES
        (v_cat_pastas, 'Lomito / Camarones Salsa Rosada', NULL, 8950, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299514/rooster-pizza/productos/flfoibqugrbw1wae571d.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_pastas, 'Lomito Salsa de Hongos',           NULL, 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299515/rooster-pizza/productos/s6me1iug7a3zezkjmhi2.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_pastas, 'Pollo Pesto Pistacho',             NULL, 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299516/rooster-pizza/productos/zkwjc3sndack2aaegger.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_pastas, 'Pollo Ajillo Chile',                NULL, 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299517/rooster-pizza/productos/cadz3scvo04jlf736jqe.png', true, false, false, false, v_instancia, now(), now()),
        (v_cat_pastas, 'Carne 3 Quesos',                    NULL, 8500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786299518/rooster-pizza/productos/knkmcd9o6unoaf0tvrn9.png', true, false, false, false, v_instancia, now(), now());

    -- 5) Bebidas (el menu original no tenia foto propia por producto; se usan
    -- fotos genericas libres de Wikimedia Commons -CC BY / CC BY-SA-, subidas
    -- a Cloudinary, como placeholder hasta que el negocio provea fotos reales)
    INSERT INTO productos (categoria_id, nombre, descripcion, precio_base, imagen_url, disponible, destacado, popular, nuevo, instancia_id, created_at, updated_at) VALUES
        (v_cat_bebidas, 'Natural Smoothies (Fruta de Temporada)', NULL, 2000, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300007/rooster-pizza/productos/huc4dqzrdyiqnh9eibhf.jpg', true, false, false, false, v_instancia, now(), now()),
        (v_cat_bebidas, 'Gaseosas',                                NULL, 1500, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300010/rooster-pizza/productos/lpregevp8tzyad2oepo0.jpg', true, false, false, false, v_instancia, now(), now()),
        (v_cat_bebidas, 'Cerveza Premium',                         NULL, 2300, 'https://res.cloudinary.com/jcrp1wfy/image/upload/v1786300017/rooster-pizza/productos/zw4ftjyhff5joyrgvkbw.jpg', true, false, false, false, v_instancia, now(), now());

    RAISE NOTICE 'Seed de productos reales aplicado correctamente.';
END $$;
