---
tags:
  - setup
  - integrations
  - learn-simply
type: Platform Setup Guide
created: 2026-05-20
owner: Omar
status: in-progress
---

# Learn Simply AIOS — Platform Integrations Setup

> **الهدف:** ربط Claude/AIOS بكل قنوات Ahmed Adel عشان نقدر نـ pull الـ data ونـ analyze ونـ build على.
>
> **الـ Principle:** خطوة خطوة. متحاولش تعمل كل حاجة في يوم. كل platform تتأكد إنها شغّالة قبل ما تنتقل للتالية.

---

## 📋 Quick Reference — الترتيب التنفيذي

```
1. YouTube      ← ابدأ هنا (Ahmed وافق + أعلى قيمة + transcripts)
2. GitHub       ← أسهل setup (5 دقايق)
3. Facebook     ← الـ App الواحد هيخدم FB + IG
4. Instagram    ← بعد ما الـ FB App جاهز
5. TikTok       ← أجلها (Ahmed مش active)
```

**Total setup time:** ~1 ساعة شغل من Omar + 10 دقايق من Ahmed (موزعين على 3-4 أيام).

---

## 1️⃣ YouTube — الأولوية القصوى ⭐

### 1.1 Status check

- [x] Ahmed بعت Editor (limited) invite لـ `omarabdo385@gmail.com`
- [ ] Omar قبل الـ invite
- [ ] Google Cloud project مفعّل
- [ ] OAuth credentials جاهزة

### 1.2 خطوات قبول الـ Invite (Omar)

**1.** افتح incognito window (مهم — عشان متبقاش loggedin بـ accounts تانية)

**2.** روح على: `https://studio.youtube.com`

**3.** سجّل دخول بـ `omarabdo385@gmail.com`

**4.** أول ما تدخل، **هيظهر banner أو popup** بيقول: *"You've been invited to manage اتعلم ببساطة"* → اضغط **Accept**

**لو الـ banner مظهرش:**
- اضغط على صورة البروفايل فوق على اليمين → Switch account
- المفروض تشوف "اتعلم ببساطة" مع زر Accept
- لو لسه مش ظاهرة: روح على `https://myaccount.google.com/brandaccounts`

### 1.3 Google Cloud OAuth Setup (Omar — 15 دقيقة)

**1.** افتح `https://console.cloud.google.com/` بـ نفس الإيميل `omarabdo385@gmail.com`

**2.** اعمل project جديد:
- أعلى الصفحة → "Select a project" → "New Project"
- Name: `learn-simply-aios`
- Create → استنى 30 ثانية

**3.** فعّل الـ APIs الـ 2 (في الـ search bar فوق):
- `YouTube Data API v3` → Enable
- `YouTube Analytics API` → Enable

**4.** اعمل OAuth Credentials:
- APIs & Services → Credentials → Create Credentials → **OAuth client ID**

**5.** أول مرة هيطلب منك تعمل consent screen:
- User Type: **External**
- App name: `Learn Simply AIOS`
- User support email: `omarabdo385@gmail.com`
- Developer email: نفسه
- Scopes: skip للآن (هنضيفهم في الكود)
- Test users: ضيف `omarabdo385@gmail.com`

**6.** ارجع لـ Credentials → Create OAuth client ID:
- Application type: **Desktop app**
- Name: `LS-AIOS-Desktop`
- Create → هينزّل ملف `client_secret_xxxx.json`

**7.** احفظ الملف في:
```
brands/learn-simply/credentials/youtube_client_secret.json
```

(لو الـ folder `credentials/` مش موجود، اعمله. وضيفه لـ `.gitignore`.)

### 1.4 اللي محتاجه من Ahmed

✅ **صفر.** خلّص دوره بالـ Editor invite. الـ API بيشتغل بصلاحياتك انت.

### 1.5 اللي بناخده من YouTube

- Channel stats (subs timeline, total views, watch time)
- كل الفيديوهات (titles, descriptions, tags, duration, publish dates)
- Per-video analytics (views, likes, comments, CTR, retention, traffic sources)
- Audience demographics (عمر، جنس، جغرافيا)
- Top performing videos patterns
- **Transcripts** (تفاصيل في section خاص تحت)

