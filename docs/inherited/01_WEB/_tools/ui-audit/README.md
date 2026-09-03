# Learn Simply — UI Audit

Playwright script بيـ catch فئة الـ bugs اللي شفناها في الـ checkout fix loop (2026-05-23): محتوى مخفي ورا الـ header، form inputs بـ widths مختلفة جداً، JS بيـ override CSS، titles مفقودة.

---

## ليه؟

في session واحد لقينا 7 bugs متتالية في الـ checkout — كل واحد محتاج merge + deploy + cache flush + verify. لو كان فيه audit واحد بيشتغل تلقائياً قبل الـ deploy، كنا قابلنا الـ 7 في run واحد بدل 7 iterations منفصلة.

الـ audit ده مش بديل للـ design review أو للـ visual eye balls. هو طبقة **safety net** بتـ catch:

| الـ check | المشكلة اللي اتـ caught | (المشكلة الحقيقية اللي حلّيناها) |
|---|---|---|
| `no-content-under-fixed-header` | عنصر نصي أو button بيقع تحت header مثبّت | title "اكمال الطلب" مخفي تحت الـ header |
| `form-input-width-consistency` | input في فورم بـ width نص اللي جنبه | password field 50% بس username full |
| `no-js-important-on-layout` | JS بيحط `style="...!important"` على main/header/form | nukeCheckoutGaps بيـ zero main padding |
| `expected-visible-title` | صفحة قصدها يكون فيها h1/h2 لكن مفيش | لو CSS hide aggressive نسف الـ title |

---

## الإعداد (مرة واحدة)

```bash
cd brands/learn-simply/_tools/ui-audit
npm run setup
```

ده هـ:
1. install `playwright` (~50 MB)
2. download Chromium لـ Playwright (~150 MB)

---

## التشغيل

```bash
# على production (الـ default)
npm run audit

# على staging أو URL تاني
node audit.mjs --base-url https://staging.learrnsimply.com

# verbose mode (يطبع تفاصيل كل violation)
UI_AUDIT_VERBOSE=1 npm run audit
```

الـ output بيتحط في `reports/YYYY-MM-DDTHH-MM-SS/`:
- `report.json` — summary + كل الـ findings بـ details
- `<page>__<viewport>.png` — screenshot لكل combination

exit code:
- `0` = صفر failures (warnings مسموحة)
- `1` = فيه failures
- `2` = الـ script crashed

---

## الـ Pages اللي بتتفحص

في `pages.json`. كل entry فيه:
- `name` — display name + filename للـ screenshot
- `path` — URL path نسبي للـ `baseUrl`
- `preSteps` (اختياري) — actions قبل فتح الصفحة (مثل `addToCart` لـ /checkout)
- `openLoginToggle` (اختياري) — يفتح الـ "هل لديك حساب؟" قبل ما يقيس
- `expectsTitle` — true لو الصفحة مفروض تـ show h1/h2
- `expectsForm` — true لو الصفحة فيها form (يـ activate الـ input width check)

عشان تضيف صفحة جديدة، أضف entry للـ `pages` array.

عشان تضيف breakpoint جديد، أضفه للـ `viewports` array.

---

## الـ Checks بالتفصيل

### 1. `no-content-under-fixed-header`
بيدور على الـ header بـ selector من `fixedHeaderSelector` في pages.json. لو `position: fixed/sticky`، بيـ scan كل text/interactive elements ويـ flag اللي top بتاعه أقل من header bottom (يعني مخفي وراه).

**bypass:** لو الـ header مش fixed على صفحة معينة، الـ check بيتخطّى تلقائياً.

### 2. `form-input-width-consistency`
لكل `<form>` على الصفحة، بياخد كل الـ text/email/password inputs، يقارن max/min width. لو الـ ratio > 1.5، يـ flag.

**ليه 1.5؟** Forms بـ 2 columns intentional بتعطي ratio = 1.0 لو متظبطة. ratio = 1.5+ تقريباً معناه واحد ضاع.

**bypass:** الـ check بيـ run بس على pages فيها `expectsForm: true`. على homepage مثلاً مش بيـ trigger.

### 3. `expected-visible-title`
بيدور على أول h1/h2/.entry-title/.page-title مرئي. لو مفيش، warning (مش failure — صفحات كتير مفيهاش title عمداً).

**bypass:** بـ pages فيها `expectsTitle: true` بس.

### 4. `no-js-important-on-layout`
بيشيك inline `style="..."` attribute على main/header/footer/form. لو فيه `!important` (يعني JS كاتبه)، warning.

**ليه warning مش failure؟** ساعات JS بياخد !important لـ legitimate reasons (animations، dynamic theming). الـ warning بيخلي الـ reviewer ياخد بال.

---

## إزاي تضيف check جديد

في `audit.mjs`:

1. اكتب function اسمها `checkXxx(page, ...args)` بتـ return `{ violations: [...] }`
2. ضيف call ليها في `auditPage()` بعد الـ checks الموجودة
3. لو الـ violations > 0، `record('fail'|'warn', pageCfg.name, viewport.name, 'check-name', message, details)`

مثال (cross-page button text consistency):
```js
async function checkButtonTextMatches(pageA, pageB, selector, label) {
  const textA = await pageA.locator(selector).first().innerText();
  const textB = await pageB.locator(selector).first().innerText();
  return textA === textB ? null : { violation: { pageA: textA, pageB: textB } };
}
```

---

## CI integration (مستقبلاً)

الـ script بيـ exit بـ code 1 لو فيه failures، فممكن نـ wire لـ:

- **GitHub Actions** على PR لـ `Learrnsimply/edublink-child`: run audit ضد staging، fail الـ PR لو فيه regressions
- **Cron على Hostinger** (نفس pattern الـ backup): يومياً، يبعت Telegram alert لو الـ failure count زاد

دلوقتي manual — الـ workflow:
1. عمل تعديل
2. Push + merge + deploy
3. `npm run audit` محلياً
4. لو failures, ابدأ debug

---

## Limitations

- بيـ test الـ rendered DOM بس — مش بيـ catch issues تظهر بس بعد user interaction (مثل dropdown opens، modal triggers)
- مش بيقارن visual بـ baseline (لو حابب pixel-diff، استخدم Percy/Chromatic فوقه)
- بيـ run sequential — على 7 pages × 3 viewports = 21 run، بياخد ~2-3 دقايق
- بيـ scan ضد production by default — لو هتـ run مرات كتير قريبة، شغّله على staging
