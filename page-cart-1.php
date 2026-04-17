<?php
/**
 * Template for /cart-1/ page — WooCommerce Cart
 *
 * WordPress template hierarchy: page-{slug}.php takes highest priority,
 * ensuring this file loads instead of the parent theme's default table layout.
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

$context = Timber::context();

// ── WooCommerce not ready → show empty ────────────────────────────────────
if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	$context['has_items']   = false;
	$context['cart_items']  = [];
	$context['total_count'] = 0;
	$context['shop_url']    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	Timber::render( 'page-cart-1.twig', $context );
	return;
}

// ── Read WooCommerce cart ─────────────────────────────────────────────────
$wc_cart      = WC()->cart;
$cart_nonce   = wp_create_nonce( 'woocommerce-cart' );
$cart_contents = $wc_cart->get_cart();
$cart_items   = [];

foreach ( $cart_contents as $cart_item_key => $cart_item ) {
	/** @var WC_Product $product */
	$product     = $cart_item['data'];
	$product_id  = (int) $cart_item['product_id'];
	$quantity    = (int) $cart_item['quantity'];

	$regular_price = (float) $product->get_regular_price();
	$sale_price    = (float) $product->get_sale_price();
	$line_total    = (float) $cart_item['line_total'];

	$thumbnail = get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' );
	if ( ! $thumbnail ) {
		$thumbnail = wc_placeholder_img_src( 'woocommerce_thumbnail' );
	}

	// Get the remove URL (WooCommerce generates it with nonce)
	$remove_url = wc_get_cart_remove_url( $cart_item_key );

	$cart_items[] = [
		'key'       => esc_attr( $cart_item_key ),
		'id'        => $product_id,
		'title'     => $product->get_name(),
		'permalink' => get_permalink( $product_id ),
		'thumbnail' => $thumbnail,
		'quantity'  => $quantity,
		'has_sale'  => ( $sale_price > 0 && $sale_price < $regular_price ),
		'price'     => wc_price( $line_total ),
		'price_old' => ( $sale_price > 0 && $sale_price < $regular_price ) ? wc_price( $regular_price * $quantity ) : '',
		'remove_url' => esc_url( $remove_url ),
	];
}

$total_count   = count( $cart_items );
$fmt_subtotal  = $wc_cart->get_cart_subtotal();          // already formatted HTML
$fmt_total     = $wc_cart->get_total();                  // already formatted HTML
$tax_totals    = $wc_cart->get_tax_totals();
$show_tax      = wc_tax_enabled() && ! empty( $tax_totals );
$fmt_tax       = '';
$tax_label     = '';
if ( $show_tax ) {
	$first_tax = reset( $tax_totals );
	$fmt_tax   = wc_price( $first_tax->amount );
	$tax_label = $first_tax->label;
}

$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
$shop_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

// ── Timber context ────────────────────────────────────────────────────────
$context['has_items']    = ! empty( $cart_items );
$context['cart_items']   = $cart_items;
$context['total_count']  = $total_count;
$context['checkout_url'] = esc_url( $checkout_url );
$context['fmt_subtotal'] = $fmt_subtotal;
$context['fmt_tax']      = $fmt_tax;
$context['fmt_total']    = $fmt_total;
$context['show_tax']     = $show_tax;
$context['tax_label']    = $tax_label;
$context['shop_url']     = esc_url( $shop_url );
$context['cart_nonce']   = $cart_nonce;

Timber::render( 'page-cart-1.twig', $context );
