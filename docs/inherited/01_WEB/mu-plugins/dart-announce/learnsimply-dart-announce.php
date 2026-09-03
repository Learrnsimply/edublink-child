<?php
/**
 * Plugin Name: Learn Simply — Dart Announcement Bar
 * Description: A thin, dismissible site-wide bar that drives traffic to the /dart waitlist landing page. Sits ABOVE the theme header without covering it (JS pushes a fixed header + content down by the bar height). Scoped under #ls-dart-bar so theme styles can't interfere.
 * Version: 1.0.0
 * Author: GrowthMora (Omar) for اتعلم ببساطة
 *
 * DEPLOY: copy to wp-content/mu-plugins/learnsimply-dart-announce.php (single file, auto-loads).
 */

if (!defined('ABSPATH')) {
    exit;
}

// Master switch.
if (!defined('LS_DART_BAR_ENABLED'))   { define('LS_DART_BAR_ENABLED', true); }
// Stop showing after launch day.
if (!defined('LS_DART_BAR_UNTIL'))     { define('LS_DART_BAR_UNTIL', '2026-06-15'); }
// Set true to show ONLY on the homepage (default: site-wide).
if (!defined('LS_DART_BAR_HOME_ONLY')) { define('LS_DART_BAR_HOME_ONLY', false); }

function ls_dart_bar_should_render() {
    if (!LS_DART_BAR_ENABLED) {
        return false;
    }
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()) {
        return false;
    }
    if (current_time('Y-m-d') > LS_DART_BAR_UNTIL) {
        return false; // launch passed
    }
    // Pointless on the landing page itself.
    if (function_exists('is_page') && is_page('dart')) {
        return false;
    }
    // Don't sit on top of the purchase flow.
    if (function_exists('is_cart') && (is_cart() || is_checkout())) {
        return false;
    }
    if (LS_DART_BAR_HOME_ONLY && !is_front_page()) {
        return false;
    }
    return true;
}

add_action('wp_body_open', 'ls_dart_bar_render', 1);

// Fallback for themes that don't fire wp_body_open: render fixed + offset <body>.
add_action('wp_footer', function () {
    if (!did_action('wp_body_open')) {
        ls_dart_bar_render(true);
    }
}, 1);

function ls_dart_bar_render($fixed = false) {
    static $done = false;
    if ($done || !ls_dart_bar_should_render()) {
        return;
    }
    $done = true;
    $url = esc_url(home_url('/dart'));
    $pos = $fixed ? 'position:fixed;top:0;left:0;right:0;' : 'position:relative;';
    ?>
    <div id="ls-dart-bar" dir="rtl" hidden>
      <style>
        #ls-dart-bar{<?php echo $pos; ?>z-index:99998;
          font-family:'Cairo',system-ui,sans-serif;
          background:linear-gradient(90deg,#5a32d6,#8a5bff);color:#fff;
          box-shadow:0 2px 14px rgba(90,50,214,.35)}
        #ls-dart-bar[hidden]{display:none}
        #ls-dart-bar .ls-bar-in{max-width:1100px;margin-inline:auto;display:flex;align-items:center;
          justify-content:center;gap:14px;flex-wrap:wrap;padding:11px 44px 11px 16px;text-align:center}
        #ls-dart-bar .ls-bar-txt{font-size:17.3px;font-weight:700;line-height:1.5}
        #ls-dart-bar .ls-bar-txt b{color:#ffe08a}
        #ls-dart-bar .ls-bar-cta{flex:0 0 auto;background:#fff;color:#5a32d6 !important;
          font-weight:800;font-size:16px;text-decoration:none !important;padding:8px 20px;border-radius:999px;
          white-space:nowrap;transition:transform .15s ease,box-shadow .15s ease}
        #ls-dart-bar .ls-bar-cta:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.25)}
        #ls-dart-bar .ls-bar-x{position:absolute;inset-inline-end:10px;top:50%;transform:translateY(-50%);
          width:26px;height:26px;border:0;border-radius:50%;background:rgba(255,255,255,.18);color:#fff;
          font-size:17px;line-height:1;cursor:pointer;padding:0}
        #ls-dart-bar .ls-bar-x:hover{background:rgba(255,255,255,.32)}
        @media (max-width:520px){
          #ls-dart-bar .ls-bar-in{gap:9px;padding:9px 40px 9px 12px}
          #ls-dart-bar .ls-bar-txt{font-size:15.2px}
          #ls-dart-bar .ls-bar-cta{font-size:14.4px;padding:7px 15px}
        }
      </style>
      <div class="ls-bar-in" style="position:relative">
        <span class="ls-bar-txt">🚀 كورس <b>Dart</b> من الصفر قريب — سجّل دلوقتي واحجز <b>خصم الإطلاق 50%</b></span>
        <a class="ls-bar-cta" href="<?php echo $url; ?>">احجز مكانك ←</a>
        <button type="button" class="ls-bar-x" aria-label="إغلاق">&times;</button>
      </div>
    </div>
    <script>
    (function () {
      var KEY = 'ls_dart_bar_closed', DAYS = 3;
      var bar = document.getElementById('ls-dart-bar');
      if (!bar) return;
      function closedRecently(){
        try { var ts = parseInt(localStorage.getItem(KEY) || '0', 10);
          return ts && (Date.now() - ts) < DAYS * 864e5; } catch (e) { return false; }
      }
      if (closedRecently()) { bar.parentNode && bar.parentNode.removeChild(bar); return; }
      bar.hidden = false;

      /* When the bar overlays the page (position:fixed) and the theme header is ALSO
         fixed (EduBlink: header.learnsimply-header-main-container, top:10px !important),
         the bar covers the header. Fix: push the fixed header AND the content down by the
         bar's exact height so the bar sits ABOVE the header without covering it.
         Reads current values + adds bar height (no hardcoded theme values); reverts on close.
         Uses !important because the theme sets header top + main padding-top with !important. */
      var off = null;
      function applyOffset() {
        if (off || getComputedStyle(bar).position !== 'fixed') { return; }
        var bh = bar.offsetHeight; if (!bh) { return; }
        var header = document.querySelector('header.learnsimply-header-main-container') || document.querySelector('header');
        var main = document.querySelector('main');
        off = { bh: bh, header: null, main: null };
        if (header) {
          var ht = parseFloat(getComputedStyle(header).top) || 0;
          off.header = { el: header, top: header.style.getPropertyValue('top'), prio: header.style.getPropertyPriority('top') };
          header.style.setProperty('top', (ht + bh) + 'px', 'important');
        }
        if (main) {
          var mp = parseFloat(getComputedStyle(main).paddingTop) || 0;
          off.main = { el: main, pt: main.style.getPropertyValue('padding-top'), prio: main.style.getPropertyPriority('padding-top') };
          main.style.setProperty('padding-top', (mp + bh) + 'px', 'important');
        }
      }
      function revertOffset() {
        if (!off) { return; }
        if (off.header) { off.header.el.style.setProperty('top', off.header.top, off.header.prio); }
        if (off.main) { off.main.el.style.setProperty('padding-top', off.main.pt, off.main.prio); }
        off = null;
      }
      applyOffset();
      var rt;
      window.addEventListener('resize', function () {
        clearTimeout(rt); rt = setTimeout(function () { revertOffset(); applyOffset(); }, 150);
      });

      bar.querySelector('.ls-bar-x').addEventListener('click', function () {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
        revertOffset();
        bar.style.transition = 'opacity .2s ease'; bar.style.opacity = '0';
        setTimeout(function () { bar.parentNode && bar.parentNode.removeChild(bar); }, 200);
      });
    })();
    </script>
    <?php
}
