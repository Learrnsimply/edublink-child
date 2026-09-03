// Assemble Tutor LMS curriculum tree (topics -> lessons + quizzes) for the 5 ACTIVE courses.
// Input:  02-curriculum-raw.csv  (3 sections: @@@TOPICS@@@ / @@@LESSONS@@@ / @@@QUIZZES@@@)
// Output: _generated-curriculum.md  (full tree) + console summary
const fs = require('fs');
const path = require('path');
const DIR = __dirname;
const raw = fs.readFileSync(path.join(DIR, '02-curriculum-raw.csv'), 'utf8');

// 5 active published courses, in display order
const COURSES = [
  { id: 11287, title: 'هياكل البيانات — المستوى الأول | Data Structure L1' },
  { id: 24443, title: 'كورس جافا للمبتدئين + كتاب هدية 🎁 | Java L1' },
  { id: 31578, title: 'البرمجة الكائنية (OOP) بلغة Java' },
  { id: 30816, title: 'هياكل البيانات — المستوى الثاني | Data Structure L2' },
  { id: 29368, title: 'مشاريع بايثون للمبتدئين (مجاني)' },
];
const COURSE_IDS = new Set(COURSES.map(c => c.id));

// --- minimal CSV line parser (handles quoted fields w/ embedded commas) ---
function parseLine(line) {
  const out = [];
  let cur = '', inQ = false;
  for (let i = 0; i < line.length; i++) {
    const ch = line[i];
    if (inQ) {
      if (ch === '"') {
        if (line[i + 1] === '"') { cur += '"'; i++; }
        else inQ = false;
      } else cur += ch;
    } else {
      if (ch === '"') inQ = true;
      else if (ch === ',') { out.push(cur); cur = ''; }
      else cur += ch;
    }
  }
  out.push(cur);
  return out;
}

function parseSection(name) {
  const start = raw.indexOf('@@@' + name + '@@@');
  if (start === -1) return [];
  const after = raw.slice(start + name.length + 6);
  const end = after.indexOf('@@@');
  const block = end === -1 ? after : after.slice(0, end);
  const lines = block.split('\n').map(l => l.replace(/\r$/, '')).filter(l => l.trim());
  // first non-empty line is the header (ID,post_title,...)
  const rows = [];
  for (const l of lines) {
    if (/^ID,post_title/.test(l)) continue;
    const f = parseLine(l);
    if (f.length < 3) continue;
    rows.push({ id: +f[0], title: f[1], parent: +f[2], order: +(f[3] ?? 0) });
  }
  return rows;
}

const topics = parseSection('TOPICS');
const lessons = parseSection('LESSONS');
const quizzes = parseSection('QUIZZES');

const lessonsByTopic = new Map();
for (const l of lessons) {
  if (!lessonsByTopic.has(l.parent)) lessonsByTopic.set(l.parent, []);
  lessonsByTopic.get(l.parent).push(l);
}
const quizzesByTopic = new Map();
for (const q of quizzes) {
  if (!quizzesByTopic.has(q.parent)) quizzesByTopic.set(q.parent, []);
  quizzesByTopic.get(q.parent).push(q);
}
const topicsByCourse = new Map();
for (const t of topics) {
  if (!COURSE_IDS.has(t.parent)) continue;
  if (!topicsByCourse.has(t.parent)) topicsByCourse.set(t.parent, []);
  topicsByCourse.get(t.parent).push(t);
}

const byOrder = (a, b) => a.order - b.order || a.id - b.id;
let md = '';
const summary = [];

for (const c of COURSES) {
  const ts = (topicsByCourse.get(c.id) || []).sort(byOrder);
  let lessonCount = 0, quizCount = 0;
  md += `\n### ${c.title}\n`;
  if (!ts.length) { md += `_(لا توجد وحدات منشورة)_\n`; }
  for (const t of ts) {
    const ls = (lessonsByTopic.get(t.id) || []).sort(byOrder);
    const qs = (quizzesByTopic.get(t.id) || []).sort(byOrder);
    lessonCount += ls.length; quizCount += qs.length;
    md += `\n**الوحدة: ${t.title}**\n`;
    for (const l of ls) md += `- ${l.title}\n`;
    for (const q of qs) md += `- 📝 (اختبار) ${q.title}\n`;
  }
  summary.push(`${c.title}: ${ts.length} وحدة / ${lessonCount} درس / ${quizCount} اختبار`);
}

fs.writeFileSync(path.join(DIR, '_generated-curriculum.md'), md.trim() + '\n', 'utf8');
console.log('=== PARSE STATS ===');
console.log(`topics=${topics.length} lessons=${lessons.length} quizzes=${quizzes.length}`);
console.log('=== ACTIVE-COURSE CURRICULUM SUMMARY ===');
summary.forEach(s => console.log(s));
console.log('=== wrote _generated-curriculum.md (' + md.trim().split('\n').length + ' lines) ===');
