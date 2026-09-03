<?php
/**
 * Plugin Name: Learn Simply — Dart Waitlist Popup
 * Description: On-site popup that collects waitlist emails for the upcoming "Dart من الصفر" course. Posts SERVER-SIDE (n8n token hidden from the browser) to the W2 n8n workflow → Mautic tag `dart-waitlist` + segment 10.
 * Version: 1.0.0
 * Author: GrowthMora (Omar) for اتعلم ببساطة
 *
 * ── DEPLOY ───────────────────────────────────────────────────────────────────
 *   Copy this file to:  wp-content/mu-plugins/learnsimply-dart-popup.php
 *   mu-plugins load automatically — no activation, can't be deactivated by accident.
 *   (If wp-content/mu-plugins/ does not exist, create it.)
 *
 * ── SECURITY MODEL ───────────────────────────────────────────────────────────
 *   The browser NEVER sees the n8n webhook secret token. The popup posts to a
 *   same-origin WP REST route (/wp-json/learnsimply/v1/dart-waitlist). WordPress
 *   then forwards server-side to n8n WITH the secret. Gates:
 *     1. Honeypot (`website` field) relayed to n8n — bots fill it → silent skip.
 *     2. Per-IP rate limit + global hourly circuit breaker (caps abuse blast radius).
 *     3. Server-side email validation (fail fast before calling n8n).
 *     4. Same-origin route (no CORS headers) — naive cross-site POSTs can't read the reply.
 *   Worst case if abused: a contact tagged `dart-waitlist` that is NEVER emailed
 *   until Omar publishes the Mautic campaign. No PII exposure, no site write.
 *
 * ── W2 CONTRACT ──────────────────────────────────────────────────────────────
 *   See 02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md
 *   n8n returns: 200 {success:true} on accept · 400 {success:false,message} on bad email.
 */

if (!defined('ABSPATH')) {
    exit; // No direct access.
}

/* ─────────────────────────── CONFIG (edit here) ─────────────────────────── */

// n8n webhook (carries the shared-secret path token). To keep it OUT of this file,
// define LS_DART_WEBHOOK_URL in wp-config.php and the inline fallback is ignored.
if (!defined('LS_DART_WEBHOOK_URL')) {
    define('LS_DART_WEBHOOK_URL', 'https://n8n.learrnsimply.com/webhook/dart-waitlist-97ae34dfa856');
}

// Master kill switch — set to false to hide the popup everywhere instantly.
if (!defined('LS_DART_ENABLED')) {
    define('LS_DART_ENABLED', true);
}

// Working assumption — popup stops rendering AFTER this date (launch day). Ahmed confirms.
if (!defined('LS_DART_LAUNCH_DATE')) {
    define('LS_DART_LAUNCH_DATE', '2026-06-15');
}

// Media Library attachment ID for the popup banner (768×512). Per W2 doc. 0 = no image.
if (!defined('LS_DART_BANNER_ID')) {
    define('LS_DART_BANNER_ID', 39310);
}

// Trigger timing.
if (!defined('LS_DART_DELAY_MS'))   { define('LS_DART_DELAY_MS', 12000); } // show after 12s …
if (!defined('LS_DART_SCROLL_PCT')) { define('LS_DART_SCROLL_PCT', 15);  } // … or 15% scroll, whichever first

// Abuse limits.
if (!defined('LS_DART_IP_MAX'))     { define('LS_DART_IP_MAX', 5);    }  // max submits per IP …
if (!defined('LS_DART_IP_WINDOW'))  { define('LS_DART_IP_WINDOW', 600); } // … per 10 min
if (!defined('LS_DART_HOUR_MAX'))   { define('LS_DART_HOUR_MAX', 300); }  // global cap per hour (blast-radius brake)

// Is the site behind Cloudflare? false on current Hostinger shared host (no CF in front)
// → trust REMOTE_ADDR for rate limiting, NOT spoofable X-Forwarded-For / CF headers.
if (!defined('LS_DART_BEHIND_CF'))  { define('LS_DART_BEHIND_CF', false); }

/* ───────────────────────────── REST endpoint ───────────────────────────── */

