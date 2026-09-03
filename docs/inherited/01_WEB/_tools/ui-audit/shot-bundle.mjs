import { chromium } from 'playwright';

const OUT = '/home/omar/Documents/Claude/brands/learn-simply/01_WEB/_evidence';
const URL = 'https://learrnsimply.com/?v=' + Date.now();

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
await page.goto(URL, { waitUntil: 'networkidle', timeout: 60000 });

// how many bundle cards render?
const cardCount = await page.locator('.ls-bundle-card').count();
console.log('ls-bundle-card count on homepage:', cardCount);

const section = page.locator('.ls-bundles-section').first();
if (await section.count() > 0) {
  await section.scrollIntoViewIfNeeded();
  await page.waitForTimeout(800);
  const p1 = `${OUT}/bundle-section-current-2026-06-23.png`;
  await section.screenshot({ path: p1 });
  console.log('saved section screenshot:', p1);

  const card = page.locator('.ls-bundle-card').first();
  if (await card.count() > 0) {
    const p2 = `${OUT}/bundle-card-current-2026-06-23.png`;
    await card.screenshot({ path: p2 });
    console.log('saved card screenshot:', p2);
  }
} else {
  console.log('.ls-bundles-section NOT found — capturing full page');
  const pf = `${OUT}/homepage-full-2026-06-23.png`;
  await page.screenshot({ path: pf, fullPage: true });
  console.log('saved full page:', pf);
}

await browser.close();
