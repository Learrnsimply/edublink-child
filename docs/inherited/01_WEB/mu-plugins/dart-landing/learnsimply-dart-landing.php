<?php
/**
 * Plugin Name: Learn Simply — Dart Landing Page (/dart)
 * Description: Renders the Dart waitlist landing page at learrnsimply.com/dart INSIDE the theme's header/footer (real WP page). The signup form posts to the SAME backend as the popup (/wp-json/learnsimply/v1/dart-waitlist → W2 → Mautic segment 10), so all waitlist sources stay unified.
 * Version: 2.0.0
 * Author: GrowthMora (Omar) for اتعلم ببساطة
 *
 * ── DEPLOY ───────────────────────────────────────────────────────────────────
 *   Files (mu-plugins auto-loads the .php in root; the html fragment is read by it):
 *     wp-content/mu-plugins/learnsimply-dart-landing.php   ← this loader
 *     wp-content/mu-plugins/dart-landing/page.html         ← the content FRAGMENT (scoped #ls-dart-lp)
 *   Requires a real WP Page with slug "dart" (create once via wp-cli):
 *     wp post create --post_type=page --post_status=publish --post_title="كورس Dart من الصفر للاحتراف" --post_name=dart
 *   Then purge the page cache (wp-optimize) so visitors get the fresh render.
 *
 * ── HOW IT WORKS ─────────────────────────────────────────────────────────────
 *   On the "dart" page request we render: get_header() + the scoped fragment + get_footer().
 *   This wraps our content in the SITE's real header/footer (nav + branding). All of our
 *   CSS is namespaced under #ls-dart-lp so theme styles can't leak in (and ours can't leak
 *   out into the header/footer). The form reuses the popup's REST route (registered by
 *   learnsimply-dart-popup.php) — this page depends on that mu-plugin for the backend.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Master kill switch — set false (in wp-config.php) to take /dart offline instantly.
if (!defined('LS_DART_LP_ENABLED')) {
    define('LS_DART_LP_ENABLED', true);
}

/**
 * Is the current request our Dart landing page?
 * Primary: the real WP page with slug "dart". Fallback: raw path "/dart"
 * (covers a routing edge where a same-named archive/category would otherwise win).
 */
function ls_dart_lp_is_target() {
    if (is_page('dart')) {
        return true;
    }
    $path = strtolower(trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
    $home = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($home !== '' && strpos($path, $home . '/') === 0) {
        $path = substr($path, strlen($home) + 1);
    }
    return ($path === 'dart');
}

// Keep the document <title> correct even if the request resolved via the path fallback.
add_filter('pre_get_document_title', function ($title) {
    if (LS_DART_LP_ENABLED && ls_dart_lp_is_target()) {
        return 'كورس Dart من الصفر للاحتراف — اتعلم ببساطة';
    }
    return $title;
}, 99);

add_action('template_redirect', function () {
    if (!LS_DART_LP_ENABLED || !ls_dart_lp_is_target()) {
        return;
    }

    $page = __DIR__ . '/dart-landing/page.html'; // deployed layout (loader in mu-plugins root)
    if (!is_readable($page)) {
        $page = __DIR__ . '/page.html';          // same-dir fallback (repo layout)
    }
    $html = is_readable($page) ? file_get_contents($page) : '';
    if ($html === '' || $html === false) {
        return; // nothing to render → don't hijack the request
    }

    // Same-origin REST endpoint (n8n secret token stays server-side in the popup plugin).
    $endpoint = esc_url_raw(rest_url('learnsimply/v1/dart-waitlist'));
    $html = str_replace('__LS_ENDPOINT__', $endpoint, $html);

    status_header(200);
    if (function_exists('nocache_headers')) {
        nocache_headers();
    }
    get_header();
    echo $html;
    get_footer();
    exit;
}, 5);