---

## 🎤 الـ Transcripts — Voice Profile Foundation

ده الـ killer feature. خلّيه أولوية بعد ما الـ YouTube API يشتغل.

### 2.1 الـ Strategy (مجانية بالكامل)

**Layer 1: youtube-transcript-api** (Python library)
- بيـ pull auto-generated captions من YouTube
- **مفيش OAuth، مفيش API key، صفر setup**
- شغّال على أي فيديو public عليه captions
- **متوقع:** 80-90% من فيديوهات Ahmed عليها auto-captions عربية

**Layer 2: yt-dlp + Groq Whisper** (للفيديوهات اللي مفيهاش captions)
- `yt-dlp` بيـ extract الـ audio من الفيديو كـ MP3
- نبعت الـ MP3 لـ Groq Whisper Large v3 (مجاني، 2K request/يوم)
- 20 دقيقة فيديو = ~5 ثوان transcription

### 2.2 الـ Output Format

ملف JSON لكل فيديو في `brands/learn-simply/transcripts/`:

```json
{
  "video_id": "abc123",
  "title": "تعلم Flutter من الصفر",
  "publish_date": "2025-03-15",
  "duration_seconds": 1247,
  "views": 45678,
  "engagement_rate": 0.045,
  "transcript_source": "youtube-captions",
  "transcript_text": "أهلا بيكم في حلقة جديدة...",
  "transcript_with_timestamps": [
    {"start": 0.0, "duration": 3.2, "text": "أهلا بيكم"},
    ...
  ]
}
```

### 2.3 اللي محتاجه من Ahmed (للـ Scripts المكتوبة)

اطلب منه يبعت أي scripts كان كتبها قبل كده — أي format:
- Google Docs links
- Notion pages
- Word/Pages files
- نصوص في ملاحظات الموبايل (screenshot + OCR)

**Minimum مفيد:** 5-10 scripts. **Ideal:** 20+.

كل ما يبعت أكتر، الـ voice profile يكون أدق.

### 2.4 الـ Voice Profile Output

Claude هياخد كل الـ transcripts + scripts ويـ extract:
- **Vocabulary:** الكلمات اللي بيكررها (frequency analysis)
- **Hooks:** إزاي بيبدأ الفيديوهات (أول 30 ثانية)
- **Transitions:** إزاي بينتقل بين الـ sections ("طب ده يقودنا لـ...")
- **Signoffs:** إزاي بيقفل
- **Technical depth patterns:** متى بيدخل في detail متى بيلخص
- **Catchphrases:** التعبيرات المميزة

الـ Output: ملف `brands/learn-simply/voice-profile.md` يتـ inject في system prompt لكل script generation.

---

## 3️⃣ GitHub — أسهل setup (5 دقايق)

### 3.1 Status check

- [ ] Omar طلب من Ahmed يضيفه collaborator
- [ ] Ahmed بعت invite
- [ ] Omar قبل الـ invite

### 3.2 اللي محتاجه من Ahmed

اطلب منه:

> "ضيفني collaborator على repo الموقع. Username: **omarabdo516**"

**خطواته:**
1. يفتح الـ repo على GitHub (مثلاً `github.com/ahmedlearnSimply/learn-simply-website`)
2. Settings → Collaborators → Add people
3. يكتب: `omarabdo516`
4. Send invite

### 3.3 اللي محتاجه من Omar

- إيميل invite هييجي على `omarabdo385@gmail.com`
- اضغط "Accept invitation"
- Clone الـ repo:
  ```bash
  cd c:/Users/PUZZLE/Documents/Claude/brands/learn-simply/website
  git clone <repo-url> wordpress-code
  ```

### 3.4 Read access فقط — كفاية للتحليل

دلوقتي مش محتاجين write access. لو احتجناه بعدين للـ publish automation:
- Ahmed يعمل Personal Access Token بـ `repo` scope
- ويبعتها بشكل آمن (1Password share أو similar)

