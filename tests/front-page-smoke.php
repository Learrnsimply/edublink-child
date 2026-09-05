<?php
/**
 * اختبار تشغيل للرئيسية — بيشغّل front-page.php فعليًا من غير WordPress.
 *
 * ليه موجود: في ٥ سبتمبر ٢٠٢٦ الرئيسية نزلت على السيرفر بصفحة بيضا، لأن دوال مساعدة
 * كانت متعرّفة تعريفًا شرطيًا (function_exists) في آخر الملف — وPHP مبيحمّل التعريف الشرطي
 * مسبقًا. `php -l` وترجمة Twig عدّوا عادي؛ الخطأ وقت تشغيل PHP. الاختبار ده بيمسك الفئة دي:
 * بيعرّف stubs لووردبريس وTutor وTimber وWooCommerce، ويضمّن الملف **مرتين** (زي ما
 * template_redirect وtemplate_include ممكن يعملوا)، ويتأكد إن Timber::render وصلها الشكل الصح.
 *
 * التشغيل: php tests/front-page-smoke.php   (بيتنادى من tools-lint.sh)
 */
namespace Timber {
	class Timber {
		public static $rendered = array();
		public static function context() { return array(); }
		public static function get_post( $id ) { $o = new \stdClass(); $o->ID = $id; $o->title = "Course $id"; $o->link = "https://x/c/$id"; return $o; }
		public static function render( $template, $ctx ) { self::$rendered[] = array( $template, $ctx ); }
	}
}

