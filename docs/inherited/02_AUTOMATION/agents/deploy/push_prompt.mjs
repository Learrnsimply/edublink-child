#!/usr/bin/env node
/**
 * push_prompt.mjs — يرفع system prompt "عمر" من omar-system-prompt.md
 * كـ **إصدار جديد** في جدول omar.agent_prompts على الـ VPS (نمط rs-aios:
 * الوكيل بيسحب الـ prompt النشط من الـ DB في كل رسالة — تعديل حي بدون redeploy).
 *
 * إمتى تشغّله: بعد أي تعديل على omar-system-prompt.md.
 * بيعمل إيه: يستخرج الجزء بين BEGIN/END markers → يأرشف الإصدار النشط الحالي
 *            → يدخل إصدار جديد status='active' → يطبع رقم الإصدار.
 *
 * التشغيل:  node push_prompt.mjs ["ملاحظة عن التغيير"]
 * المتطلبات: ssh alias `learnsimply-vps` شغّال (بيدخل الـ SQL عبر docker exec psql).
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

const HERE = dirname(fileURLToPath(import.meta.url));
const PROMPT_FILE = resolve(HERE, '..', 'omar-system-prompt.md');
const SQL_FILE = resolve(HERE, 'prompt_push.sql'); // أثر للمراجعة — متجاهَل من git لو حبيت

const BEGIN = '---- BEGIN SYSTEM PROMPT ----';
const END = '---- END SYSTEM PROMPT ----';
const TAG = '$OMARPROMPT$'; // dollar-quoting — بيخلي النص العربي/الرموز تعدي بأمان

const notes = (process.argv[2] || 'push from omar-system-prompt.md').replace(/'/g, "''");

const md = readFileSync(PROMPT_FILE, 'utf8');
// الـ markers لازم سطور كاملة — سطر التوثيق أعلى الملف بيذكرهم inline
const beginMatch = md.match(new RegExp(`^${BEGIN.replace(/[-]/g, '\\-')}\\s*$`, 'm'));
const endMatch = md.match(new RegExp(`^${END.replace(/[-]/g, '\\-')}\\s*$`, 'm'));
if (!beginMatch || !endMatch) throw new Error('BEGIN/END markers (كسطور كاملة) مش موجودين');
const promptText = md.slice(beginMatch.index + beginMatch[0].length, endMatch.index).replace(/\r/g, '').trim();
if (promptText.length < 2000) throw new Error(`الـ prompt قصير بشكل مريب (${promptText.length} حرف)`);
if (promptText.includes(TAG)) throw new Error(`الـ prompt فيه ${TAG} — غيّر الـ dollar-quote tag`);

const sql = `\\set ON_ERROR_STOP on
BEGIN;
CREATE TABLE IF NOT EXISTS omar.agent_prompts (
  id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  version     INTEGER NOT NULL,
  status      TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','archived')),
  prompt_text TEXT NOT NULL,
  notes       TEXT,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_agent_prompts_version ON omar.agent_prompts (version);
CREATE UNIQUE INDEX IF NOT EXISTS uq_agent_prompts_one_active ON omar.agent_prompts (status) WHERE status = 'active';
UPDATE omar.agent_prompts SET status = 'archived' WHERE status = 'active';
INSERT INTO omar.agent_prompts (version, status, prompt_text, notes)
VALUES (
  (SELECT COALESCE(max(version), 0) + 1 FROM omar.agent_prompts),
  'active',
  ${TAG}${promptText}${TAG},
  '${notes}'
)
RETURNING version, length(prompt_text) AS chars;
COMMIT;
`;

writeFileSync(SQL_FILE, sql, 'utf8');
const out = execFileSync(
  'ssh',
  ['learnsimply-vps', 'docker exec -i omar-pgvector psql -U omar_agent -d omar_agent'],
  { input: sql, encoding: 'utf8' },
);
console.log(out.trim());
console.log('✅ الـ prompt اترفع كإصدار جديد active — الوكيل هيقراه من أول رسالة جاية (مفيش redeploy).');
