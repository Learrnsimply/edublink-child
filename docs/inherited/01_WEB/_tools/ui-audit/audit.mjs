#!/usr/bin/env node
/**
 * Learn Simply — UI Audit
 *
 * Headless Playwright run that catches the class of UI regression we hit on
 * the checkout page during the 2026-05-23 fix loop:
 *
 *   - Content sliding under the fixed site header
 *   - Form inputs in the same form rendering at very different widths
 *     (caused by overly broad CSS selectors like `:has(button)`)
 *   - JS overwriting layout CSS with !important inline styles
 *   - Pages where the expected title element is missing or invisible
 *
 * Pages, viewports, and selectors are configured in pages.json. Findings go
 * to reports/<timestamp>/report.json + one screenshot per page+viewport.
 * Exits 1 if any finding has severity=fail, so it can be wired to CI later.
 *
 * Usage:
 *   npm run setup     # one-time: install playwright + chromium
 *   npm run audit     # against the baseUrl from pages.json
 *   node audit.mjs --base-url https://staging.learrnsimply.com
 */

import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// --- Config ------------------------------------------------------------------

const config = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'pages.json'), 'utf8'),
);

const argv = process.argv.slice(2);
const baseUrlOverrideIdx = argv.indexOf('--base-url');
if (baseUrlOverrideIdx >= 0 && argv[baseUrlOverrideIdx + 1]) {
  config.baseUrl = argv[baseUrlOverrideIdx + 1].replace(/\/$/, '');
}

const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
const reportDir = path.join(__dirname, 'reports', timestamp);
fs.mkdirSync(reportDir, { recursive: true });

// --- Finding collector -------------------------------------------------------

/** @type {{severity:'fail'|'warn',page:string,viewport:string,check:string,message:string,details?:unknown}[]} */
const findings = [];

function record(severity, page, viewport, check, message, details) {
  findings.push({ severity, page, viewport, check, message, details });
  const icon = severity === 'fail' ? '❌' : '⚠️ ';
  console.log(`  ${icon} ${check}: ${message}`);
  if (details && process.env.UI_AUDIT_VERBOSE) {
    console.log(`     ${JSON.stringify(details).slice(0, 300)}`);
  }
}

// --- Checks ------------------------------------------------------------------

/**
 * Check 1: No content hidden behind a fixed/sticky header.
 *
 * Catches the regression that happened after PR #6: the site header was
 * fixed at top:10px with height ~80px (footprint ends ~y=90), but `main` had
 * padding-top: 0 from a JS-set inline style. Result: page title and the first
 * checkout card sat at y=0-89, fully behind the header bar.
 */
async function checkNoContentUnderFixedHeader(page, headerSelector) {
  return page.evaluate((sel) => {
    const header = document.querySelector(sel);
    if (!header) return { skipped: true, reason: `no element matches ${sel}` };

    const hr = header.getBoundingClientRect();
    const cs = getComputedStyle(header);
    if (cs.position !== 'fixed' && cs.position !== 'sticky') {
      return { skipped: true, reason: `header is ${cs.position}, not fixed/sticky` };
    }
    if (hr.height === 0) return { skipped: true, reason: 'header has zero height' };

    const headerBottom = hr.bottom;
    const selectors = 'h1, h2, h3, p, button, a.btn, input, label, .entry-title, .page-title, .woocommerce-form-login';
    const violations = [];

    document.querySelectorAll(selectors).forEach((el) => {
      // Skip elements inside the header itself
      if (header.contains(el)) return;

      const ecs = getComputedStyle(el);
      if (ecs.visibility === 'hidden' || ecs.display === 'none') return;

      const r = el.getBoundingClientRect();
      if (r.height === 0 || r.width === 0) return;

      // Element is in viewport AND its top edge is under the header bottom
      // AND it actually overlaps (bottom > header top)
      if (r.top < headerBottom && r.top >= 0 && r.bottom > hr.top) {
        violations.push({
          tag: el.tagName,
          classes: (el.className || '').toString().slice(0, 80),
          text: (el.textContent || '').trim().slice(0, 60),
          element_top: Math.round(r.top),
          element_bottom: Math.round(r.bottom),
          header_bottom: Math.round(headerBottom),
        });
      }
    });

    return {
      header_bottom: Math.round(headerBottom),
      violation_count: violations.length,
      // Cap details so report.json stays small
      violations: violations.slice(0, 10),
    };
  }, headerSelector);
}

