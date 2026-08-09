import fs from 'node:fs';

const DIR = 'C:\\Users\\CHRIST~1\\AppData\\Local\\Temp\\claude\\c--xampp-htdocs-Back-Integradorlll\\3ac3dfb9-7b3d-436f-924d-10a1dd9bcba0\\scratchpad\\perf';
const ROL = { cliente: 'Cliente (usuario)', admin: 'Admin', superadmin: 'Superadmin' };

const pct = (arr, p) => { const s = [...arr].sort((a, b) => a - b); return s[Math.max(0, Math.ceil((p / 100) * s.length) - 1)]; };
const avg = (arr) => arr.reduce((a, b) => a + b, 0) / arr.length;
const r1 = (n) => Math.round(n * 10) / 10;

function parse(filter) {
  const files = fs.readdirSync(DIR).filter(filter);
  const g = new Map();
  for (const f of files) {
    for (const line of fs.readFileSync(`${DIR}\\${f}`, 'utf8').split(/\r?\n/)) {
      if (!line.startsWith('http_req_duration,')) continue;
      const c = line.split(',');
      const value = parseFloat(c[2]); const method = c[8]; const name = c[9]; const scenario = c[11]; const url = c[16] || '';
      if (!ROL[scenario] || !name || name.startsWith('http')) continue;
      const key = scenario + '||' + name;
      if (!g.has(key)) g.set(key, { rol: ROL[scenario], name, method, path: url.replace('http://127.0.0.1:8000', ''), vals: [] });
      g.get(key).vals.push(value);
    }
  }
  return g;
}

const despues = parse((f) => /^raw_.*\.csv$/.test(f));
const antes = parse((f) => /^antes_.*\.csv$/.test(f));

const rows = [...despues.values()].map((g) => {
  const key = Object.keys(ROL).find((k) => ROL[k] === g.rol) + '||' + g.name;
  const a = antes.get(key);
  const mediaAntes = a ? r1(avg(a.vals)) : null;
  const mediaAhora = r1(avg(g.vals));
  const mejora = mediaAntes ? Math.round(((mediaAntes - mediaAhora) / mediaAntes) * 100) : null;
  return {
    rol: g.rol,
    interaccion: g.name.replace(/^(cli|adm|sup): /, ''),
    metodo: g.method,
    endpoint: g.path,
    muestras: g.vals.length,
    media_antes_ms: mediaAntes ?? '',
    media_ms: mediaAhora,
    p50_ms: r1(pct(g.vals, 50)),
    p95_ms: r1(pct(g.vals, 95)),
    mejora_pct: mejora != null ? mejora + '%' : '',
  };
});

const ordRol = { 'Cliente (usuario)': 1, Admin: 2, Superadmin: 3 };
rows.sort((a, b) => (ordRol[a.rol] - ordRol[b.rol]) || (b.media_ms - a.media_ms));

// Resumen por rol (antes vs ahora, promedio de medias por endpoint).
const resumen = ['Cliente (usuario)', 'Admin', 'Superadmin'].map((rl) => {
  const rs = rows.filter((r) => r.rol === rl);
  const ah = r1(avg(rs.map((r) => r.media_ms)));
  const an = r1(avg(rs.filter((r) => r.media_antes_ms !== '').map((r) => r.media_antes_ms)));
  return { rol: rl, endpoints: rs.length, media_antes: an, media_ahora: ah, mejora: an ? Math.round(((an - ah) / an) * 100) + '%' : '' };
});

// ── CSV (UTF-8 BOM) ──
const cols = ['rol', 'interaccion', 'metodo', 'endpoint', 'muestras', 'media_antes_ms', 'media_ms', 'p50_ms', 'p95_ms', 'mejora_pct'];
const head = ['Perspectiva', 'Interacción', 'Método', 'Endpoint (API)', 'Muestras', 'Media ANTES (ms)', 'Media AHORA (ms)', 'p50 (ms)', 'p95 (ms)', 'Mejora'];
fs.writeFileSync(`${DIR}\\resultados.csv`, '\uFEFF' + [head.join(','), ...rows.map((r) => cols.map((k) => r[k]).join(','))].join('\r\n'), 'utf8');