### 3.5 اللي بناخده من GitHub

- نوع الـ setup (theme/plugins على Git؟ أم headless؟)
- الـ deploy mechanism (SFTP؟ GitHub Actions؟ webhook لـ Hostinger؟)
- أي custom WordPress code (`functions.php`, custom post types, EduBlink integrations)
- Commit history (إزاي هو بيشتغل، كل قد إيه بيـ update)
- Issues/PRs (لو في bugs معروفة)

---

## 4️⃣ Facebook — الـ App ده هيخدم IG برضه

### 4.1 Status check

- [ ] Meta Developer App مفعّل
- [ ] Ahmed ضاف Omar admin على Page
- [ ] Long-Lived Page Access Token مولّد
- [ ] Permissions approved (auto للأذونات الأساسية)

### 4.2 اللي محتاجه من Omar — 20 دقيقة

**1.** افتح `https://developers.facebook.com/` وسجّل دخول بـ Facebook account (يفضّل حسابك الشخصي عشان تبقى الـ App owner)

**2.** اعمل App:
- My Apps → Create App
- Use case: **Other**
- App type: **Business**
- App name: `Learn Simply AIOS`
- Contact email: `omarabdo385@gmail.com`

**3.** Add Products:
- Facebook Login → Set Up
- Instagram Graph API → Set Up (للخطوة 5)
- Pages API (موجودة under Facebook Login)

**4.** احفظ الـ App credentials في:
```
brands/learn-simply/credentials/meta_app.env
```
بـ format:
```env
META_APP_ID=xxx
META_APP_SECRET=xxx
```

### 4.3 اللي محتاجه من Ahmed — 5 دقايق

اطلب منه:

> "ضيفني Admin على Facebook Page بتاعة 'اتعلم ببساطه' (et3lmbbsata)"

**خطواته:**
1. Meta Business Suite → Settings → Page Roles (أو Facebook Page → Settings → Page roles)
2. Add → يكتب اسمك على Facebook أو إيميلك
3. Role: **Admin** (مش Editor — Admin مهم للـ Insights API)

### 4.4 بعد ما Ahmed يقبل invite

**5.** اطلع Page Access Token:
- افتح Graph API Explorer: `https://developers.facebook.com/tools/explorer`
- Application: اختار `Learn Simply AIOS`
- User or Page: اختار **Page** → "اتعلم ببساطه"
- Generate Token
- اضغط على الـ "i" جنب الـ token → "Open in Access Token Tool"
- Convert لـ **Long-Lived Token** (60 يوم بدل ساعة)

**⚠️ Note:** كل 60 يوم لازم نـ regenerate (أو نـ automate refresh script).

**6.** Request permissions (في App Review → Permissions and Features):
- `pages_show_list` (auto-approved)
- `pages_read_engagement` (auto-approved)
- `read_insights` (auto-approved للـ Pages اللي انت admin عليها)

### 4.5 اللي بناخده من Facebook

- Page insights (likes growth, reach, engagement rate)
- Post performance (views, reactions, shares, comments per post)
- Audience demographics
- Traffic to website
- Video metrics (لو نشر فيديوهات على FB)

---

## 5️⃣ Instagram — يبني فوق Facebook App

### 5.1 Status check

- [ ] Omar سأل Ahmed عن نوع الـ IG account
- [ ] Ahmed حوّل لـ Creator/Business لو كان Personal
- [ ] الـ IG account متربط بـ FB Page
- [ ] Instagram Graph API permissions مفعّلة
- [ ] Instagram Business Account ID محفوظ

### 5.2 الـ Pre-flight check (أهم سؤال)

**اسأل Ahmed:**

> "حساب Instagram بتاعك `@ahmed.aaddel` نوعه إيه؟ (Personal / Creator / Business)؟"

**كيف يعرف:**
Instagram app → Profile → Menu → Settings → Account → Account type & tools

### 5.3 السيناريوهات