namespace {
	if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }

	define( 'ABSPATH', '/' );
	define( 'OBJECT', 'OBJECT' );
	class_alias( 'Timber\\Timber', 'Timber' );

	class WP_Error {}
	class WooCommerce {}
	function is_wp_error( $x ) { return $x instanceof WP_Error; }
	function add_action() {} function add_filter() {} function update_post_meta() {}
	function get_option() { return false; } function update_option() {}
	function get_stylesheet_directory_uri() { return 'https://x/theme'; }
	function wp_reset_postdata() {}
	function home_url( $p = '' ) { return 'https://x' . $p; }
	function get_permalink( $id ) { return "https://x/p/$id"; }
	function get_post_status( $id ) { return 'publish'; }
	function get_the_title( $id ) { return 39654 === $id ? 'أساسيات Dart من الصفر لـ OOP | أول خطوة لـ Flutter' : "Course $id"; }
	function get_the_post_thumbnail_url( $id, $size ) { return "https://x/img/$id.jpg"; }
	function learnsimply_no_image_url() { return 'https://x/none.png'; }
	function is_user_logged_in() { return false; }
	function get_current_user_id() { return 0; }
	function get_term_link( $slug, $tax ) { return "https://x/course-category/$slug/"; }
	function has_excerpt( $id ) { return true; }
	function get_the_excerpt( $id ) { return "<p>وصف الكورس $id بكلمات كتير جدًا هنا عشان نجرب القص لثمانية عشر كلمة وبعدين نقيس النتيجة كويس جدًا فعلًا</p>"; }
	function wp_strip_all_tags( $t ) { return strip_tags( $t ); }
	function wp_trim_words( $t, $n, $more ) { $w = preg_split( '/\s+/', trim( $t ) ); return count( $w ) > $n ? implode( ' ', array_slice( $w, 0, $n ) ) . $more : $t; }
	function get_post_meta( $id, $key, $single ) {
		if ( '_course_duration' === $key ) { return array( 'hours' => 13, 'minutes' => 40 ); }
		if ( '_tutor_course_level' === $key ) { return 31578 === $id ? 'intermediate' : 'beginner'; }
		return '';
	}
	function get_page_by_path( $slug, $output, $type ) {
		$map = array( 'java-basics-oop-bundle' => 33336, 'all_in_one' => 40754 );
		$map[ rawurlencode( 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ) ] = 39043;
		if ( ! isset( $map[ $slug ] ) ) { return null; }
		$p = new stdClass(); $p->ID = $map[ $slug ]; $p->post_status = 'publish'; return $p;
	}
	function learnsimply_bundle_item_product_ids( array $ids ) {
		// جافا = منتجات كورسي جافا (24443+1 · 31578+1) · هياكل = (11287+1 · 30816+1) — نفس قاعدة الـstub: product = course + 1
		$all = array( 33336 => array( 24444, 31579 ), 39043 => array( 11288, 30817 ) );
		$out = array(); foreach ( $ids as $id ) { $out[ $id ] = isset( $all[ $id ] ) ? $all[ $id ] : array(); } return $out;
	}
	function wc_get_product( $id ) {
		$names = array( 33336 => 'كورس Java Basics + OOP', 39043 => 'هياكل البيانات الكاملة', 40754 => 'جميع الدورات' );
		$prices = array( 33336 => '1000', 39043 => '1000', 40754 => '2500' );
		return new class( $names[ $id ], $prices[ $id ] ) {
			private $n; private $p;
			public function __construct( $n, $p ) { $this->n = $n; $this->p = $p; }
			public function get_price() { return $this->p; }
			public function get_name() { return $this->n; }
		};
	}

	class WP_Query {
		public $posts = array();
		public function __construct( $args ) {
			// ترتيب النشر العشوائي عن قصد — الصفحة لازم تعيد الترتيب بمنطق الأكاديمية
			foreach ( array( 39654, 11287, 24443, 31578, 29368, 30816 ) as $id ) { $p = new stdClass(); $p->ID = $id; $this->posts[] = $p; }
		}
	}
	function tutor() { $o = new stdClass(); $o->course_post_type = 'courses'; return $o; }
	class LS_Tutor_Utils_Stub {
		public function get_option( $k ) { return 'wc'; }
		public function get_course_rating( $id ) { $r = new stdClass(); $r->rating_avg = 4.9; $r->rating_count = $id % 50; return $r; }
		public function get_raw_course_price( $id ) { $p = new stdClass(); $p->regular_price = 29368 === $id ? 0 : 1200; $p->sale_price = 29368 === $id ? 0 : 700; return $p; }
		public function price_type( $id ) { return 29368 === $id ? 'free' : 'paid'; }
		public function get_lesson_count_by_course( $id ) { return 80; }
		public function count_enrolled_users_by_course( $id ) { return 11287 === $id ? 1646 : 100; }
		public function get_course_product_id( $id ) { return $id + 1; }
		public function get_course_id_by_product( $pid ) { return $pid - 1; }
		public function is_enrolled( $a, $b ) { return false; }
	}
	function tutor_utils() { return new LS_Tutor_Utils_Stub(); }

	require __DIR__ . '/../inc/academy-structure.php';

	$fail = 0;
	function ls_check( $ok, $label ) { global $fail; echo ( $ok ? '  ✅ ' : '  ❌ ' ) . $label . "\n"; if ( ! $ok ) { $fail = 1; } }

	// التضمين مرتين — زي template_redirect + template_include. لازم ميرميش "cannot redeclare".
	require __DIR__ . '/../front-page.php';
	require __DIR__ . '/../front-page.php';

	ls_check( 2 === count( Timber\Timber::$rendered ), 'front-page.php بيتضمّن مرتين من غير fatal (تعريفات الدوال محمية)' );
	$ctx = Timber\Timber::$rendered[0][1];
	ls_check( 'front-page.twig' === Timber\Timber::$rendered[0][0], 'بيرندر front-page.twig' );
	ls_check( 6 === count( $ctx['courses'] ), '٦ كورسات' );
	$order = array_map( function ( $c ) { return $c->ID; }, $ctx['courses'] );
	ls_check( array( 24443, 31578, 11287, 30816, 39654, 29368 ) === $order, 'الترتيب بمنطق الأكاديمية: جافا ← OOP ← هياكل ١ ← هياكل ٢ ← دارت ← بايثون' );
	$best = array_values( array_filter( $ctx['courses'], function ( $c ) { return $c->is_bestseller; } ) );
	ls_check( 1 === count( $best ) && 11287 === $best[0]->ID, '«الأكثر مبيعًا» واحد بس = أعلى كورس مدفوع في المشتركين' );
	$free = array_values( array_filter( $ctx['courses'], function ( $c ) { return $c->is_free; } ) );
	ls_check( 1 === count( $free ) && '' === $free[0]->price_label, 'الكورس المجاني من غير سعر' );
	ls_check( '700' === $ctx['courses'][0]->price_label && '13h' === $ctx['courses'][0]->hours, 'سعر واحد بس + الساعات من meta المدة' );
	ls_check( 2 === count( $ctx['paths'] ) && 5 === count( $ctx['paths'][0]['steps'] ) && 2 === count( $ctx['paths'][1]['steps'] ), 'مسارين: الأساسيات ٥ خطوات · التطبيقات ٢ (دارت + Flutter قريبًا)' );
	ls_check( true === $ctx['paths'][1]['steps'][1]['coming_soon'], 'Flutter كارت «قريبًا»' );
	ls_check( 'أساسيات Dart من الصفر لـ OOP' === $ctx['paths'][0]['steps'][4]['title'], 'عنوان الخطوة مختصر قبل «|»' );
	ls_check( 3 === count( $ctx['bundles'] ), '٣ باقات: جافا · هياكل · الكل' );
	ls_check( '1,000' === $ctx['bundles'][0]['price_label'] && 2 === $ctx['bundles'][0]['courses_count'] && 26 === $ctx['bundles'][0]['hours_total'], 'باقة جافا: كورسين · 26 ساعة · السعر من ووكومرس' );
	ls_check( array( 'Course 11287', 'Course 30816' ) === $ctx['bundles'][1]['items'], 'باقة هياكل: عناصرها من جدول البلجن بترتيبه' );
	ls_check( true === $ctx['bundles'][2]['featured'] && 6 === $ctx['bundles'][2]['courses_count'] && '2,500' === $ctx['bundles'][2]['price_label'], 'جميع الدورات: مميزة · ٦ كورسات · 2,500' );
	ls_check( '4.9' === $ctx['stats']['rating_avg'], 'التقييم المجمّع محسوب' );

	echo $fail ? "  اختبار الرئيسية فشل\n" : "  اختبار تشغيل الرئيسية عدّى\n";
	exit( $fail );
}
