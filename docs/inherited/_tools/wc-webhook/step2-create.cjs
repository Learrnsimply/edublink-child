// Step 2: create the WooCommerce webhook that delivers Order-created events to n8n W1.
const { chromium } = require('playwright');

const BASE = 'https://learrnsimply.com';
const PROFILE = 'C:/Users/PUZZLE/Documents/Claude/brands/learn-simply/_tools/wc-webhook/profile';
const DIR = 'C:/Users/PUZZLE/Documents/Claude/brands/learn-simply/_tools/wc-webhook';
const DELIVERY_URL = 'https://n8n.learrnsimply.com/webhook/wc-mautic-sync-a7f3c19e4b82';

(async () => {
  const ctx = await chromium.launchPersistentContext(PROFILE, { headless: true });
  const page = ctx.pages()[0] || (await ctx.newPage());
  page.setDefaultTimeout(45000);
  const out = {};

  // Ensure logged in
  await page.goto(`${BASE}/wp-admin/`, { waitUntil: 'domcontentloaded' });
  if (await page.$('#user_login')) {
    await page.fill('#user_login', process.env.WP_USER);
    await page.fill('#user_pass', process.env.WP_PASS);
    await page.click('#wp-submit');
    await page.waitForLoadState('networkidle').catch(() => {});
  }

  // Open the "Add webhook" form
  await page.goto(`${BASE}/wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=0`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1500);
  out.formFieldsPresent = {
    name: !!(await page.$('#webhook_name')),
    status: !!(await page.$('#webhook_status')),
    topic: !!(await page.$('#webhook_topic')),
    url: !!(await page.$('#webhook_delivery_url')),
  };
  await page.screenshot({ path: `${DIR}/step2-form-before.png`, fullPage: true });

  // Fill the form
  await page.fill('#webhook_name', 'n8n W1 — Mautic Contact Sync');
  await page.selectOption('#webhook_status', 'active').catch((e) => (out.statusErr = e.message));
  await page.selectOption('#webhook_topic', 'order.created').catch((e) => (out.topicErr = e.message));
  await page.fill('#webhook_delivery_url', DELIVERY_URL);
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${DIR}/step2-form-filled.png`, fullPage: true });

  // Save — WooCommerce sends a validation ping on save for active webhooks
  let clicked = 'none';
  if (await page.$('#publish')) { await page.click('#publish'); clicked = '#publish'; }
  else if (await page.$('button[name="save_webhook"]')) { await page.click('button[name="save_webhook"]'); clicked = 'save_webhook'; }
  else { await page.getByRole('button', { name: /save webhook/i }).click().catch(() => {}); clicked = 'byText'; }
  out.savedVia = clicked;

  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(3000);
  await page.screenshot({ path: `${DIR}/step2-after-save.png`, fullPage: true });
  out.afterUrl = page.url();

  // Read any success/warning notice + the resulting status field if still on edit page
  out.notice = await page.evaluate(() => {
    const n = document.querySelector('#message, .updated, .notice-success, .notice-warning, .notice-error');
    return n ? n.innerText.replace(/\s+/g, ' ').trim().slice(0, 300) : null;
  });
  out.statusValue = await page.evaluate(() => {
    const s = document.querySelector('#webhook_status');
    return s ? s.value : null;
  });

  console.log(JSON.stringify(out, null, 2));
  await ctx.close();
})().catch((e) => { console.error('SCRIPT_ERROR:', e.message); process.exit(1); });