add_action('rest_api_init', function () {
    register_rest_route('learnsimply/v1', '/dart-waitlist', [
        'methods'             => 'POST',
        'callback'            => 'ls_dart_handle_signup',
        'permission_callback' => '__return_true', // public; abuse gates enforced inside
    ]);
});

/**
 * Resolve the client IP for rate limiting.
 *
 * On Hostinger shared hosting there is NO trusted reverse proxy in front, so
 * REMOTE_ADDR is the real client IP. We must NOT trust X-Forwarded-For /
 * CF-Connecting-IP here — a client can spoof those headers and rotate the
 * per-IP rate limit at will. Only honour CF if the site is genuinely behind
 * Cloudflare (set LS_DART_BEHIND_CF = true in that case).
 */
function ls_dart_client_ip() {
    if (defined('LS_DART_BEHIND_CF') && LS_DART_BEHIND_CF && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $cf = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Per-IP + global hourly rate limit. Returns true if the request is allowed.
 */
function ls_dart_rate_ok($ip) {
    // Global hourly circuit breaker.
    $hour_key   = 'ls_dart_hour_' . gmdate('YmdH');
    $hour_count = (int) get_transient($hour_key);
    if ($hour_count >= LS_DART_HOUR_MAX) {
        return false;
    }

    // Per-IP window.
    $ip_key   = 'ls_dart_ip_' . md5($ip);
    $ip_count = (int) get_transient($ip_key);
    if ($ip_count >= LS_DART_IP_MAX) {
        return false;
    }

    set_transient($ip_key, $ip_count + 1, LS_DART_IP_WINDOW);
    set_transient($hour_key, $hour_count + 1, HOUR_IN_SECONDS);
    return true;
}

/**
 * Handle a popup submission: validate, then forward server-side to n8n.
 */
function ls_dart_handle_signup(WP_REST_Request $request) {
    $params    = $request->get_json_params();
    $email     = isset($params['email'])     ? sanitize_email((string) $params['email']) : '';
    $firstname = isset($params['firstname']) ? sanitize_text_field((string) $params['firstname']) : '';
    $honeypot  = isset($params['website'])   ? (string) $params['website'] : '';

    // Honeypot: bots fill it. Return a success-looking reply, do nothing.
    if ($honeypot !== '') {
        return new WP_REST_Response(['success' => true], 200);
    }

    // Email must be valid before we spend an n8n call.
    if ($email === '' || !is_email($email)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'الإيميل مش مكتوب صح — راجعه وحاول تاني.',
        ], 400);
    }

    // Abuse gates.
    $ip = ls_dart_client_ip();
    if (!ls_dart_rate_ok($ip)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'جرّبت كذا مرة بسرعة — استنى شوية وحاول تاني.',
        ], 429);
    }

    // Forward server-side to n8n (token stays here, never in the browser).
    $response = wp_remote_post(LS_DART_WEBHOOK_URL, [
        'timeout' => 12,
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode([
            'email'     => $email,
            'firstname' => $firstname,
            'website'   => '', // honeypot already passed locally
        ]),
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'في مشكلة مؤقتة عندنا — جرّب كمان شوية.',
        ], 502);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code >= 200 && $code < 300 && !empty($body['success'])) {
        return new WP_REST_Response(['success' => true], 200);
    }

    // n8n rejected (e.g. bad email it caught) — relay a friendly inline error.
    // sanitize_text_field on n8n's message: never trust an upstream response blindly.
    return new WP_REST_Response([
        'success' => false,
        'message' => !empty($body['message'])
            ? sanitize_text_field((string) $body['message'])
            : 'مقدرناش نسجّلك دلوقتي — جرّب تاني.',
    ], 400);
}

/* ─────────────────────────── Front-end render ───────────────────────────── */

/**
 * Should the popup render on this request?
 */
function ls_dart_should_render() {
    if (!LS_DART_ENABLED) {
        return false;
    }
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }
    // Past launch day → stop showing the waitlist popup.
    if (current_time('Y-m-d') > LS_DART_LAUNCH_DATE) {
        return false;
    }
    // Never disturb the purchase flow.
    if (function_exists('is_cart') && (is_cart() || is_checkout())) {
        return false;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }
    // Don't show the popup on the dedicated /dart landing page — it already has the form.
    if (function_exists('is_page') && is_page('dart')) {
        return false;
    }
    return true;
}

