# Costos y plan de hosteo — Rooster Pizza & Grill

Estimación de costos y arquitectura para poner el sistema en producción (web + apps
móviles) **si el proyecto es elegido**. Pensado para el escenario más barato posible.

> Tipo de cambio de referencia: **≈ ₡525/USD** (jul 2026, aprox). Las tiendas cobran en USD.
> *Última actualización: 2026-07-28.*

---

## 1. Resumen ejecutivo

- **Arranque (una sola vez):** ≈ **₡52.500** (reusando un PC existente).
- **Recurrente:** ≈ **₡69.000 – 119.000 / año** (~₡6-10k/mes).
- **Primer año completo:** ≈ **₡121.000 – 172.000**.
- El **75% del costo recurrente es la cuenta Apple ($99/año)**. Si se arranca solo con
  **web + Android**, el recurrente baja a **₡17.000 – 67.000/año**.
- **No se paga BD hosteada** (lo caro se evita): la base de datos vive en el PC del dueño.

---

## 2. Arquitectura elegida (Opción B — sin hosteo externo del backend)

```
App iOS   ─┐
App Play  ─┼─→ https://api.bonito.com ──(Cloudflare Tunnel)──→ PC del dueño
Web       ─┘        (HTTPS, mismo backend)                     ├─ Nginx (sirve web estática + reenvía /api)
                                                               ├─ Laravel (backend)
                                                               └─ PostgreSQL (única BD)
```

- **Una sola fuente de verdad**: las 3 plataformas pegan al mismo backend y a la misma BD.
  No hay datos duplicados ni sincronización.
- **Cloudflare Tunnel** expone el PC casero con HTTPS y subdominio estable, **sin abrir
  puertos ni IP pública** (la IP dinámica del hogar no importa).
- El **proxy de `ionic serve` es solo desarrollo**. En producción el rol de "servir web +
  reenviar `/api`" lo hace **Nginx** en el PC.

