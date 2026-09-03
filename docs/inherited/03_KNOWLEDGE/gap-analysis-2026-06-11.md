# Gap Analysis — إيه اللي اتعمل وإيه الفاضل (2026-06-11)

> **الغرض:** جرد مصحّح قبل بناء Spec Kit جديد (GTM v2 + Website Audit v2).
> المصادر: `gtm-roadmap-90days.md` · `comprehensive-audit.md` · `01_WEB/specs/001-bug-remediation-90day/` · `bugs-report.md` · `HANDOFF.md` · CLAUDE.md CURRENT STATE.
>
> ⚠️ **تنبيه مصدر-الحقيقة:** `tasks.md` بتاع spec 001 آخر تحديث 2026-05-24 — قبل الـ VPS (2026-06-01) وقبل نقل الاستضافة (2026-06-04). أي "blocker" مكتوب هناك عن VPS أو Mautic أو Pixel **منتهي**. الملف ده هو التصحيح.
>
> 🔧 **تصحيح ذاتي (2026-06-11):** نسخة أولى من الملف ده قالت إن SES هي قناة الإرسال وإنها بلوكر — **غلط**. الحقيقة (من `.env` §22): **Brevo هو الـ PRIMARY sender من 2026-06-06**، مربوط بـ Mautic ومتأكد، 300/يوم free. SES رُفض 2026-06-08 لكنه **مش قناتنا ومش بلوكر** — بقى تحسين سعة اختياري للـ 13K في Phase 002. القسم ده اتصحّح بالكامل تحت.

---

## 1) الصورة الكبيرة

| المسار | نسبة الإنجاز | الحالة |
|---|---|---|
| **GTM (روح السوق)** | ~35-40% | البنية التحتية كلها LIVE — التنفيذ التسويقي نفسه لسه |
| **Website Audit/Bugs (001)** | ~20% tasks · لكن أخطر الحاجات اتقفلت | Phase 0 ✅ · Security 9/12 · Perf 6/22 · Theme 0% |

**أكبر إنجاز متراكم:** VPS + Mautic (13,711 contact) + n8n + تتبع Meta/TikTok + مساعد واتساب "عمر" FULLY LIVE + صفحة /dart + popup + 74+ waitlist + SES provisioned.

**أكبر فجوة:** كل قنوات الإيراد الجديدة واقفة على 3 حاجات: منتج Dart في WooCommerce (أحمد) · موافقة AWS SES (Case 178058147100175) · صفر شغل على يوتيوب (350K مشترك بدون أي CTA أو lead magnet).

---

## 2) GTM — جرد بالـ Phase

### Phase 0 — Audit & Access ✅ مكتمل
كل الـ audits اتعملت (شامل + قنوات + تتبع + دفع). الأرقام مثبتة: ~67K EGP/شهر · 30.2% إلغاء أوردرات (~200-350K EGP/سنة نزيف) · 13,711 contact · L1→L2 conversion = 7.5%.

### Phase 1 — Measurement 🟡
| البند | الحالة |
|---|---|
| Meta Pixel + CAPI | ✅ LIVE ومتأكد (699717432496147) |
| TikTok Pixel | ✅ LIVE (1.04M EGP attributed) |
| GA4 conversion events (purchase/checkout) | ❌ لسه PageView بس |
| Microsoft Clarity (heatmaps) | ❌ مش متعمل (15 دقيقة شغل) |
| UTM discipline + GTM container | ❌ مش متعمل (قرار GTM متاخد من 2026-05-24، مؤجل) |

### Phase 2 — Email Engine 🟡
| البند | الحالة |
|---|---|
| أداة الإيميل (Mautic على VPS) | ✅ LIVE + hardened |
| استيراد الـ 13K | ✅ 13,711 (tag wp-import) |
| إيميل إعادة التعريف (id 1) | ✅ مبني + متختبر inbox — **مستني GO** |
| سيكوينس Dart (ids 2-5) | ✅ drafts — **مستني منتج Dart بس** (القناة جاهزة) |
| محرك الإرسال | ✅ **Brevo = PRIMARY (live 2026-06-06)** مربوط بـ Mautic ومتأكد، 300/يوم free. (SES رُفض 2026-06-08 — اختياري للـ 13K، appeal في Phase 002) |
| Welcome sequence | ❌ |
| Cart abandonment emails | ❌ (الـ n8n W1 جاهز، الإيميلات نفسها مش مكتوبة) |
| Lead magnets (PDFs) | ❌ |
| فورم capture عام على الموقع (غير /dart) | ❌ |

### Phase 3 — Conversion Optimization ❌ صفر تقريباً
- يوتيوب → الموقع: **مفيش أي حاجة** (أكبر فرصة غير مستغلة)
- احتكاك الدفع (تقسيط/Apple Pay على Kashier): متشخّص، مش متنفذ (~70-175K EGP/سنة)
- social proof المزيف ("12 مكان فاضل"): متعلّم عليه، محتاج قرار أحمد
- توحيد صفحات فيسبوك الـ 3: ❌

### Phase 4 — Content & Outbound ❌ صفر
بلوج (بوست واحد) · SEO schema (Rank Math موجود مش مفعّل) · B2B/Manara · affiliate — كله NOT STARTED.