#### السيناريو A: Creator أو Business بالفعل ✅
- ⏭️ كمل للخطوة 5.4 مباشرة
- لازم يكون متربط بـ Facebook Page "اتعلم ببساطه"

#### السيناريو B: Personal ⚠️
**اطلب منه يحوّل لـ Creator (مجاناً، 30 ثانية):**

1. Settings → Account → Switch to Professional Account
2. Category: **Education** أو **Software Developer**
3. Type: **Creator** (مش Business — Creator أنسب للـ individual content creators)
4. Link to Facebook Page: اختار "اتعلم ببساطه" (et3lmbbsata)

**ليه ده مهم:**
- بدون Switch → **مفيش API access إطلاقاً**، هنحتاج Instaloader scraping (أبطأ + risky)
- مع Switch → Instagram Graph API مجاناً + كل الـ analytics + ability to publish via API لاحقاً

### 5.4 اللي محتاجه من Omar — 10 دقايق

**1.** في نفس الـ FB App:
- Add Product → Instagram Graph API → Set Up

**2.** Permissions الإضافية:
- `instagram_basic`
- `instagram_manage_insights`
- `pages_show_list` (موجودة من FB)

**3.** اطلع الـ Instagram Business Account ID:
- في Graph API Explorer:
- `GET /me/accounts` → هيرجعلك الـ pages
- `GET /{page-id}?fields=instagram_business_account`
- هيرجعلك Instagram Business Account ID → احفظه في:
```
brands/learn-simply/credentials/meta_app.env
```
بـ:
```env
IG_BUSINESS_ACCOUNT_ID=xxx
```

### 5.5 اللي بناخده من Instagram

- Followers timeline
- Posts performance (likes, comments, saves, shares, reach)
- Stories metrics (لو بينشر)
- Reels metrics
- Audience demographics
- Profile views & website clicks

---

## 6️⃣ TikTok — أجلها مؤقتاً ⏸️

**القرار:** Skip لـ Phase 0.

**ليه:**
- Ahmed مش active على TikTok (الـ handle موجود، صفر engagement حقيقي)
- TikTok API approval بتاخد أسابيع
- الـ Display API محدودة
- الـ Research API بـ academic only

**Phase 2 (لو قررنا نـ activate):** نتكلم بعدين عن:
- TikTok for Developers application
- أو scraping عبر open-source tools لو احتجنا analytics

---

## 📋 الـ Master Checklist

### اللي على Omar مباشرة (مش محتاج Ahmed)

- [ ] اقبل YouTube Editor invite
- [ ] اعمل Google Cloud project + YouTube APIs OAuth
- [ ] اعمل Meta Developer App
- [ ] جهّز folder structure:
  ```
  brands/learn-simply/
    ├── credentials/         ← gitignored
    │   ├── youtube_client_secret.json
    │   ├── youtube_token.json (يتولد بعد first auth)
    │   └── meta_app.env
    ├── transcripts/         ← مش gitignored بس large files
    ├── scripts/             ← scripts Ahmed كتبها
    ├── voice-profile.md     ← الـ output النهائي
    └── website/
        └── wordpress-code/  ← cloned repo
  ```

### اللي محتاج Ahmed يعمله

ابعتله الرسالة دي:

> أهلاً يا Ahmed، عشان نبدأ شغل التحليل، محتاج منك 3 حاجات صغيرة:
>
> **1. GitHub:**
> ضيفني collaborator على repo الموقع.
> Username: `omarabdo516`
>
> **2. Facebook Page (اتعلم ببساطه):**
> ضيفني Admin على الـ Page عشان أقدر أوصل للـ Insights API.
> اسمي/إيميلي: [حط اسمك على FB أو الإيميل]
>
> **3. Instagram (@ahmed.aaddel):**
> حسابك نوعه إيه؟ Personal، Creator، ولا Business؟
>
> لو Personal، حوّله لـ Creator (مجاناً، 30 ثانية):
> Settings → Account → Switch to Professional Account → Creator → اربطه بـ Facebook Page "اتعلم ببساطه".
>
> **بونص لو متاح:**
> أي scripts كتبتها لفيديوهات قديمة (Google Docs، Notion، Word، أي format). كل ما تبعت أكتر، الـ AI يقدر يكتب بصوتك أحسن.
>
> ⏱️ الـ 3 خطوات مع بعض = 7-10 دقايق منك.

