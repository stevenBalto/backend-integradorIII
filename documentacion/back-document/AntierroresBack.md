# Antierrores — Backend

Catálogo de errores del backend. Cada vez que se corrige un error, se documenta aquí para que NO se repita en la próxima sesión.

Cómo se llena: una entrada por error corregido, con la regla a no romper.

Formato sugerido por entrada:
```
### EB-01 — <título corto>
- Qué pasó: <descripción del error>
- Causa: <por qué pasó>
- Regla: <qué hacer siempre / nunca para no repetirlo>
- Fecha: YYYY-MM-DD
```

### EB-01 — personal_access_tokens debe ser polimórfica (Sanctum)
- Qué pasó: el esquema reconstruido tenía `personal_access_tokens.user_id` (FK a `users`), pero Laravel Sanctum requiere columnas polimórficas `tokenable_type` + `tokenable_id`. Con `user_id`, `createToken()` falla.
- Causa: la tabla del ERD no siguió el formato estándar de Sanctum (polimórfico).
- Regla: para auth con Sanctum, `personal_access_tokens` SIEMPRE va polimórfica (`tokenable_type`/`tokenable_id`, sin FK a `users`). No modelarla con `user_id`.
- Fecha: 2026-06-28 (migración en `bd-doc/migracion_2026-06-28_sanctum_personal_access_tokens.sql`)
