import { chromium } from 'playwright';

const OUT = '/home/omar/Documents/Claude/brands/learn-simply/01_WEB/_evidence';
const URL = 'https://learrnsimply.com/?v=' + Date.now();

const browser = await chromium.launch();
for (const vp of [{ w: 1440, h: 1000, tag: 'desktop' }, { w: 375, h: 800, tag: 'mobile' }]) {
  const page = await browser.newPage({ viewport: { width: vp.w, height: vp.h } });
  await page.goto(URL, { waitUntil: 'networkidle', timeout: 60000 });
  const sec = page.locator('.startright-section').first();
  const n = await sec.count();
  console.log(`[${vp.tag}] .startright-section count:`, n);
  if (n > 0) {
    await sec.scrollIntoViewIfNeeded();
    await page.waitForTimeout(700);
    // confirm the title actually renders in IBM Plex (font-family computed)
    const titleFont = await page.locator('.startright-title').first().evaluate(el => getComputedStyle(el).fontFamily).catch(() => 'n/a');
    console.log(`[${vp.tag}] title font-family:`, titleFont);
    const p = `${OUT}/features-section-${vp.tag}-2026-06-23.png`;
    await sec.screenshot({ path: p });
    console.log(`[${vp.tag}] saved:`, p);
  }
  await page.close();
}
await browser.close();
