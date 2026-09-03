<?php
/**
 * Plugin Name: Learn Simply — Skip Cart (direct to checkout)
 * Description: Sends "add to cart" straight to checkout, skipping the cart page entirely.
 *              The store sells 9 digital products and ~99% of buyers take a single course/bundle,
 *              so the cart page is pure friction. Multi-item still works (add A -> checkout -> back -> add B).
 *              Fully reversible: delete this file to restore the default cart-page flow.
 * Author: GrowthMora (Omar)
 * Version: 1.0.0
 * Date: 2026-06-24
 */
if (!defined('ABSPATH')) { exit; }

// 1) After any add-to-cart, redirect straight to the checkout (CartFlows-aware via wc_get_checkout_url()).
add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    return function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : $url;
}, 99);

// 2) Force a full (non-AJAX) add-to-cart request so the redirect above always fires,
//    and stop the default "redirect to cart page after add" behaviour.
//    Runtime-only overrides (no DB writes) -> deleting this file reverts everything.
add_filter('option_woocommerce_enable_ajax_add_to_cart', function () { return 'no'; });
add_filter('option_woocommerce_cart_redirect_after_add', function () { return 'no'; });
