# /blog/ verification — 2026-05-23

Captured via Playwright on 2026-05-23 to settle the audit's BUG-001 claim that `/blog/` returned 404.

## Finding

- `curl -A "Mozilla/5.0" /blog/` → `HTTP 404` (bot/cache layer responds 404)
- Real browser via Playwright → page loads with title "المقالات - اتعلم ببساطة", one article visible
- Article card links to `learrnsimply.com/prompt/` (NOT `/blog/prompt/`)

## Conclusion

**BUG-001 was a false positive in audit.** The /blog/ archive works for browsers. The deeper insight: posts use root-level permalinks (no `/blog/` prefix), which is unusual for SEO but functional.

## Files

| File | What |
|---|---|
| `blog-page-1-top.png` | Above-the-fold of /blog/ — title "المقالات" + search bar visible |
| `blog-page-2-full.png` | Full-page screenshot — article card + footer |
| `prompt-page.png` | The /prompt/ article page reached by clicking the article card |
