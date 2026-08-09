-- Migración 2026-08-09: tabla request_timings (telemetría de tiempos de respuesta).
-- La escribe el middleware App\Http\Middleware\LogRequestTiming en cada request.
-- Sin esta tabla el INSERT fallaba (42P01) y se pagaba una excepción por request.
-- Aditiva, re-ejecutable (IF NOT EXISTS). No pertenece al esquema de dominio (telemetría).

CREATE TABLE IF NOT EXISTS public.request_timings (
    id          bigserial PRIMARY KEY,
    method      varchar(10)  NOT NULL,
    path        varchar(255) NOT NULL,
    route       varchar(255) NULL,
    duration_ms integer      NOT NULL,
    status_code integer      NULL,
    user_id     bigint       NULL,
    ip          varchar(45)  NULL,
    created_at  timestamp(0) without time zone NULL,
    updated_at  timestamp(0) without time zone NULL
);

CREATE INDEX IF NOT EXISTS request_timings_path_index    ON public.request_timings (path);
CREATE INDEX IF NOT EXISTS request_timings_route_index   ON public.request_timings (route);
CREATE INDEX IF NOT EXISTS request_timings_user_id_index ON public.request_timings (user_id);
