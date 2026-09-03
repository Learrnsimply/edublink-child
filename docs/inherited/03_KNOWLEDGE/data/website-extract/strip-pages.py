# Extract clean visible text from rendered page HTML (Elementor-aware enough).
import re, html, sys, io
SRC = '05-pages-rendered-raw.html'
OUT = '05-pages-text.md'
raw = open(SRC, encoding='utf-8', errors='replace').read()

parts = re.split(r'@@@@@PAGE::(.+?)@@@@@', raw)
# parts[0] is preamble; then alternating id, html, id, html...
pages = []
for i in range(1, len(parts), 2):
    pid = parts[i].strip()
    body = parts[i+1] if i+1 < len(parts) else ''
    pages.append((pid, body))

def clean(h):
    title = ''
    m = re.search(r'<title[^>]*>(.*?)</title>', h, re.I | re.S)
    if m:
        title = html.unescape(re.sub(r'\s+', ' ', m.group(1))).strip()
    # drop non-content regions
    for pat in [r'<head[^>]*>.*?</head>', r'<script[^>]*>.*?</script>',
                r'<style[^>]*>.*?</style>', r'<noscript[^>]*>.*?</noscript>',
                r'<svg[^>]*>.*?</svg>', r'<iframe[^>]*>.*?</iframe>',
                r'<header[^>]*>.*?</header>', r'<footer[^>]*>.*?</footer>',
                r'<nav[^>]*>.*?</nav>', r'<!--.*?-->']:
        h = re.sub(pat, ' ', h, flags=re.I | re.S)
    # block closers -> newline for readability
    h = re.sub(r'</(p|div|section|h[1-6]|li|ul|ol|tr|td|br|figure|article)>', '\n', h, flags=re.I)
    h = re.sub(r'<br\s*/?>', '\n', h, flags=re.I)
    h = re.sub(r'<[^>]+>', ' ', h)            # strip remaining tags
    h = html.unescape(h)
    # normalize whitespace per line
    lines = []
    seen = set()
    for ln in h.split('\n'):
        ln = re.sub(r'[ \t ]+', ' ', ln).strip()
        if not ln:
            continue
        # drop pure-symbol / nav noise
        if len(ln) < 2:
            continue
        lines.append(ln)
    # collapse consecutive duplicate lines (Elementor often duplicates)
    out = []
    prev = None
    for ln in lines:
        if ln == prev:
            continue
        out.append(ln)
        prev = ln
    return title, '\n'.join(out)

buf = io.StringIO()
buf.write('# نصوص الصفحات العامة — مرندرة (rendered) من learrnsimply.com\n')
buf.write('> سحب 2026-06-03. النص المرئي بعد إزالة header/footer/scripts. خام للمراجعة — يُنقّى يدوياً في knowledge-base.md.\n\n')
for pid, body in pages:
    title, text = clean(body)
    buf.write(f'\n\n=================== {pid} ===================\n')
    buf.write(f'TITLE: {title}\n')
    buf.write(f'CHARS: {len(text)}\n')
    buf.write('-------------------------------------------\n')
    buf.write(text + '\n')

open(OUT, 'w', encoding='utf-8').write(buf.getvalue())
# console summary
print('pages processed:', len(pages))
for pid, body in pages:
    title, text = clean(body)
    print(f'{pid:20s} | chars={len(text):6d} | {title[:60]}')
print('wrote', OUT)
