// build-pdf.mjs — convert a markdown report → professional PDF via Edge headless.
// Usage:  node scripts/build-pdf.mjs <input.md> [output.pdf]
// Default output: same basename as input, .pdf next to it.

import { readFileSync, writeFileSync, existsSync, mkdtempSync } from "node:fs";
import { spawnSync } from "node:child_process";
import { resolve, basename, dirname, join } from "node:path";
import { tmpdir } from "node:os";
import { marked } from "marked";

const [, , inputArg, outputArg] = process.argv;
if (!inputArg) {
  console.error("usage: node scripts/build-pdf.mjs <input.md> [output.pdf]");
  process.exit(1);
}

const inputPath = resolve(inputArg);
if (!existsSync(inputPath)) {
  console.error(`not found: ${inputPath}`);
  process.exit(1);
}

const outputPath = resolve(
  outputArg || join(dirname(inputPath), basename(inputPath).replace(/\.md$/, ".pdf"))
);

const markdown = readFileSync(inputPath, "utf8");
const bodyHtml = marked.parse(markdown, { gfm: true, breaks: false });

const escapeHtml = (s) =>
  s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#39;");

const title = escapeHtml((markdown.match(/^#\s+(.+)$/m)?.[1] || basename(inputPath)).trim());

const html = `<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<title>${title}</title>
<style>
  @page {
    size: A4;
    margin: 18mm 16mm 20mm 16mm;
  }
  :root {
    --ink: #1a1a1a;
    --ink-soft: #4a4a4a;
    --ink-muted: #6b7280;
    --rule: #e5e7eb;
    --accent: #1f5fbf;
    --critical: #b91c1c;
    --high: #c2410c;
    --medium: #a16207;
    --low: #166534;
    --code-bg: #f6f7f9;
    --table-head-bg: #f8fafc;
    --quote-bg: #f9fafb;
    --quote-border: #d1d5db;
  }

  html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body {
    font-family: 'Inter', 'Segoe UI', 'Cairo', 'Tahoma', system-ui, sans-serif;
    color: var(--ink);
    line-height: 1.55;
    font-size: 10.5pt;
    margin: 0;
  }

  /* Mixed-language: Arabic words inline get proper font + slight size lift */
  :lang(ar), [lang="ar"] { font-family: 'Cairo', 'Tahoma', 'Segoe UI', sans-serif; }

  h1, h2, h3, h4 { color: var(--ink); font-weight: 600; line-height: 1.25; }
  h1 {
    font-size: 22pt;
    margin: 0 0 0.25em 0;
    padding-bottom: 0.4em;
    border-bottom: 2px solid var(--accent);
  }
  h2 {
    font-size: 15pt;
    margin: 1.8em 0 0.5em 0;
    padding-bottom: 0.2em;
    border-bottom: 1px solid var(--rule);
    page-break-after: avoid;
  }
  h3 {
    font-size: 12pt;
    margin: 1.3em 0 0.4em 0;
    color: var(--ink);
    page-break-after: avoid;
  }
  h4 {
    font-size: 10.5pt;
    margin: 1em 0 0.3em 0;
    color: var(--ink-soft);
    page-break-after: avoid;
  }

  p { margin: 0.5em 0; }
  ul, ol { margin: 0.5em 0; padding-left: 1.4em; }
  li { margin: 0.18em 0; }
  li > p { margin: 0.2em 0; }

  hr {
    border: none;
    border-top: 1px solid var(--rule);
    margin: 1.6em 0;
  }

  /* Blockquote */
  blockquote {
    margin: 0.8em 0;
    padding: 0.6em 1em;
    background: var(--quote-bg);
    border-left: 3px solid var(--quote-border);
    color: var(--ink-soft);
    font-size: 0.96em;
  }
  blockquote p:first-child { margin-top: 0; }
  blockquote p:last-child { margin-bottom: 0; }

  /* Code */
  code {
    font-family: 'Consolas', 'Menlo', 'SF Mono', monospace;
    font-size: 0.9em;
    background: var(--code-bg);
    padding: 0.1em 0.35em;
    border-radius: 3px;
    color: #b91c1c;
  }
  pre {
    background: var(--code-bg);
    border: 1px solid var(--rule);
    padding: 0.8em 1em;
    overflow-x: auto;
    font-size: 0.88em;
    border-radius: 4px;
  }
  pre code { background: none; padding: 0; color: var(--ink); }

  /* Tables */
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 0.8em 0;
    font-size: 9.5pt;
    page-break-inside: avoid;
  }
  thead {
    background: var(--table-head-bg);
  }
  th, td {
    border: 1px solid var(--rule);
    padding: 0.5em 0.7em;
    text-align: left;
    vertical-align: top;
  }
  th { font-weight: 600; color: var(--ink); }
  /* Center numeric columns by header convention */
  th:first-child, td:first-child { white-space: nowrap; }

  /* Links — kept readable in print */
  a { color: var(--accent); text-decoration: none; }
  a::after {
    content: " (" attr(href) ")";
    font-size: 0.78em;
    color: var(--ink-muted);
    word-break: break-all;
  }
  /* Don't decorate anchor-only links */
  a[href^="#"]::after,
  a[href^="mailto:"]::after { content: ""; }

  /* Page breaks — keep h2 sections together when starting */
  h2 { page-break-before: auto; }
  table, blockquote, pre { page-break-inside: avoid; }

  /* Footer-like header */
  .doc-meta {
    color: var(--ink-muted);
    font-size: 9pt;
    margin-top: -0.5em;
    margin-bottom: 1em;
  }
</style>
</head>
<body>
${bodyHtml}
</body>
</html>`;

const tmp = mkdtempSync(join(tmpdir(), "report-pdf-"));
const htmlPath = join(tmp, "report.html");
writeFileSync(htmlPath, html, "utf8");

const edgePath = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";

console.log(`→ rendering ${basename(inputPath)} → ${basename(outputPath)}`);

const result = spawnSync(
  edgePath,
  [
    "--headless",
    "--disable-gpu",
    "--no-sandbox",
    "--no-pdf-header-footer",
    "--run-all-compositor-stages-before-draw",
    "--virtual-time-budget=10000",
    `--print-to-pdf=${outputPath}`,
    `file:///${htmlPath.replace(/\\/g, "/")}`,
  ],
  { stdio: ["ignore", "pipe", "pipe"] }
);

if (result.stderr?.length) {
  const errMsg = result.stderr.toString();
  if (errMsg.trim()) console.error("[edge stderr]", errMsg.trim().slice(0, 500));
}

if (result.status !== 0) {
  console.error("Edge headless failed:");
  console.error(result.stderr?.toString() || "(no stderr)");
  process.exit(result.status || 1);
}

if (!existsSync(outputPath)) {
  console.error(`Edge ran but no output produced at ${outputPath}`);
  process.exit(1);
}

const { statSync } = await import("node:fs");
const sizeKb = (statSync(outputPath).size / 1024).toFixed(1);

console.log(`✓ PDF ready: ${outputPath}`);
console.log(`  size: ${sizeKb} KB`);
console.log(`  intermediate HTML kept at: ${htmlPath}`);
