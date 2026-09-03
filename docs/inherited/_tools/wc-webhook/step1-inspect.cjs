// Step 1: log into wp-admin and inspect the WooCommerce webhooks page.
// Run with NODE_PATH pointing at the global node_modules so `playwright` resolves.
const { chromium } = require('playwright');

const BASE = 'https://learrnsimply.com';
const PROFILE = 'C:/Users/PUZZLE/Documents/Claude/brands/learn-simply/_tools/wc-webhook/profile';
const SHOT = 'C:/Users/PUZZLE/Documents/Claude/brands/learn-simply/_tools/wc-webhook/step1-webhooks.png';

(async () => {
  const ctx = await chromium.launchPersistentContext(PROFILE, { headless: true });
  const page = ctx.pages()[0] || (await ctx.newPage());
  page.setDefaultTimeout(45000);

  // --- Login (only if the login form is present) ---
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  if (await page.$('#user_login')) {
    await page.fill('#user_login', process.env.WP_USER);
    await page.fill('#user_pass', process.env.WP_PASS);
    await page.click('#wp-submit');
    await page.waitForLoadState('networkidle').catch(() => {});
  }

  // --- Go to the webhooks settings page ---
  await page.goto(`${BASE}/wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2500);
  await page.screenshot({ path: SHOT, fullPage: true });

  const title = await page.title();
  const url = page.url();
  const addHref = await page.getAttribute('a.page-title-action', 'href').catch(() => null);
  const loggedIn = !!(await page.$('#wpadminbar'));
  const text = await page.evaluate(() => {
    const w = document.querySelector('.wrap') || document.body;
    return (w.innerText || '').replace(/\n{2,}/g, '\n').slice(0, 1800);
  });

  console.log('LOGGED_IN:', loggedIn);
  console.log('TITLE:', title);
  console.log('URL:', url);
  console.log('ADD_HREF:', addHref);
  console.log('--- PAGE TEXT ---');
  console.log(text);

  await ctx.close();
})().catch((e) => { console.error('SCRIPT_ERROR:', e.message); process.exit(1); });
