<?php
/**
 * صفحة الكورس — الكونترولر.
 *
 * بنية Code with Mosh (MOSH-BLUEPRINT.md · الموجة د): عمود واحد، ١٣ كتلة بترتيب ثابت،
 * والسعر في ٣ أماكن بس (الهيرو · الـCTA الختامي · الشريط اللاصق على الموبايل).
 * الكلام كله من حقول Tutor عبر inc/course-content.php (المحلّل اللي بيستحمل اختلاف
 * الكورسات)، ومكان الكورس في مساره من inc/academy-structure.php.
 *
 * الملف ده بيحضّر الـcontext بس — مفيش تعريف دوال هنا (درس ٥/٩: التعريف الشرطي في
 * آخر الملف وقّع الرئيسية). الدوال في inc/ وبتتحمّل من functions.php.
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
if ( ! function_exists( 'tutor_utils' ) ) {
	echo 'Tutor LMS is not active';
	exit;
}

$course_id = (int) get_the_ID();
$context   = Timber::context();
$user_id   = (int) get_current_user_id();

$context['theme_uri']      = get_stylesheet_directory_uri();
$context['assets_version'] = defined( 'LS_ASSETS_VERSION' ) ? LS_ASSETS_VERSION : '1';

$cart_page_id        = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
$context['cart_url'] = ( $cart_page_id > 0 ) ? get_permalink( $cart_page_id ) : ( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/' );

$course                = Timber::get_post( $course_id );
$context['course']     = $course;
$context['course_id']  = $course_id;
$context['title']      = get_the_title( $course_id );
$context['course_url'] = get_permalink( $course_id );

// ─────────────────────────────────────────────────────────────────────────────
// الحالة: مشترك؟ مجاني؟ بيتباع بووكومرس؟
// ─────────────────────────────────────────────────────────────────────────────
$context['is_user_logged_in'] = is_user_logged_in();
$context['is_enrolled']       = (bool) tutor_utils()->is_enrolled( $course_id, $user_id );
$context['is_public']         = 'yes' === get_post_meta( $course_id, '_tutor_is_public_course', true );
$context['is_purchasable']    = (bool) tutor_utils()->is_course_purchasable( $course_id );
$context['lesson_url']        = tutor_utils()->get_course_first_lesson( $course_id );
$context['tutor_nonce']       = wp_create_nonce( tutor()->nonce_action );
$context['tutor_nonce_field'] = tutor()->nonce;

$price_info    = tutor_utils()->get_raw_course_price( $course_id );
$regular_price = ! empty( $price_info->regular_price ) ? (float) $price_info->regular_price : 0;
$sale_price    = ! empty( $price_info->sale_price ) ? (float) $price_info->sale_price : 0;
$price         = $sale_price > 0 ? $sale_price : $regular_price;
$context['is_free']     = ( 'free' === tutor_utils()->price_type( $course_id ) ) || $price <= 0;
$context['price_label'] = $context['is_free'] ? '' : number_format( $price, 0, '.', ',' );

$context['product_id'] = null;
$sell_by               = apply_filters( 'tutor_course_sell_by', null );
if ( 'woocommerce' === $sell_by && class_exists( 'WooCommerce' ) ) {
	$product_id            = tutor_utils()->get_course_product_id( $course_id );
	$context['product_id'] = $product_id ? (int) $product_id : null;
}
$context['checkout_url'] = $context['product_id'] ? home_url( '/checkout/?add-to-cart=' . $context['product_id'] ) : '';
$context['can_buy']      = ! $context['is_enrolled'] && $context['is_purchasable'] && ! $context['is_free'] && $context['product_id'];

// ─────────────────────────────────────────────────────────────────────────────
// الأرقام — من Tutor زي ما هي
// ─────────────────────────────────────────────────────────────────────────────
$rating                  = tutor_utils()->get_course_rating( $course_id );
$context['rating_avg']   = ( $rating && $rating->rating_count > 0 ) ? number_format( (float) $rating->rating_avg, 1 ) : '';
$context['rating_count'] = $rating ? (int) $rating->rating_count : 0;
$context['students']     = (int) tutor_utils()->count_enrolled_users_by_course( $course_id );
$context['lesson_count'] = (int) tutor_utils()->get_lesson_count_by_course( $course_id )
	+ (int) tutor_utils()->get_quiz_count_by_course( $course_id )
	+ (int) tutor_utils()->get_assignment_count_by_course( $course_id );
$context['hours']        = learnsimply_course_hours( $course_id );
$level                   = learnsimply_course_level( $course_id );
$context['level']        = $level['label'];
$context['level_key']    = $level['key'];

// ─────────────────────────────────────────────────────────────────────────────
// الصورة أو الفيديو التعريفي
// ─────────────────────────────────────────────────────────────────────────────
$context['image'] = get_the_post_thumbnail_url( $course_id, 'full' );
if ( ! $context['image'] ) {
	$context['image'] = learnsimply_no_image_url();
}

$context['video'] = null;
$video_info       = get_post_meta( $course_id, '_video', true );
if ( is_array( $video_info ) && ! empty( $video_info['source'] ) ) {
	// كل المفاتيح موجودة دايمًا — القالب بيسأل عن embed_url/embed_code/url من غير ما يتأكد.
	$v = array( 'source' => $video_info['source'], 'url' => null, 'embed_url' => null, 'embed_code' => null, 'poster' => null );
	switch ( $video_info['source'] ) {
		case 'html5':
			if ( ! empty( $video_info['source_video_id'] ) ) {
				$v['url'] = wp_get_attachment_url( $video_info['source_video_id'] );
			}
			break;
		case 'external_url':
			if ( ! empty( $video_info['source_external_url'] ) ) {
				$v['url'] = $video_info['source_external_url'];
			}
			break;
		case 'youtube':
			if ( ! empty( $video_info['source_youtube'] ) && preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_info['source_youtube'], $m ) ) {
				$v['embed_url'] = 'https://www.youtube.com/embed/' . $m[1];
			}
			break;
		case 'vimeo':
			if ( ! empty( $video_info['source_vimeo'] ) && preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $video_info['source_vimeo'], $m ) ) {
				$v['embed_url'] = 'https://player.vimeo.com/video/' . $m[1];
			}
			break;
		case 'embedded':
			if ( ! empty( $video_info['source_embedded'] ) ) {
				$v['embed_code'] = $video_info['source_embedded'];
			}
			break;
	}
	if ( ! empty( $video_info['poster'] ) ) {
		$v['poster'] = wp_get_attachment_url( $video_info['poster'] );
	}
	if ( ! empty( $v['url'] ) || ! empty( $v['embed_url'] ) || ! empty( $v['embed_code'] ) ) {
		$context['video'] = $v;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// المنهج — الوحدات ودروسها. بنقرا من posts مباشرة بدل حلقة the_post() عشان
// منلمسش الـglobal query.
// ─────────────────────────────────────────────────────────────────────────────
$context['topics']            = array();
$context['preview_count']     = 0;
$context['first_preview_url'] = '';
$topics_query                 = tutor_utils()->get_topics( $course_id );
$topic_posts                  = ( $topics_query && ! empty( $topics_query->posts ) ) ? $topics_query->posts : array();
foreach ( $topic_posts as $topic ) {
	$contents_query = tutor_utils()->get_course_contents_by_topic( $topic->ID, -1 );
	$content_posts  = ( $contents_query && ! empty( $contents_query->posts ) ) ? $contents_query->posts : array();
	$contents       = array();
	foreach ( $content_posts as $item ) {
		$is_preview = (bool) get_post_meta( $item->ID, '_is_preview', true );
		$url        = get_permalink( $item->ID );
		$contents[] = array(
			'id'         => $item->ID,
			'title'      => $item->post_title,
			'type'       => in_array( $item->post_type, array( 'tutor_quiz', 'quiz' ), true ) ? 'quiz' : ( in_array( $item->post_type, array( 'tutor_assignments', 'assignment' ), true ) ? 'assignment' : 'lesson' ),
			'duration'   => 'lesson' === $item->post_type || 'tutor_lesson' === $item->post_type ? (string) get_post_meta( $item->ID, '_video_duration', true ) : '',
			'is_preview' => $is_preview,
			'url'        => $url,
		);
		if ( $is_preview ) {
			$context['preview_count']++;
			if ( '' === $context['first_preview_url'] && $url ) {
				$context['first_preview_url'] = $url;
			}
		}
	}
	// وحدة من غير دروس (زي «بيتم تحديث الكويزات ⏳») مش وحدة — مبتتعرضش.
	if ( empty( $contents ) ) {
		continue;
	}
	$context['topics'][] = array(
		'id'       => $topic->ID,
		'title'    => learnsimply_course_text_clean( $topic->post_title ),
		'count'    => count( $contents ),
		'contents' => $contents,
	);
}
$context['units_count'] = count( $context['topics'] );

// ─────────────────────────────────────────────────────────────────────────────
// الأقسام النصية — المحلّل + الحقول الأصلية (inc/course-content.php)
// ─────────────────────────────────────────────────────────────────────────────
$context['sections'] = learnsimply_course_sections( $course_id );
$context['tagline']  = learnsimply_course_tagline( $course_id, $context['sections'] );

$course_slug       = $course ? urldecode( $course->post_name ) : '';
$context['extras'] = function_exists( 'learnsimply_get_course_extras' ) ? learnsimply_get_course_extras( $course_slug ) : array( 'build_items' => array(), 'faq_items' => array() );

// ─────────────────────────────────────────────────────────────────────────────
// المسار — مكان الكورس، اللي قبله، اللي بعده، كمّل المسار
// ─────────────────────────────────────────────────────────────────────────────
$context['path'] = learnsimply_course_path_context( $course_id );

// كروت «كمّل المسار»: بيانات الكارت الموحّد (نفس كارت الرئيسية) — سعر واحد وعدد الدروس.
foreach ( $context['path']['rest'] as $i => $step ) {
	$p       = tutor_utils()->get_raw_course_price( $step['id'] );
	$reg     = ! empty( $p->regular_price ) ? (float) $p->regular_price : 0;
	$sale    = ! empty( $p->sale_price ) ? (float) $p->sale_price : 0;
	$sprice  = $sale > 0 ? $sale : $reg;
	$is_free = ( 'free' === tutor_utils()->price_type( $step['id'] ) ) || $sprice <= 0;
	$context['path']['rest'][ $i ]['price_label']  = $is_free ? '' : number_format( $sprice, 0, '.', ',' );
	$context['path']['rest'][ $i ]['is_free']      = $is_free;
	$context['path']['rest'][ $i ]['lesson_count'] = (int) tutor_utils()->get_lesson_count_by_course( $step['id'] );
	$context['path']['rest'][ $i ]['position']     = $context['path']['position'] ? $context['path']['position'] + $i + 1 : $i + 1;
}

// ─────────────────────────────────────────────────────────────────────────────
// تلميح الباقة — لو الكورس جوه باقة من باقات المسارات (منتج ووكومرس + جدول البلجن)
// ─────────────────────────────────────────────────────────────────────────────
$context['bundle'] = null;
if ( $context['product_id'] && class_exists( 'WooCommerce' ) && function_exists( 'learnsimply_bundle_item_product_ids' ) ) {
	$bundle_slugs = array(
		'java-basics-oop-bundle',
		rawurlencode( 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ),
		'هياكل-البيانات-الكاملة-data-structure-level-1-2',
	);
	$bundle_posts = array();
	foreach ( $bundle_slugs as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post && 'publish' === $post->post_status ) {
			$bundle_posts[ $post->ID ] = $post;
		}
	}
	if ( ! empty( $bundle_posts ) ) {
		$items_by_bundle = learnsimply_bundle_item_product_ids( array_keys( $bundle_posts ) );
		foreach ( $items_by_bundle as $bundle_id => $product_ids ) {
			if ( ! in_array( (int) $context['product_id'], $product_ids, true ) ) {
				continue;
			}
			$product = wc_get_product( $bundle_id );
			if ( ! $product ) {
				continue;
			}
			$names = array();
			foreach ( $product_ids as $pid ) {
				$cid = tutor_utils()->get_course_id_by_product( $pid );
				if ( $cid ) {
					$names[] = function_exists( 'learnsimply_course_short_label' ) ? learnsimply_course_short_label( $cid, get_the_title( $cid ) ) : get_the_title( $cid );
				}
			}
			$bprice            = (float) $product->get_price();
			$context['bundle'] = array(
				'title'       => $product->get_name(),
				'url'         => get_permalink( $bundle_id ),
				'items'       => $names,
				'count'       => count( $product_ids ),
				'price_label' => $bprice > 0 ? number_format( $bprice, 0, '.', ',' ) : '',
			);
			break;
		}
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// المدرّس — نفس الأرقام التسويقية اللي في الرئيسية
// ─────────────────────────────────────────────────────────────────────────────
$context['instructor'] = null;
$instructors           = tutor_utils()->get_instructors_by_course( $course_id );
if ( ! empty( $instructors ) && isset( $instructors[0]->ID ) ) {
	$context['instructor'] = array(
		'name'   => $instructors[0]->display_name,
		'avatar' => get_avatar_url( $instructors[0]->ID, array( 'size' => 120 ) ),
		'url'    => home_url( '/about_me/' ),
	);
}
$context['stats'] = array(
	'youtube'    => '403K',
	'students'   => '+10,000',
	'views'      => '17M',
	'experience' => '+7',
);

// ─────────────────────────────────────────────────────────────────────────────
// التقييمات — المعتمدة كلها (أول ٣ ظاهرين والباقي بزرار)، وتقييم المستخدم لو مشترك
// ─────────────────────────────────────────────────────────────────────────────
$context['reviews'] = array();
$reviews            = tutor_utils()->get_course_reviews( $course_id, 0, 30, false, array( 'approved' ), $user_id );
if ( is_array( $reviews ) ) {
	foreach ( $reviews as $review ) {
		$context['reviews'][] = array(
			'id'      => $review->comment_ID,
			'author'  => $review->display_name,
			'rating'  => (int) $review->rating,
			'content' => stripslashes( (string) $review->comment_content ),
			'date'    => $review->comment_date,
			'pending' => isset( $review->comment_status ) && 'hold' === $review->comment_status,
		);
	}
}
$context['can_write_review'] = $context['is_enrolled'] && $context['is_user_logged_in'];
$context['user_review']      = null;
if ( $context['can_write_review'] ) {
	$mine = tutor_utils()->get_reviews_by_user( 0, 0, 150, false, $course_id, array( 'approved', 'hold' ) );
	if ( $mine && ! empty( $mine->rating ) ) {
		$context['user_review'] = array(
			'id'      => $mine->comment_ID,
			'rating'  => (int) $mine->rating,
			'content' => stripslashes( (string) $mine->comment_content ),
		);
	}
}

Timber::render( 'single-course.twig', $context );