### 🤖 خارج الـ roadmap الأصلي (اتضاف واتعمل)
مساعد واتساب "عمر" FULLY LIVE (Evolution v2.3.7 + Gemini + RAG 100% hit@5 + تصعيد Telegram) — ده أصل GTM حقيقي مكنش في الخطة الأصلية.

---

## 3) Website Audit (spec 001) — جرد بالـ Phase

| Phase | الحالة | تفاصيل |
|---|---|---|
| **0 Foundation** | ✅ 100% | Backup 3 طبقات · Sprint 1 PR merged · UI Audit tool · فحص ما-بعد-النقل |
| **1 Revenue Recovery** | 🟡 | 1.8 + 1.10 ✅ · cart recovery **اتفك blocker-ها** (Mautic live) بس مش متنفذة · 67 trashed courses قرار أحمد · كورس 29368 بدون منتج WC |
| **2 Security** | 🟡 9/12 | htaccess/xmlrpc/HSTS/chmod/SMTP-rotation/Tutor 3.9.11 ✅ · الفاضل: **Limit Login + 2FA + re-check SMTP بعد النقل** |
| **3 Perf/Cleanup** | 🟡 6/22 | +10.1GB disk · autoload -70% · indexes ✅ · الفاضل: **HPOS · plugins 61→43 · orphan tables · cron tuning · schema fixes · orphan postmeta** |
| **4 Theme Refactor** | ❌ 0% | functions.php 106KB · parent theme update |
| **5 Future-proofing** | ❌ 0% | CI للـ UI audit · monitoring · staging |

### ⚠️ حاجات بقت stale بعد نقل الاستضافة (2026-06-04)
ملفات `audit-*.md` التلاتة اتكتبوا على السيرفر القديم. الفحص الأساسي بعد النقل عدّى (200/SSL/Pixel/W1)، لكن **مفيش re-audit منهجي** على البيئة الجديدة (PHP config، cron behavior، file permissions sweep، إيميل deliverability من الـ host الجديد). دي أول حاجة في Audit v2.

### 🗑️ بنود بقت obsolete (متتنقلش للخطة الجديدة زي ما هي)
- "Kashier gateway مكسور / 909 فشل CC = 195K" → **اتدحض**. المشكلة الحقيقية: 3DS friction + إلغاءات طبيعية. البديل: تفعيل تقسيط + تحسين UX.
- "الأوردرات processing الـ 662 = مشكلة" → **سليمة**، ده workflow أحمد اليدوي. ممنوع اللمس.
- "Brevo/MailerLite" → اتحسم Mautic.
- "شراء VPS" → خلص من 2026-06-01.

---

## 4) قائمة الانتظار — مين واقف على مين

| البند | مستني مين | بيفتح إيه |
|---|---|---|
| منتج Dart + كوبون DART50 في WC | **أحمد** (قبل 15 يونيو!) | حملة الإطلاق كلها (74+ waitlist) — **البلوكر الوحيد للإطلاق** |
| SES production access (اختياري) | **AWS** (appeal في Phase 002) | سعة أرخص للـ 13K بس — **مش بلوكر** (Brevo بيغطي) |
| موافقة على نصوص الإيميلات 2-5 | **أحمد** | نشر السيكوينس |
| GO لإيميل إعادة التعريف | **Omar** | أول broadcast (بالـ warmup ramp) |
| قرار الـ 67 trashed courses | **أحمد** | تنظيف الكتالوج |
| قرار social proof المزيف | **أحمد** | مصداقية صفحات البيع |
| قرار re-consent للـ 13K | **Omar + أحمد** | الغطاء القانوني للإرسال |
| تقسيط/Apple Pay على Kashier | **أحمد** (إعداد حساب Kashier) | استرداد جزء من الـ 30% إلغاءات |
| YouTube editor access | **أحمد** | تعديل descriptions/CTAs |
| إضافة أحمد لجروب تنبيهات التليجرام | ✅ تم 2026-06-10 | — |

---

## 5) الهيكل المتنفّذ — 2 Tracks منفصلين (اتبنوا 2026-06-11)

**ليه منفصلين:** إيقاع مختلف (GTM أسبوعي تسويقي vs Audit تقني دفعات)، stakeholders مختلفين (أحمد بيراجع GTM، الـ audit شغل داخلي)، و-blockers مختلفة. الداشبورد الموحّد: [`../ROADMAP.md`](../ROADMAP.md).

### Track 1 — GTM (`specs/`)
- **001-launch-unblock** 🔄 ACTIVE — إطلاق Dart 15 يونيو (البلوكر = منتج أحمد؛ القناة جاهزة على Brevo)
- **002-email-engine** 📋 — broadcast الـ 13K (Brevo) + SES appeal + sequences + lead magnets
- **003-youtube-funnel** 📋 — استغلال الـ 350K مشترك
- **004-cro-tracking** 📋 — سد نزيف الـ 30% + GA4 events + Clarity
- **005-content-outbound** 📋 — SEO + بلوج + B2B/Manara + affiliate

### Track 2 — Website Audit (`01_WEB/specs/`)
- **001-bug-remediation-90day** 🟡 — legacy (20/113)، اتجمّد
- **002-website-audit-v2** 📋 — re-audit بعد النقل + قفل security/perf/theme المتبقي

**الخطوة الجاية:** مراجعة Phase 001 في plannotator → APPROVED في ROADMAP → تنفيذ.