/**
 * Check 2: Form inputs in the same form should have comparable widths.
 *
 * Catches the password-input regression: the password input rendered at ~50%
 * width while the username input above it was full-width. Caused by an
 * over-broad `:has(button)` selector that matched the password row too.
 */
async function checkFormInputWidthConsistency(page) {
  return page.evaluate(() => {
    const violations = [];

    document.querySelectorAll('form').forEach((form) => {
      const cs = getComputedStyle(form);
      if (cs.display === 'none' || cs.visibility === 'hidden') return;

      const inputs = [...form.querySelectorAll(
        'input[type="text"], input[type="email"], input[type="password"], input[type="tel"], input.input-text'
      )].filter((el) => {
        const ics = getComputedStyle(el);
        if (ics.display === 'none' || ics.visibility === 'hidden') return false;
        return el.getBoundingClientRect().width > 0;
      });

      if (inputs.length < 2) return;

      const widths = inputs.map((i) => i.getBoundingClientRect().width);
      const min = Math.min(...widths);
      const max = Math.max(...widths);
      const ratio = max / Math.max(min, 1);

      // 1.5x = arbitrary but catches "one is roughly half the other".
      // Forms designed with side-by-side first/last fields will legitimately
      // hit 2x on desktop; this check is most useful on mobile or for forms
      // that should stack vertically.
      if (ratio > 1.5) {
        violations.push({
          form_class: (form.className || '').toString().slice(0, 80),
          min_width: Math.round(min),
          max_width: Math.round(max),
          ratio: ratio.toFixed(2),
          inputs: inputs.map((i) => ({
            name: i.name || '(no name)',
            type: i.type,
            width: Math.round(i.getBoundingClientRect().width),
          })),
        });
      }
    });

    return { violation_count: violations.length, violations };
  });
}

/**
 * Check 3: Pages that should show a title actually have one visible.
 *
 * Catches over-aggressive "hide page header" CSS that nukes the h1 along
 * with the breadcrumb, and the "title walked behind fixed header" case.
 */
async function checkVisibleTitle(page) {
  return page.evaluate(() => {
    const candidates = document.querySelectorAll('h1, .entry-title, .page-title, main h2');
    for (const el of candidates) {
      const cs = getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') continue;
      const r = el.getBoundingClientRect();
      if (r.height === 0 || r.width === 0) continue;
      const text = (el.textContent || '').trim();
      if (!text) continue;
      return {
        found: true,
        tag: el.tagName,
        text: text.slice(0, 80),
        top: Math.round(r.top),
        height: Math.round(r.height),
      };
    }
    return { found: false };
  });
}

/**
 * Check 4: Layout-critical elements should not have JS-applied !important
 * inline styles.
 *
 * Catches the entire class of "JS overrides CSS" bug: when functions.php's
 * nukeCheckoutGaps was zeroing main's padding-top, the inline style attribute
 * on <main> had `padding-top: 0 !important; ...`. JS-set !important inline
 * styles beat CSS even with !important, which is hard to debug from the
 * stylesheet side. Flag any layout-critical element carrying them.
 */
async function checkNoJsImportantOnLayout(page) {
  return page.evaluate(() => {
    const selectors = [
      'main',
      'header',
      'footer',
      '.site-main',
      'form.checkout',
      'form.woocommerce-form-login',
      '.learnsimply-header-main-container',
    ];
    const seen = new Set();
    const violations = [];

    selectors.forEach((sel) => {
      document.querySelectorAll(sel).forEach((el) => {
        if (seen.has(el)) return;
        seen.add(el);
        const styleAttr = el.getAttribute('style') || '';
        if (styleAttr.includes('!important')) {
          violations.push({
            tag: el.tagName,
            classes: (el.className || '').toString().slice(0, 80),
            style: styleAttr.slice(0, 240),
          });
        }
      });
    });

    return { violation_count: violations.length, violations };
  });
}

// --- Page runner -------------------------------------------------------------

async function runPreSteps(page, pageCfg) {
  if (!pageCfg.preSteps) return;
  for (const step of pageCfg.preSteps) {
    if (step.action === 'addToCart') {
      await page.goto(`${config.baseUrl}/?add-to-cart=${step.productId}`, { waitUntil: 'networkidle' });
    }
  }
}

async function tryOpenLoginToggle(page) {
  try {
    const toggle = await page.$('.showlogin');
    if (toggle) {
      await toggle.click();
      await page.waitForTimeout(800);
    }
  } catch {
    // Toggle not present or click failed — that's OK, just means the form is
    // already visible or the test page doesn't have one.
  }
}

