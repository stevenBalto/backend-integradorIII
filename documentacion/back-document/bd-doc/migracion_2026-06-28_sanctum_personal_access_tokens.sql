-- =====================================================================
--  MIGRACION 2026-06-28  -  Alinear personal_access_tokens a Laravel Sanctum
--  Aprobada por el usuario. Cambia la tabla de FK user_id a relacion
--  polimorfica (tokenable_type + tokenable_id), como exige Sanctum.
--  Aplicada en vivo via psql (la BD se mantiene por SQL, no por migrate).
-- =====================================================================

-- ---- FORWARD ----
ALTER TABLE personal_access_tokens DROP CONSTRAINT IF EXISTS fk_pat_user;
ALTER TABLE personal_access_tokens RENAME COLUMN user_id TO tokenable_id;
ALTER TABLE personal_access_tokens ADD COLUMN tokenable_type varchar(255) NOT NULL DEFAULT 'App\Models\User';
ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_type DROP DEFAULT;
CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_type_tokenable_id_index
    ON personal_access_tokens (tokenable_type, tokenable_id);

-- ---- ROLLBACK ----
-- DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index;
-- ALTER TABLE personal_access_tokens DROP COLUMN tokenable_type;
-- ALTER TABLE personal_access_tokens RENAME COLUMN tokenable_id TO user_id;
-- ALTER TABLE personal_access_tokens
--     ADD CONSTRAINT fk_pat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
