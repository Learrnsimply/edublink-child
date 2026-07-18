<?php
/**
 * Custom template for Tutor LMS Course Bundles (post type: course-bundle).
 *
 * Renders a bespoke landing page (views/single-course-bundle.twig) that mirrors
 * the premium bundle layout: hero + real-value breakdown + course cards +
 * benefits + guarantee + final CTA. Prices are pulled live from the member
 * courses (regular / pre-discount price) so the "real value" and savings stay
 * accurate against the products.
 *
 * @package EduBlink_Child
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

$context              = Timber::context();
$context['theme_uri'] = get_stylesheet_directory_uri();
$context['cart_url']  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/';

$bundle_id = get_queried_object_id();

// Instructor title (matches the rest of the site)
$context['instructor_title'] = 'مهندس برمجيات';

// ── Member courses: matched by slug, priced from the LIVE course data ──
$bundle_course_slugs = function_exists( 'learnsimply_programming_bundle_slugs' )
	? learnsimply_programming_bundle_slugs()
	: array( 'java-course-level1', 'javaoop', 'data-structure-c', 'data_structure_level2', 'dart', 'مشاريع-بايثون-للمبتدئين' );

$course_post_type = function_exists( 'tutor' ) ? tutor()->course_post_type : 'courses';

$bundle_items  = array();
$value_total   = 0; // sum of member courses' REGULAR (pre-discount) prices
$total_lessons = 0;

foreach ( $bundle_course_slugs as $c_slug ) {
	$found = get_posts( array(
		'name'           => $c_slug,
		'post_type'      => $course_post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
	) );

	if ( empty( $found ) ) {
		continue;
	}

	$c_id = $found[0]->ID;

	$regular = 0;
	$sale    = 0;
	$is_free = false;
	$lessons = 0;

	if ( function_exists( 'tutor_utils' ) ) {
		$price_info = tutor_utils()->get_raw_course_price( $c_id );
		$regular    = ( $price_info && $price_info->regular_price ) ? floatval( $price_info->regular_price ) : 0;
		$sale       = ( $price_info && $price_info->sale_price ) ? floatval( $price_info->sale_price ) : 0;

		$price_type = tutor_utils()->price_type( $c_id );
		$is_free    = ( $price_type === 'free' || ( ! $regular && ! $sale ) );

		$lessons = (int) tutor_utils()->get_lesson_count_by_course( $c_id );
	}

	$value_total   += $regular;
	$total_lessons += $lessons;

	$thumb = get_the_post_thumbnail_url( $c_id, 'full' );
	if ( ! $thumb && function_exists( 'learnsimply_no_image_url' ) ) {
		$thumb = learnsimply_no_image_url();
	}

	$bundle_items[] = array(
		'title'         => get_the_title( $c_id ),
		'link'          => get_permalink( $c_id ),
		'thumbnail'     => $thumb,
		'regular_price' => $regular,
		'is_free'       => $is_free,
		'lessons'       => $lessons,
	);
}

// ── Bundle price + WooCommerce buy link ──
$bundle_product_id = 0;
if ( function_exists( 'tutor_utils' ) ) {
	$maybe_pid = tutor_utils()->get_course_product_id( $bundle_id );
	$bundle_product_id = $maybe_pid ? (int) $maybe_pid : 0;
}
if ( ! $bundle_product_id ) {
	$meta_pid = get_post_meta( $bundle_id, '_tutor_course_product_id', true );
	$bundle_product_id = $meta_pid ? (int) $meta_pid : 0;
}

$bundle_price = 1900; // safe fallback
if ( $bundle_product_id && function_exists( 'wc_get_product' ) ) {
	$wc = wc_get_product( $bundle_product_id );
	if ( $wc ) {
		$live_price = $wc->get_price();
		if ( $live_price !== '' && $live_price !== null ) {
			$bundle_price = floatval( $live_price );
		}
	}
}

if ( $bundle_product_id ) {
	$buy_url = add_query_arg( 'add-to-cart', $bundle_product_id, $context['cart_url'] );
} else {
	// Fall back to the native bundle permalink so the button always goes somewhere.
	$buy_url = get_permalink( $bundle_id );
}

$savings         = max( 0, $value_total - $bundle_price );
$savings_percent = $value_total > 0 ? round( ( $savings / $value_total ) * 100 ) : 0;

// Bundle description (falls back to a sensible default)
$bundle_excerpt = get_the_excerpt( $bundle_id );
if ( empty( trim( (string) $bundle_excerpt ) ) ) {
	$bundle_excerpt = 'رحلة البرمجة كاملة من الأساسيات لهياكل البيانات في باقة واحدة — كل الكورسات مع بعض بسعر واحد ووصول مدى الحياة.';
}

$context['bundle_data'] = array(
	'title'           => get_the_title( $bundle_id ),
	'description'     => $bundle_excerpt,
	'items'           => $bundle_items,
	'value_total'     => $value_total,
	'price'           => $bundle_price,
	'savings'         => $savings,
	'savings_percent' => $savings_percent,
	'total_lessons'   => $total_lessons,
	'course_count'    => count( $bundle_items ),
	'buy_url'         => $buy_url,
);

Timber::render( 'single-course-bundle.twig', $context );
