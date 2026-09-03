#!/usr/bin/env node
/**
 * export_workflows.mjs — Backup كل n8n workflows للريبو (نمط rs-aios).
 *
 * بيعمل إيه: يسحب كل الـ workflows من n8n API → ينضّف أي secrets محتملة →
 *            يحفظ JSON لكل واحد في workflows/_exports/ → يولّد _REGISTRY.md.
 * إمتى تشغّله: بعد أي تعديل workflows في n8n (قبل الـ commit).
 * التشغيل:  node export_workflows.mjs   (من أي مكان)
 *
 * ⚠️ n8n هو الـ source of truth — الملفات دي backup للقراءة والاسترجاع،
 *    مش للتعديل اليدوي. التعديل يتم في n8n (عبر n8n-MCP) وبعدين re-export.
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const BRAND_ROOT = resolve(HERE, '..', '..', '..');
const EXPORT_DIR = resolve(HERE, '..', 'workflows', '_exports');
const REGISTRY_FILE = resolve(HERE, '..', 'workflows', '_REGISTRY.md');

const apiKey = readFileSync(resolve(BRAND_ROOT, '.env'), 'utf8').match(/^N8N_API_KEY=[REDACTED];
const base = 'https://n8n.learrnsimply.com/api/v1';
const headers = { 'X-N8N-API-KEY': apiKey };

// أدوار موثّقة (يدوي) — أي workflow جديد من غير دور هيظهر "(وثّقه!)"
const ROLES = {
  whayAvTcXhG6TDeQ: 'WC order جديد → contact في Mautic (webhook WC id 7)',
  VMVSlPEcwNr1Bd6J: 'فورم popup/صفحة /dart → Mautic segment 10 (tag dart-waitlist)',
  ESYkoJgz0e4ngMrM: 'مساعد واتساب "عمر" — الرئيسي (debounce + AI Agent + 4 tools)',
  ULoRfU57m5fSLD2B: 'sub-workflow التصعيد: DB-first + إيميل Brevo + واتساب لأحمد وعمر',
  sv6ART3GjO4JUN81: 'endpoint الـ RAG: embed (Gemini) → omar.kb_search (pgvector)',
  YktkjLMI12YUGWfc: 'Error Trigger → Telegram (البوت المشترك) — متربط في settings.errorWorkflow',
  IfIlQ2RsfsubUkHW: 'wrapper أداة kb_search (toolWorkflow): Execute Trigger → HTTP لـ W3c webhook (لازم active في queue mode)',
  gC9cPSiTBP3M6DZj: 'wrapper أداة order_lookup (toolWorkflow): Execute Trigger → HTTP WC orders (لازم active)',
  R5xDtKKGSeBchue6: 'wrapper أداة mautic_upsert (toolWorkflow): Execute Trigger → HTTP Mautic create (لازم active)',
};

// تنضيف دفاعي لأي secrets محتملة (نفس درس rs-aios) — الـ JSONs المفروض نضيفة أصلاً
// (الـ credentials بـ IDs بس)، ده حزام أمان لو حد حط مفتاح inline بالغلط.
const SECRET_PATTERNS = [
  /AIza[\w-]{30,}/g,                 // Google API keys (صيغة قديمة)
  /AQ\.[\w.-]{30,}/g,                // Google API keys (صيغة جديدة)
  /sk-[\w-]{30,}/g,                  // OpenAI
  /EAA\w{30,}/g,                     // Meta tokens
  /xsmtpsib-[\w-]{30,}/g,            // Brevo SMTP keys
  /eyJhbGciOi[\w.-]{50,}/g,          // JWTs
  /\b[0-9a-f]{48,64}\b/g,            // hex secrets طويلة (Evolution apikey وأشباهه)
];
function scrub(text) {
  let out = text;
  let hits = 0;
  for (const re of SECRET_PATTERNS) out = out.replace(re, (m) => { hits++; return '__REDACTED_' + m.slice(0, 6) + '__'; });
  return { out, hits };
}

const list = await (await fetch(`${base}/workflows?limit=100`, { headers })).json();
mkdirSync(EXPORT_DIR, { recursive: true });

const rows = [];
let totalRedactions = 0;
for (const meta of list.data) {
  const wf = await (await fetch(`${base}/workflows/${meta.id}`, { headers })).json();
  const safeName = wf.name.replace(/[^\w؀-ۿ-]+/g, '_').replace(/_+/g, '_').slice(0, 60);
  const { out, hits } = scrub(JSON.stringify(wf, null, 2));
  totalRedactions += hits;
  writeFileSync(resolve(EXPORT_DIR, `${safeName}__${wf.id}.json`), out, 'utf8');
  rows.push({
    id: wf.id,
    name: wf.name,
    active: wf.active,
    nodes: wf.nodes.length,
    errorWorkflow: wf.settings?.errorWorkflow || '—',
    updatedAt: (wf.updatedAt || '').slice(0, 16).replace('T', ' '),
    role: ROLES[wf.id] || '⚠️ (وثّقه في ROLES جوه export_workflows.mjs!)',
  });
}

const registry = `# 📒 n8n Workflows Registry — Learn Simply

> **يتولّد آلياً** من \`_tools/export_workflows.mjs\` — متعدّلوش يدوي، شغّل الأداة.
> **n8n هو الـ source of truth** (https://n8n.learrnsimply.com) — الـ JSONs في \`_exports/\` نسخ احتياطية.
> آخر export: راجع تاريخ آخر commit للملف ده. الـ credentials في الـ JSONs = IDs بس (مفيش secrets).

| ID | الاسم | الحالة | Nodes | errorWorkflow | آخر تعديل | الدور |
|---|---|---|---|---|---|---|
${rows.map((r) => `| \`${r.id}\` | ${r.name} | ${r.active ? '🟢 ACTIVE' : '⚪ inactive'} | ${r.nodes} | \`${r.errorWorkflow}\` | ${r.updatedAt} | ${r.role} |`).join('\n')}

## استرجاع workflow من الـ backup

1. افتح الـ JSON من \`_exports/\`.
2. n8n UI → Workflows → Import from File (أو \`n8n_create_workflow\` عبر n8n-MCP بالـ nodes/connections من الملف).
3. اربط الـ credentials تاني (الـ IDs في brand \`.env\` §20) — الـ import مش بينقل الـ credentials نفسها.
`;
writeFileSync(REGISTRY_FILE, registry, 'utf8');

console.log(`✅ ${rows.length} workflows exported → ${EXPORT_DIR}`);
console.log(`✅ Registry → ${REGISTRY_FILE}`);
console.log(totalRedactions ? `⚠️ ${totalRedactions} redactions حصلت — فيه secrets كانت inline، راجعها!` : '✅ صفر redactions — الـ JSONs نضيفة (credentials بـ IDs بس).');