add_action('wp_footer', 'ls_dart_render_popup', 99);

function ls_dart_render_popup() {
    if (!ls_dart_should_render()) {
        return;
    }

    $rest_url   = esc_url_raw(rest_url('learnsimply/v1/dart-waitlist'));
    $banner_url = LS_DART_BANNER_ID ? wp_get_attachment_image_url(LS_DART_BANNER_ID, 'large') : '';

    $config = wp_json_encode([
        'endpoint'  => $rest_url,
        'delayMs'   => (int) LS_DART_DELAY_MS,
        'scrollPct' => (int) LS_DART_SCROLL_PCT,
    ]);
    ?>
    <div id="ls-dart-pop" class="ls-dart-pop" role="dialog" aria-modal="true"
         aria-labelledby="ls-dart-title" aria-describedby="ls-dart-sub" dir="rtl" hidden>
      <div class="ls-dart-overlay" data-ls-dart-close></div>
      <div class="ls-dart-card" role="document">
        <button type="button" class="ls-dart-x" data-ls-dart-close aria-label="إغلاق">&times;</button>

        <?php if ($banner_url) : ?>
          <div class="ls-dart-banner">
            <img src="<?php echo esc_url($banner_url); ?>" alt="كورس Dart من الصفر — اتعلم ببساطة"
                 width="768" height="512" loading="lazy" decoding="async" />
          </div>
        <?php endif; ?>

        <div class="ls-dart-body">
          <h2 id="ls-dart-title" class="ls-dart-title">احجز مكانك في <strong>قايمة الانتظار</strong></h2>
          <p id="ls-dart-sub" class="ls-dart-sub">
            سيب إيميلك دلوقتي واحجز <strong>خصم الإطلاق 50%</strong> — هنبعتهولك أول ما كورس Dart ينزل، قبل أي حد.
          </p>

          <form class="ls-dart-form" novalidate>
            <input type="email" name="email" class="ls-dart-input" required
                   placeholder="اكتب إيميلك هنا" autocomplete="email" inputmode="email" />

            <!-- Honeypot: hidden from humans, bots fill it → silently skipped. -->
            <input type="text" name="website" class="ls-dart-hp" tabindex="-1"
                   autocomplete="off" aria-hidden="true" />

            <button type="submit" class="ls-dart-btn">احجز خصمي دلوقتي 🚀</button>
            <p class="ls-dart-msg" role="alert" hidden></p>
            <p class="ls-dart-fine">إيميل واحد بس عند الإطلاق — مفيش إزعاج 🤍</p>
          </form>

          <div class="ls-dart-done" hidden>
            <p class="ls-dart-done-emoji">🎉</p>
            <h3>تم تسجيلك!</h3>
            <p>هنبعتلك أول ما الكورس ينزل + خصم الإطلاق. استنى مني إيميل قريب.</p>
            <button type="button" class="ls-dart-btn ls-dart-btn-ghost" data-ls-dart-close>تمام</button>
          </div>
        </div>
      </div>
    </div>

    <style>
      #ls-dart-pop.ls-dart-pop{position:fixed;inset:0;z-index:999999;display:flex;align-items:center;
        justify-content:center;padding:16px;font-family:'Cairo',system-ui,sans-serif;direction:rtl;box-sizing:border-box}
      #ls-dart-pop.ls-dart-pop *{box-sizing:border-box}
      #ls-dart-pop.ls-dart-pop[hidden]{display:none}
      #ls-dart-pop .ls-dart-overlay{position:absolute;inset:0;background:rgba(12,15,24,.7);
        backdrop-filter:blur(3px);opacity:0;transition:opacity .28s ease}
      #ls-dart-pop.is-open .ls-dart-overlay{opacity:1}
      #ls-dart-pop .ls-dart-card{position:relative;width:100%;max-width:452px;background:#fff;
        border-radius:22px;overflow:hidden;box-shadow:0 24px 70px rgba(12,15,24,.45);
        transform:translateY(16px) scale(.98);opacity:0;
        transition:transform .32s cubic-bezier(.16,1,.3,1),opacity .32s ease}
      #ls-dart-pop.is-open .ls-dart-card{transform:translateY(0) scale(1);opacity:1}
      #ls-dart-pop .ls-dart-x{position:absolute;top:10px;left:10px;z-index:2;width:34px;height:34px;
        border:0;border-radius:50%;background:rgba(255,255,255,.92);color:#1c2230;
        font-size:22px;line-height:1;cursor:pointer;padding:0;box-shadow:0 2px 8px rgba(0,0,0,.18);
        transition:background .2s,transform .2s}
      #ls-dart-pop .ls-dart-x:hover{background:#fff;transform:rotate(90deg)}
      #ls-dart-pop .ls-dart-banner{aspect-ratio:768/512;background:#0f121c;overflow:hidden}
      #ls-dart-pop .ls-dart-banner img{width:100%;height:100%;object-fit:cover;display:block}
      #ls-dart-pop .ls-dart-body{padding:20px 24px 24px;text-align:center}
      #ls-dart-pop .ls-dart-title{margin:0 0 8px;font-size:24.8px;line-height:1.4;color:#161b26;font-weight:800}
      #ls-dart-pop .ls-dart-title strong{color:#7c4dff}
      #ls-dart-pop .ls-dart-sub{margin:0 0 18px;font-size:16.8px;line-height:1.7;color:#5a6273}
      #ls-dart-pop .ls-dart-sub strong{color:#161b26;font-weight:700}
      #ls-dart-pop .ls-dart-form{display:flex;flex-direction:column;gap:11px;margin:0}
      #ls-dart-pop .ls-dart-input{width:100%;padding:14px 16px;border:1.5px solid #e4e7ef !important;
        border-radius:12px;font-family:'Cairo',system-ui,sans-serif !important;line-height:1.4;
        color:#161b26 !important;background:#fbfbfd !important;text-align:center;box-shadow:none;font-size:16.8px;
        transition:border-color .2s,background .2s,box-shadow .2s}
      #ls-dart-pop .ls-dart-input:focus{outline:0;border-color:#7c4dff !important;background:#fff !important;
        box-shadow:0 0 0 3px rgba(124,77,255,.13)}
      #ls-dart-pop .ls-dart-input::placeholder{color:#9aa1af;opacity:1}
      #ls-dart-pop .ls-dart-hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none}
      #ls-dart-pop .ls-dart-btn{margin:4px 0 0;width:100%;padding:15px 16px;border:0 !important;border-radius:12px;
        cursor:pointer;font-family:'Cairo',system-ui,sans-serif !important;font-size:18.6px;font-weight:800;
        letter-spacing:0;color:#fff !important;text-shadow:none !important;
        background:linear-gradient(135deg,#8a5bff,#5a32d6) !important;background-color:#7c4dff !important;
        box-shadow:0 10px 24px rgba(124,77,255,.45);transition:transform .18s,box-shadow .18s,opacity .2s}
      #ls-dart-pop .ls-dart-btn:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(124,77,255,.55)}
      #ls-dart-pop .ls-dart-btn:active{transform:translateY(0)}
      #ls-dart-pop .ls-dart-btn[disabled]{opacity:.65;cursor:default;transform:none;box-shadow:none}
      #ls-dart-pop .ls-dart-btn-ghost{background:#161b26 !important;background-color:#161b26 !important;box-shadow:none;margin-top:14px}
      #ls-dart-pop .ls-dart-msg{margin:2px 0 0;font-size:14.7px;color:#d23b54;font-weight:600}
      #ls-dart-pop .ls-dart-fine{margin:9px 0 0;font-size:14.1px;color:#9aa1af;text-align:center}
      #ls-dart-pop .ls-dart-done{text-align:center;padding:8px 0}
      #ls-dart-pop .ls-dart-done-emoji{font-size:41.6px;margin:0 0 6px}
      #ls-dart-pop .ls-dart-done h3{margin:0 0 8px;font-size:23.2px;color:#161b26;font-weight:800}
      #ls-dart-pop .ls-dart-done p{margin:0;font-size:16.3px;line-height:1.7;color:#5a6273}
      @media (max-width:480px){#ls-dart-pop .ls-dart-title{font-size:22.1px}#ls-dart-pop .ls-dart-body{padding:18px}}
      @media (prefers-reduced-motion:reduce){
        #ls-dart-pop .ls-dart-overlay,#ls-dart-pop .ls-dart-card{transition:none}
        #ls-dart-pop .ls-dart-card{transform:none}
      }
    </style>

    <script>
    (function () {
      var CFG = <?php echo $config; // already JSON-encoded ?>;
      var SEEN_KEY = 'ls_dart_dismissed_at', DONE_KEY = 'ls_dart_signed';
      var DISMISS_DAYS = 2;
      var pop = document.getElementById('ls-dart-pop');
      if (!pop) return;

      function store(k){ try { return window.localStorage.getItem(k); } catch(e){ return null; } }
      function save(k,v){ try { window.localStorage.setItem(k,v); } catch(e){} }

      function recentlyDismissed(){
        var ts = parseInt(store(SEEN_KEY) || '0', 10);
        if (!ts) return false;
        return (Date.now() - ts) < DISMISS_DAYS * 864e5;
      }
      if (store(DONE_KEY) || recentlyDismissed()) return;

      var lastFocus = null;
      function open(){
        if (!pop.hasAttribute('hidden')) return;
        lastFocus = document.activeElement;
        pop.removeAttribute('hidden');
        // next frame → trigger CSS transition
        requestAnimationFrame(function(){ pop.classList.add('is-open'); });
        var first = pop.querySelector('.ls-dart-input[name="email"]');
        if (first) setTimeout(function(){ first.focus(); }, 320);
        document.addEventListener('keydown', onKey);
        teardownTriggers();
      }
      function close(){
        pop.classList.remove('is-open');
        document.removeEventListener('keydown', onKey);
        save(SEEN_KEY, String(Date.now()));
        setTimeout(function(){ pop.setAttribute('hidden',''); }, 320);
        if (lastFocus && lastFocus.focus) lastFocus.focus();
      }
      function onKey(e){ if (e.key === 'Escape') close(); }

      // Close handlers
      pop.querySelectorAll('[data-ls-dart-close]').forEach(function(el){
        el.addEventListener('click', close);
      });

      // ── Trigger: timer OR scroll %, whichever first ──
      var timer = setTimeout(open, CFG.delayMs);
      function onScroll(){
        var h = document.documentElement;
        var pct = (h.scrollTop) / ((h.scrollHeight - h.clientHeight) || 1) * 100;
        if (pct >= CFG.scrollPct) open();
      }
      window.addEventListener('scroll', onScroll, { passive: true });
      function teardownTriggers(){
        clearTimeout(timer);
        window.removeEventListener('scroll', onScroll);
      }

      // ── Submit ──
      var form = pop.querySelector('.ls-dart-form');
      var msg  = pop.querySelector('.ls-dart-msg');
      var btn  = pop.querySelector('.ls-dart-btn');
      var done = pop.querySelector('.ls-dart-done');

      function showMsg(t){ msg.textContent = t; msg.hidden = false; }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        msg.hidden = true;
        var email = (form.email.value || '').trim();
        var hp    = (form.website.value || '');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          showMsg('اكتب إيميل صح من فضلك.');
          return;
        }
        btn.disabled = true; btn.textContent = 'بسجّلك...';

        fetch(CFG.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email, firstname: '', website: hp })
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (res.ok && res.d && res.d.success) {
            save(DONE_KEY, '1');
            form.hidden = true;
            done.hidden = false;
          } else {
            btn.disabled = false; btn.textContent = 'احجز خصمي دلوقتي 🚀';
            showMsg((res.d && res.d.message) || 'مقدرناش نسجّلك دلوقتي — جرّب تاني.');
          }
        })
        .catch(function () {
          btn.disabled = false; btn.textContent = 'احجز خصمي دلوقتي 🚀';
          showMsg('النت قطع شوية — جرّب كمان مرة.');
        });
      });
    })();
    </script>
    <?php
}