### Order التنفيذي (week 1)

| اليوم | المهمة | اللي بيتم |
|---|---|---|
| **Day 1** | YouTube OAuth setup + Google Cloud project | Omar يقدر يـ pull channel data |
| **Day 2** | Ahmed يضيفك على GitHub + FB Page + IG switch | invitations pending |
| **Day 3** | اقبل invites + setup Meta App permissions | كل الـ APIs ready |
| **Day 4** | ابدأ pull transcripts (youtube-transcript-api) | أول 100 transcript جاهزين |
| **Week 2** | Voice profile analysis + first script generation test | proof of concept |

---

## 🔐 الـ Security Reminders

- **مش حاجة من الـ credentials تتـ commit على Git.** Add to `.gitignore`:
  ```
  brands/learn-simply/credentials/
  *.env
  *_secret.json
  *_token.json
  ```

- **Page Access Tokens بتنتهي كل 60 يوم.** ضع reminder كل شهر للـ regenerate.

- **OAuth refresh tokens بتموت لو الـ App في "Testing" mode بعد 7 أيام.** بعد ما تشتغل، حرّك الـ Google Cloud Consent Screen لـ "In production" (مش بيحتاج verification لو الـ usage محدود).

- **مفيش tokens على شات.** أي token يتـ paste في chat = compromised فوراً → regenerate.

---

## 🔄 الـ Refresh Schedule

| الـ Token / Permission | الـ Lifetime | الـ Action |
|---|---|---|
| Google OAuth refresh token | Forever (لو الـ App Production) | لا حاجة |
| Google OAuth refresh token | 7 أيام (لو Testing mode) | حرّك الـ App لـ Production |
| FB Page Access Token | 60 يوم | Regenerate من Graph API Explorer |
| Instagram permissions | مع الـ Page Token | نفس الـ Page refresh |
| GitHub Collaborator | Forever | لا حاجة |

---

## 📞 Troubleshooting

### YouTube
- **"Access blocked: This app's request is invalid"** → الـ Consent Screen مش مكتمل، رجع أكمله
- **403 Forbidden على Analytics API** → اتأكد إنك Editor (limited) على الـ channel وإن Analytics API مفعّلة
- **Quota exceeded** → الـ default 10K units/يوم. الـ `videos.list` بـ 1 unit، `search.list` بـ 100. اتجنب الـ search

### Facebook
- **"This object does not exist"** → الـ Page ID غلط، استخدم Graph API Explorer للـ verify
- **Token expired** → Regenerate Long-Lived Token من الـ Access Token Tool
- **Missing permissions** → اتأكد إنك Admin (مش Editor)

### Instagram
- **"Invalid OAuth access token"** → الـ token مش متربط بـ Instagram Business Account
- **مفيش insights data** → الـ account لازم يكون Creator/Business + المتابعين > 100

### GitHub
- **Invite expired** → اطلب من Ahmed يبعت تاني
- **Can't clone** → اتأكد إنك accepted invite + الـ git authenticated محلياً

---

## 📚 Sources & References

- [YouTube Data API v3 Docs](https://developers.google.com/youtube/v3)
- [YouTube Analytics API Docs](https://developers.google.com/youtube/analytics)
- [Facebook Graph API Docs](https://developers.facebook.com/docs/graph-api)
- [Instagram Graph API Docs](https://developers.facebook.com/docs/instagram-api)
- [Groq Whisper API Docs](https://console.groq.com/docs/speech-to-text)
- [youtube-transcript-api GitHub](https://github.com/jdepoix/youtube-transcript-api)
- [yt-dlp GitHub](https://github.com/yt-dlp/yt-dlp)

---

**Last updated:** 2026-05-20
**Next review:** بعد ما الـ 5 platforms كلهم working + أول voice profile generated