// ── XLS (tabla HTML que Excel abre nativo) ──
const color = (v) => (v === '' ? '' : v <= 100 ? '#dcfce7' : v <= 250 ? '#fef9c3' : '#fee2e2');
const th = (t) => `<th style="background:#1f2937;color:#fff;padding:6px 10px;border:1px solid #cbd5e1;font-family:Segoe UI,Arial;font-size:12px;text-align:left">${t}</th>`;
const td = (t, bg) => `<td style="padding:5px 10px;border:1px solid #e2e8f0;font-family:Segoe UI,Arial;font-size:12px${bg ? ';background:' + bg : ''}">${t}</td>`;
let html = '<html><head><meta charset="UTF-8"></head><body style="font-family:Segoe UI,Arial">';
html += '<h2>Rooster Pizza — Tiempos de respuesta del API (k6)</h2>';
html += `<p style="font-size:12px;color:#475569">Medido con k6 v2.1.0 · 30 iteraciones por rol · ${new Date().toLocaleString('es-CR')}<br>ANTES = sin OPcache · AHORA = con OPcache habilitado · verde ≤100ms · amarillo ≤250ms · rojo &gt;250ms</p>`;
html += '<h3>Resumen por perspectiva</h3><table style="border-collapse:collapse">';
html += '<tr>' + ['Perspectiva', 'Endpoints', 'Media ANTES (ms)', 'Media AHORA (ms)', 'Mejora'].map(th).join('') + '</tr>';
for (const s of resumen) html += '<tr>' + [td(s.rol), td(s.endpoints), td(s.media_antes, color(s.media_antes)), td(s.media_ahora, color(s.media_ahora)), td(s.mejora)].join('') + '</tr>';
html += '</table><br><h3>Detalle por interacción</h3><table style="border-collapse:collapse">';
html += '<tr>' + head.map(th).join('') + '</tr>';
for (const r of rows) {
  html += '<tr>' + [
    td(r.rol), td(r.interaccion), td(r.metodo), td(r.endpoint), td(r.muestras),
    td(r.media_antes_ms, color(r.media_antes_ms)), td(r.media_ms, color(r.media_ms)),
    td(r.p50_ms, color(r.p50_ms)), td(r.p95_ms, color(r.p95_ms)), td(r.mejora_pct),
  ].join('') + '</tr>';
}
html += '</table>';

// ── Sección Frontend (Lighthouse) si existe lighthouse.json ──
try {
  const lh = JSON.parse(fs.readFileSync(`${DIR}\\lighthouse.json`, 'utf8'));
  const scoreColor = (v) => (v >= 90 ? '#dcfce7' : v >= 50 ? '#fef9c3' : '#fee2e2');
  const lcpColor = (v) => (v <= 2500 ? '#dcfce7' : v <= 4000 ? '#fef9c3' : '#fee2e2');
  html += '<br><h3>Frontend — Core Web Vitals (Lighthouse 13, desktop, sin throttle)</h3><table style="border-collapse:collapse">';
  html += '<tr>' + ['Perspectiva', 'Ruta', 'Performance', 'Accesibilidad', 'LCP (ms)', 'FCP (ms)', 'TBT (ms)', 'CLS', 'Speed Index (ms)'].map(th).join('') + '</tr>';
  for (const r of lh) {
    html += '<tr>' + [
      td(r.perspectiva), td(r.url), td(r.performance, scoreColor(r.performance)), td(r.accesibilidad, scoreColor(r.accesibilidad)),
      td(r.LCP_ms, lcpColor(r.LCP_ms)), td(r.FCP_ms), td(r.TBT_ms), td(r.CLS), td(r.SpeedIndex_ms),
    ].join('') + '</tr>';
  }
  html += '</table>';
} catch { /* sin lighthouse.json: solo API */ }

html += '</body></html>';
fs.writeFileSync(`${DIR}\\resultados.xls`, html, 'utf8');

console.log('Resumen:', JSON.stringify(resumen, null, 0));
console.log('Filas detalle:', rows.length);
