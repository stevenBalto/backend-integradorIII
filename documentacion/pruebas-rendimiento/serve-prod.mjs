// Sirve el build de PRODUCCIÓN del frontend (carpeta www/) para medir rendimiento
// real: estático + brotli/gzip (como un CDN) + proxy /api y /storage al backend
// + fallback SPA. Node puro (http/zlib/fs), SIN paquetes.
//
// Por qué existe: `ng serve` es build de desarrollo (~9.5 MB sin minificar) y da
// números irreales (~52). Los números del entregable son sobre `ng build
// --configuration production` servido así.
//
// Uso:
//   1) En el front:  ng build --configuration production   (genera www/)
//   2) node serve-prod.mjs [ruta-a-www] [puerto]
//      - ruta-a-www: por defecto intenta el repo hermano ../frotend-integradorIII/www
//      - puerto:     por defecto 4300
//   3) Abrí http://localhost:4300 en Chrome (incógnito) -> Lighthouse -> Desktop.
//      (En "Mobile" verás ~77: es el perfil Slow 4G + CPU 4x, no la experiencia real.)
//
// Requiere el backend arriba en 127.0.0.1:8000 (php artisan serve) con OPcache.
import http from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import zlib from 'node:zlib';
import path from 'node:path';

// Candidatos por defecto para www (ajustá si tu layout de carpetas difiere).
const DEFAULTS = [
  path.resolve(process.cwd(), 'www'),
  path.resolve(process.cwd(), '../../../../Front_Integradorlll/frotend-integradorIII/www'),
  path.resolve(process.cwd(), '../frotend-integradorIII/www'),
];
const ROOT = process.argv[2] ? path.resolve(process.argv[2]) : (DEFAULTS.find(existsSync) || DEFAULTS[0]);
const PORT = Number(process.argv[3]) || 4300;
const BACK = { host: '127.0.0.1', port: 8000 };

if (!existsSync(path.join(ROOT, 'index.html'))) {
  console.error(`No encuentro index.html en:\n  ${ROOT}\nCorré primero "ng build --configuration production" o pasá la ruta a www/ como argumento:\n  node serve-prod.mjs C:/ruta/al/front/www`);
  process.exit(1);
}

const MIME = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8', '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
  '.webp': 'image/webp', '.ico': 'image/x-icon', '.woff2': 'font/woff2', '.woff': 'font/woff',
  '.ttf': 'font/ttf', '.map': 'application/json; charset=utf-8', '.txt': 'text/plain; charset=utf-8',
};
const GZIPPABLE = new Set(['.html', '.js', '.css', '.json', '.svg', '.map', '.txt']);

// Cache de comprimidos: cada archivo se comprime UNA vez (como un CDN). Sin esto,
// brotli q11 sincrono en cada request bloquea el event-loop y ensucia la medición.
const compCache = new Map();
function compressed(file, buf, enc) {
  const key = enc + '::' + file;
  let hit = compCache.get(key);
  if (!hit) {
    hit = enc === 'br'
      ? zlib.brotliCompressSync(buf, { params: { [zlib.constants.BROTLI_PARAM_QUALITY]: 11 } })
      : zlib.gzipSync(buf);
    compCache.set(key, hit);
  }
  return hit;
}

function proxy(req, res) {
  const opts = { host: BACK.host, port: BACK.port, method: req.method, path: req.url, headers: { ...req.headers, host: `${BACK.host}:${BACK.port}` } };
  const up = http.request(opts, (r) => { res.writeHead(r.statusCode, r.headers); r.pipe(res); });
  up.on('error', (e) => { res.writeHead(502); res.end('proxy error: ' + e.message); });
  req.pipe(up);
}

async function serveFile(req, res, file) {
  const ext = path.extname(file).toLowerCase();
  const type = MIME[ext] || 'application/octet-stream';
  const ae = req.headers['accept-encoding'] || '';
  const acceptsBr = /\bbr\b/.test(ae), acceptsGz = /\bgzip\b/.test(ae);
  const headers = { 'Content-Type': type, 'Cache-Control': ext === '.html' ? 'no-cache' : 'public, max-age=31536000, immutable' };
  const buf = await readFile(file);
  if ((acceptsBr || acceptsGz) && GZIPPABLE.has(ext)) {
    const enc = acceptsBr ? 'br' : 'gzip';
    const comp = compressed(file, buf, enc);
    res.writeHead(200, { ...headers, 'Content-Encoding': enc, 'Content-Length': comp.length, Vary: 'Accept-Encoding' });
    res.end(comp);
  } else {
    res.writeHead(200, { ...headers, 'Content-Length': buf.length });
    res.end(buf);
  }
}

http.createServer(async (req, res) => {
  try {
    const url = decodeURIComponent(req.url.split('?')[0]);
    if (url.startsWith('/api') || url.startsWith('/storage')) return proxy(req, res);
    const rel = url === '/' ? '/index.html' : url;
    const file = path.join(ROOT, path.normalize(rel).replace(/^(\.\.[/\\])+/, ''));
    if (existsSync(file) && (await stat(file)).isFile()) return serveFile(req, res, file);
    return serveFile(req, res, path.join(ROOT, 'index.html')); // fallback SPA
  } catch (e) { res.writeHead(500); res.end(String(e)); }
}).listen(PORT, () => {
  console.log(`Prod server: http://localhost:${PORT}`);
  console.log(`  www:    ${ROOT}`);
  console.log(`  proxy:  /api y /storage -> http://${BACK.host}:${BACK.port}`);
  console.log(`  Medí en Chrome incognito -> Lighthouse -> Desktop.`);
});