### Decisión de plataformas
Las **3 (web, Play, App Store) exponen cliente + admin + superadmin**, con **un único
login** (el rol lo decide el backend según las credenciales; no hay botón de "entrar como
admin/superadmin"). Es la misma app (Capacitor envuelve el mismo código Angular).

- Lo que realmente protege el superadmin **no es que esté oculto**, sino: auth Sanctum +
  middleware de rol por endpoint + contraseña fuerte + **throttle en `/login`** + HTTPS.
- Único cuidado pendiente: **UX del admin en teléfono** (tablas grandes se ven apretadas) —
  es pulido visual, no bloqueante.

---

## 3. Costos detallados

### 3.1 Una sola vez
| Ítem | USD | ≈ Colones | Nota |
|---|---|---|---|
| Cuenta **Google Play** | $25 | ₡13.125 | Pago único de por vida |
| **UPS** (batería) | $75 | ₡39.375 | Recomendado: evita corromper la BD en apagones |
| **PC servidor** | $0–250 | ₡0–131.250 | Reusar = ₡0. Mini PC (N100/16GB/SSD) basta para poco tráfico |
| **Mac** (build iOS) | $0 | 0 | No comprar: se usa build en la nube (Codemagic) |

### 3.2 Recurrente (por año)
| Ítem | USD | ≈ Colones/año | Nota |
|---|---|---|---|
| **Apple Developer** | $99/año | ₡51.975 | Obligatorio para App Store |
| **Dominio** `.com` | $12/año | ₡6.300 | Namecheap / Cloudflare |
| **Hosting web** | $0 | 0 | Cloudflare Pages/Netlify, o servido por el mismo túnel |
| **Cloudflare Tunnel** | $0 | 0 | HTTPS + subdominio estable |
| **Backups nube** | $0 | 0 | La BD pesa MB → Google Drive gratis (15 GB) alcanza |
| **Electricidad 24/7** | — | ₡10.500–61.000 | Mini PC (~12W) ≈ ₡10.500; desktop (~70W) ≈ ₡61.000 |

### 3.3 Totales
- **Arranque:** ≈ **₡52.500** (reusando PC) — o **+₡131.250** si compran mini PC.
- **Por año:** ≈ **₡69.000 – 119.000**.
- **Primer año:** ≈ **₡121.000 – 172.000**.
- **Solo web + Android:** primer año ≈ **₡69.000 – 120.000**; recurrente **₡17.000 – 67.000/año**.

---

## 4. Fases de implementación (se puede por partes)

Mismo código base (Capacitor) → se agregan plataformas sin rehacer nada.

- **Fase 1 — Web.** Dominio + Cloudflare Tunnel + Nginx (web estática + `/api`) + Laravel +
  PostgreSQL en el PC. Backups automáticos. *(Ya casi listo: hoy corre en dev con el proxy.)*
- **Fase 2 — Play (Android).** `npx cap add android` → build AAB firmado (cualquier PC con
  Android Studio) → subir. Costo: $25 único.
- **Fase 3 — App Store (iOS).** `npx cap add ios` → build en la nube (Codemagic) → subir.
  Costo: $99/año.

### Build iOS sin comprar Mac
- **Codemagic**: 500 min/mes gratis, corre en Macs reales de Apple (legal). Es la opción.
- Alternativas: GitHub Actions (runners macOS), Mac por horas (MacinCloud ~$20-30/mes).
- **NO usar VM de macOS (Hackintosh):** viola el EULA de Apple y es frágil.
- La Mac (o Mac en la nube) se necesita **solo al momento de compilar/publicar** cada
  versión de iOS. Entre versiones, y para el día a día, no se necesita. La app publicada
  corre sola en el teléfono del usuario.

---

## 5. Tiempos de aprobación en tiendas

| Tienda | Primera vez | Updates después |
|---|---|---|
| **Apple App Store** | ~1 semana (review 24-48h + setup de certificados) | 1-2 días |
| **Google Play (cuenta personal)** | ~2-3 semanas (**test cerrado con ≥12 testers por 14 días** obligatorio antes de producción) | horas – 1 día |
| **Google Play (cuenta organización)** | ~días (sin el requisito de 14 días) | horas – 1 día |

- **Truco:** registrar Google Play como **cuenta de organización/empresa** evita el
  testing obligatorio de 14 días. Recomendado si el cliente es un negocio formal.
- Planificar **~2-3 semanas de colchón por tienda** en la primera publicación.

---

## 6. Requisitos operativos del PC servidor (Opción B)

Regla mental: **todo lo que hoy se levanta a mano debe sobrevivir un reinicio sin tocar nada.**

1. **PC encendido 24/7**: apagar sleep/hibernación; en BIOS activar "encender al volver la luz".
2. **Servicios que arrancan solos al bootear**: PostgreSQL (servicio de Windows), Cloudflare
   Tunnel (como servicio), backend (Nginx + PHP-FPM como servicios). Si no, el PC prende pero
   la app sigue caída.
3. **NO usar `php artisan serve` en producción** (dev, un hilo). Usar **Nginx/Apache + PHP-FPM**.
4. **Modo producción de Laravel**: `APP_ENV=production`, `APP_DEBUG=false`,
   `php artisan config:cache route:cache`.
5. **Backups probados**: `pg_dump` diario (Task Scheduler) → subir a la nube. **Probar una
   restauración** (backup sin probar = no backup).
6. **Seguridad**: contraseña fuerte de Postgres, SO/PHP/Postgres actualizados, throttle en
   `/login`. El túnel ya evita abrir puertos.

---

## 7. Cuándo migrar a un VPS (~$5/mes ≈ ₡2.625/mes)

La Opción B (PC casero) es perfecta para web/demo. Pero **una app publicada en tiendas que
se cae porque el PC de casa se apagó = malas reseñas**. Cuando ya haya apps en tiendas y se
busque confiabilidad 24/7, conviene mover backend + BD + web a un **VPS** (Hetzner ~€4/mes,
DigitalOcean, Contabo):

- Ya viene encendido 24/7, con IP pública (no necesita túnel) y mejor uptime.
- Self-hostear PostgreSQL en el mismo VPS **evita la BD *managed*** (que es lo caro).
- Misma arquitectura, sin depender de que el PC casero esté prendido.

---

## 8. Checklist para el día de producción

- [ ] Comprar dominio y ponerlo en Cloudflare (DNS).
- [ ] Cloudflare Tunnel: `api.bonito.com` → `127.0.0.1:8000` del PC.
- [ ] Nginx sirviendo `ng build` estático + reenviando `/api` a Laravel.
- [ ] Laravel en modo producción (`APP_DEBUG=false`, caches) + throttle en `/login`.
- [ ] PostgreSQL como servicio + backup diario a la nube (probar restore).
- [ ] BIOS "encender al volver la luz" + servicios en autostart + UPS.
- [ ] `environment` móvil apuntando a `https://api.bonito.com/api` (absoluto, no `/api`).
- [ ] CORS de Laravel permitiendo `capacitor://localhost` y `http://localhost`.
- [ ] Cuenta Google Play (organización si se puede) + assets + política de privacidad.
- [ ] Cuenta Apple Developer + build iOS en Codemagic + assets + política de privacidad.