async function auditPage(browser, pageCfg, viewport) {
  const ctx = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    locale: 'ar-EG',
  });
  const page = await ctx.newPage();

  console.log(`\n→ ${pageCfg.name} @ ${viewport.name} (${viewport.width}x${viewport.height})`);

  try {
    await runPreSteps(page, pageCfg);

    // Cache-bust each page load so the audit measures the freshest deploy.
    const url = `${config.baseUrl}${pageCfg.path}${pageCfg.path.includes('?') ? '&' : '?'}_ui_audit=${Date.now()}`;
    await page.goto(url, { waitUntil: 'networkidle' });

    // Wait for any deferred JS (e.g. nukeCheckoutGaps fires at 500ms + 1500ms)
    await page.waitForTimeout(config.settleAfterLoadMs ?? 2500);

    if (pageCfg.openLoginToggle) {
      await tryOpenLoginToggle(page);
    }

    // --- Run checks ---
    const overlap = await checkNoContentUnderFixedHeader(page, config.fixedHeaderSelector);
    if (overlap.violation_count > 0) {
      record('fail', pageCfg.name, viewport.name, 'no-content-under-fixed-header',
        `${overlap.violation_count} element(s) sit behind the fixed header (header bottom = ${overlap.header_bottom}px)`,
        overlap.violations);
    }

    if (pageCfg.expectsForm) {
      const formIssues = await checkFormInputWidthConsistency(page);
      if (formIssues.violation_count > 0) {
        record('fail', pageCfg.name, viewport.name, 'form-input-width-consistency',
          `${formIssues.violation_count} form(s) have inputs with wildly different widths`,
          formIssues.violations);
      }
    }

    if (pageCfg.expectsTitle) {
      const title = await checkVisibleTitle(page);
      if (!title.found) {
        record('warn', pageCfg.name, viewport.name, 'expected-visible-title',
          'No visible h1/h2/.entry-title found on a page configured to expect one');
      }
    }

    const jsOverrides = await checkNoJsImportantOnLayout(page);
    if (jsOverrides.violation_count > 0) {
      record('warn', pageCfg.name, viewport.name, 'no-js-important-on-layout',
        `${jsOverrides.violation_count} layout element(s) carry JS-applied !important inline styles (CSS war risk)`,
        jsOverrides.violations);
    }

    // --- Screenshot ---
    const shotPath = path.join(reportDir, `${pageCfg.name}__${viewport.name}.png`);
    await page.screenshot({ path: shotPath, fullPage: false });
  } catch (err) {
    record('fail', pageCfg.name, viewport.name, 'page-load', `Audit threw: ${err.message}`);
  } finally {
    await ctx.close();
  }
}

// --- Main --------------------------------------------------------------------

(async () => {
  console.log(`\nLearn Simply UI Audit`);
  console.log(`Base URL: ${config.baseUrl}`);
  console.log(`Pages:    ${config.pages.length}`);
  console.log(`Viewports:${' '} ${config.viewports.map((v) => v.name).join(', ')}`);
  console.log(`Report:   reports/${timestamp}/`);

  const browser = await chromium.launch();

  for (const pageCfg of config.pages) {
    for (const viewport of config.viewports) {
      await auditPage(browser, pageCfg, viewport);
    }
  }

  await browser.close();

  const report = {
    timestamp,
    baseUrl: config.baseUrl,
    summary: {
      pages: config.pages.length,
      viewports: config.viewports.length,
      runs: config.pages.length * config.viewports.length,
      failures: findings.filter((f) => f.severity === 'fail').length,
      warnings: findings.filter((f) => f.severity === 'warn').length,
      total_findings: findings.length,
    },
    findings,
  };

  fs.writeFileSync(path.join(reportDir, 'report.json'), JSON.stringify(report, null, 2));

  console.log('\n' + '='.repeat(60));
  console.log('Audit complete');
  console.log(`  Failures: ${report.summary.failures}`);
  console.log(`  Warnings: ${report.summary.warnings}`);
  console.log(`  Report:   ${path.relative(process.cwd(), path.join(reportDir, 'report.json'))}`);
  console.log(`  Shots:    ${path.relative(process.cwd(), reportDir)}/*.png`);
  console.log('='.repeat(60));

  process.exit(report.summary.failures > 0 ? 1 : 0);
})().catch((err) => {
  console.error('Audit crashed:', err);
  process.exit(2);
});
