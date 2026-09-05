<?php
/**
 * اختبار تشغيل لصفحة الكورس — بيشغّل tutor/single-course.php فعليًا من غير WordPress.
 *
 * نفس فكرة tests/front-page-smoke.php: stubs لووردبريس وTutor وTimber وWooCommerce،
 * وبنتأكد إن الـcontext اللي بيوصل Timber::render بالشكل اللي القالب مستنيه — لكورسين
 * مختلفين عن قصد (جافا: خطوة ١ · OOP: خطوة ٢ بمتطلب سابق) وللزائر وللمشترك.
 *
 * التشغيل: php tests/single-course-smoke.php   (بيتنادى من tools-lint.sh)
 */
namespace Timber {
	class Timber {
		public static $rendered = array();
		public static function context() { return array( 'site' => (object) array( 'url' => 'https://x' ) ); }
		public static function get_post( $id ) { $o = new \stdClass(); $o->ID = $id; $o->post_name = 24443 === $id ? 'java-course-level1' : 'javaoop'; return $o; }
		public static function render( $template, $ctx ) { self::$rendered[] = array( $template, $ctx ); }
	}
}

namespace {
	if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }

	define( 'ABSPATH', '/' );
	define( 'OBJECT', 'OBJECT' );
	define( 'LS_ASSETS_VERSION', 'test' );
	class_alias( 'Timber\\Timber', 'Timber' );

	$GLOBALS['ls_current'] = 24443;
	$GLOBALS['ls_enrolled'] = false;

	class WP_Error {}
	class WooCommerce {}
	function is_wp_error( $x ) { return $x instanceof WP_Error; }
	function add_action() {} function add_filter() {} function update_post_meta() {} function update_option() {}
	function get_option() { return false; }
	function get_the_ID() { return $GLOBALS['ls_current']; }
	function get_stylesheet_directory_uri() { return 'https://x/theme'; }
	function home_url( $p = '' ) { return 'https://x' . $p; }
	function get_permalink( $id ) { return "https://x/p/$id"; }
	function get_post_status( $id ) { return 'publish'; }
	function get_the_title( $id ) { $t = array( 24443 => 'كورس جافا للمبتدئين + كتاب هدية', 31578 => 'البرمجة الكائنية (OOP) بلغة Java من الصفر للاحتراف' ); return isset( $t[ $id ] ) ? $t[ $id ] : "Course $id"; }
	function get_the_post_thumbnail_url( $id, $size ) { return "https://x/img/$id.jpg"; }
	function learnsimply_no_image_url() { return 'https://x/none.png'; }
	function is_user_logged_in() { return $GLOBALS['ls_enrolled']; }
	function get_current_user_id() { return $GLOBALS['ls_enrolled'] ? 7 : 0; }
	function get_term_link( $slug, $tax ) { return "https://x/course-category/$slug/"; }
	function has_excerpt( $id ) { return false; }
	function get_the_excerpt( $id ) { return ''; }
	function get_post_field( $field, $id ) { return ''; }
	function apply_filters( $tag, $value ) { return 'tutor_course_sell_by' === $tag ? 'woocommerce' : $value; }
	function wp_strip_all_tags( $t ) { return strip_tags( $t ); }
	function wp_trim_words( $t, $n, $more ) { $w = preg_split( '/\s+/', trim( $t ) ); return count( $w ) > $n ? implode( ' ', array_slice( $w, 0, $n ) ) . $more : $t; }
	function wp_create_nonce( $a ) { return 'nonce'; }
	function wc_get_page_id( $p ) { return 21; }
	function wp_get_attachment_url( $id ) { return "https://x/att/$id.mp4"; }
	function get_avatar_url( $id, $args ) { return "https://x/avatar/$id.png"; }
	$GLOBALS['ls_fixtures'] = require __DIR__ . '/course-content-fixtures.php';
	function get_post_meta( $id, $key, $single ) {
		if ( '_course_duration' === $key ) { return array( 'hours' => 13, 'minutes' => 0 ); }
		if ( '_tutor_course_level' === $key ) { return 31578 === $id ? 'intermediate' : 'beginner'; }
		if ( '_tutor_course_benefits' === $key ) { return implode( "\n", $GLOBALS['ls_fixtures'][ 24443 === $id ? 'java' : 'oop' ] ); }
		if ( 'ls_department' === $key ) { return in_array( $id, array( 24443, 31578, 11287, 30816 ), true ) ? 'foundations' : ( 39654 === $id ? 'app-development' : 'start-here' ); }
		if ( '_is_preview' === $key ) { return 1001 === $id; }
		if ( '_video' === $key ) { return 24443 === $id ? array( 'source' => 'embedded', 'source_embedded' => '<iframe src="https://iframe.mediadelivery.net/embed/1/x"></iframe>' ) : ''; }
		return '';
	}
	function get_page_by_path( $slug, $output, $type ) {
		if ( 'java-basics-oop-bundle' === $slug ) { $p = new stdClass(); $p->ID = 33336; $p->post_status = 'publish'; return $p; }
		return null;
	}
	function learnsimply_bundle_item_product_ids( array $ids ) { return array( 33336 => array( 24444, 31579 ) ); }
	function wc_get_product( $id ) {
		return new class() { public function get_price() { return '1000'; } public function get_name() { return 'كورس Java Basics + OOP'; } };
	}
	function tutor() { $o = new stdClass(); $o->course_post_type = 'courses'; $o->nonce_action = 'a'; $o->nonce = '_tutor_nonce'; return $o; }

	class LS_Posts_Stub { public $posts = array(); public function __construct( $posts ) { $this->posts = $posts; } }
	class LS_Tutor_Utils_Stub {
		public function get_course_rating( $id ) { $r = new stdClass(); $r->rating_avg = 24443 === $id ? 5.0 : 4.9; $r->rating_count = 24443 === $id ? 57 : 34; return $r; }
		public function is_enrolled( $a, $b ) { return $GLOBALS['ls_enrolled']; }
		public function is_course_purchasable( $id ) { return true; }
		public function get_course_first_lesson( $id ) { return "https://x/lesson/$id/1"; }
		public function get_raw_course_price( $id ) { $p = new stdClass(); $p->regular_price = 700; $p->sale_price = 24443 === $id ? 550 : 0; return $p; }
		public function price_type( $id ) { return 'paid'; }
		public function get_lesson_count_by_course( $id ) { return 80; }
		public function get_quiz_count_by_course( $id ) { return 1; }
		public function get_assignment_count_by_course( $id ) { return 0; }
		public function get_instructors_by_course( $id ) { $i = new stdClass(); $i->ID = 1; $i->display_name = 'أحمد عادل'; return array( $i ); }
		public function count_enrolled_users_by_course( $id ) { return 612; }
		public function get_course_product_id( $id ) { return $id + 1; }
		public function get_course_id_by_product( $pid ) { return $pid - 1; }
		public function get_topics( $id ) {
			$mk = function ( $id, $t ) { $p = new stdClass(); $p->ID = $id; $p->post_title = $t; return $p; };
			return new LS_Posts_Stub( array( $mk( 100, 'الوحدة الاولي' ), $mk( 101, 'الوحدة الثانية' ), $mk( 102, 'بيتم تحديث الكويزات ⏳' ) ) );
		}
		public function get_course_contents_by_topic( $topic_id, $n ) {
			if ( 102 === $topic_id ) { return new LS_Posts_Stub( array() ); }
			$mk = function ( $id, $t, $type ) { $p = new stdClass(); $p->ID = $id; $p->post_title = $t; $p->post_type = $type; return $p; };
			return new LS_Posts_Stub( array( $mk( 1000 + $topic_id - 100, 'درس ١', 'lesson' ), $mk( 2000 + $topic_id, 'درس ٢', 'lesson' ), $mk( 3000 + $topic_id, 'كويز', 'tutor_quiz' ) ) );
		}
		public function get_course_reviews( $id, $a, $b, $c, $d, $e ) {
			$out = array();
			foreach ( array( 'يزيد', 'أدهم', 'بسملة', 'آدم' ) as $i => $n ) { $r = new stdClass(); $r->comment_ID = $i; $r->display_name = $n; $r->rating = 5; $r->comment_content = 'شرح ممتاز'; $r->comment_date = '2026-08-01 00:00:00'; $r->comment_status = 'approved'; $out[] = $r; }
			return $out;
		}
		public function get_reviews_by_user() { return null; }
	}
	function tutor_utils() { return new LS_Tutor_Utils_Stub(); }

	require __DIR__ . '/../inc/academy-structure.php';
	require __DIR__ . '/../inc/course-content.php';
	require __DIR__ . '/../inc/course-extras.php';

	$fail = 0;
	function ls_check( $ok, $label ) { global $fail; echo ( $ok ? '  ✅ ' : '  ❌ ' ) . $label . "\n"; if ( ! $ok ) { $fail = 1; } }

	// ── جافا للمبتدئين — زائر ──
	require __DIR__ . '/../tutor/single-course.php';
	$ctx = Timber\Timber::$rendered[0][1];
	ls_check( 'single-course.twig' === Timber\Timber::$rendered[0][0], 'بيرندر single-course.twig' );
	ls_check( '550' === $ctx['price_label'] && true === (bool) $ctx['can_buy'] && 24444 === $ctx['product_id'], 'جافا: سعر واحد (550 = سعر البيع) · قابل للشرا · منتج ووكومرس' );
	ls_check( 'https://x/checkout/?add-to-cart=24444' === $ctx['checkout_url'], 'رابط الشيك أوت بالمنتج' );
	ls_check( 1 === $ctx['path']['position'] && 5 === $ctx['path']['total'] && 'مسار الأساسيات' === $ctx['path']['label'], 'جافا: الخطوة ١ من ٥ في مسار الأساسيات' );
	ls_check( null === $ctx['path']['prereq'] && 31578 === $ctx['path']['next']['id'], 'جافا: مفيش متطلب سابق · اللي بعده OOP' );
	ls_check( 'now' === $ctx['path']['steps'][0]['state'] && '' === $ctx['path']['steps'][1]['state'], 'حالة الخطوات: الأولى «أنت هنا»' );
	ls_check( 3 === count( $ctx['path']['rest'] ) && 31578 === $ctx['path']['rest'][0]['id'] && 2 === $ctx['path']['rest'][0]['position'] && '700' === $ctx['path']['rest'][0]['price_label'], 'كمّل المسار: ٣ كورسات بعده بسعر وموضع' );
	ls_check( 7 === count( $ctx['sections']['learn_groups'] ) && 16 === $ctx['sections']['learn_count'], 'جافا: «هتتعلم» ٧ مجموعات · 16 نقطة (من المحلّل)' );
	ls_check( 5 === count( $ctx['sections']['audience'] ) && 1 === count( $ctx['sections']['about'] ), 'جافا: لمين ٥ · عن الكورس فقرة' );
	ls_check( '' !== $ctx['tagline'] && false === mb_strpos( $ctx['tagline'], 'الكورس بيبدأ' ), 'جملة الهيرو = أول جملة من فقرة التعريف بس' );
	ls_check( 2 === $ctx['units_count'] && 3 === $ctx['topics'][0]['count'] && 'quiz' === $ctx['topics'][0]['contents'][2]['type'], 'المنهج: الوحدة الفاضية اتشالت · ٣ عناصر في الوحدة · الكويز نوعه quiz' );
	ls_check( 1 === $ctx['preview_count'] && 'https://x/p/1001' === $ctx['first_preview_url'], 'درس المعاينة اتعدّ ورابطه اتاخد' );
	ls_check( 'embedded' === $ctx['video']['source'] && ! empty( $ctx['video']['embed_code'] ), 'الفيديو التعريفي (embedded)' );
	ls_check( 'كورس Java Basics + OOP' === $ctx['bundle']['title'] && '1,000' === $ctx['bundle']['price_label'] && array( 'جافا للمبتدئين', 'Java OOP' ) === $ctx['bundle']['items'], 'تلميح الباقة: باقة جافا 1,000 بكورسيها' );
	ls_check( 3 === count( $ctx['extras']['build_items'] ) && 5 === count( $ctx['extras']['faq_items'] ), 'كروت «هتبني إيه» ٣ · أسئلة ٥ (من inc/course-extras.php)' );
	ls_check( 4 === count( $ctx['reviews'] ) && false === $ctx['can_write_review'], 'التقييمات ٤ · الزائر مش بيكتب تقييم' );
	ls_check( 81 === $ctx['lesson_count'] && 13 === $ctx['hours'] && 'مبتدئ' === $ctx['level'], 'الأرقام: 81 درس (دروس+كويز) · 13 ساعة · مبتدئ' );

	// ── Java OOP — مشترك ──
	$GLOBALS['ls_current'] = 31578;
	$GLOBALS['ls_enrolled'] = true;
	require __DIR__ . '/../tutor/single-course.php';
	ls_check( 2 === count( Timber\Timber::$rendered ), 'الكونترولر بيتضمّن مرتين من غير fatal (مفيش تعريف دوال جواه)' );
	$ctx = Timber\Timber::$rendered[1][1];
	ls_check( true === $ctx['is_enrolled'] && false === (bool) $ctx['can_buy'] && true === $ctx['can_write_review'], 'OOP مشترك: مفيش شرا · يقدر يكتب تقييم' );
	ls_check( 2 === $ctx['path']['position'] && 24443 === $ctx['path']['prereq']['id'] && 11287 === $ctx['path']['next']['id'], 'OOP: الخطوة ٢ · المتطلب جافا · بعده هياكل ١' );
	ls_check( 'done' === $ctx['path']['steps'][0]['state'] && 'now' === $ctx['path']['steps'][1]['state'], 'حالة الخطوات: جافا «قبله» · OOP «أنت هنا»' );
	ls_check( 'متوسط' === $ctx['level'] && 'intermediate' === $ctx['level_key'] && '700' === $ctx['price_label'], 'OOP: متوسط · 700 (مفيش سعر بيع)' );
	ls_check( 'مع نهاية الكورس هتكون' === $ctx['sections']['learn_title'] && 3 === $ctx['sections']['learn_count'] && empty( $ctx['sections']['outcome'] ), 'OOP: «هتتعلم» = نقط «مع نهاية الكورس هتكون» بعنوانها' );
	ls_check( 5 === count( $ctx['sections']['about'] ) && 1 === count( $ctx['sections']['audience'] ), 'OOP: ٥ فقرات · جملة جمهور واحدة' );
	ls_check( null === $ctx['video'] && 'https://x/img/31578.jpg' === $ctx['image'], 'OOP: مفيش فيديو → الصورة' );

	// للمعاينة المحلية: LS_DUMP_CTX=/path يكتب الـcontext بتاع الحالتين (زائر جافا · مشترك OOP) لرندر Twig حقيقي.
	if ( getenv( 'LS_DUMP_CTX' ) ) {
		file_put_contents( getenv( 'LS_DUMP_CTX' ) . '/ctx-java.ser', serialize( Timber\Timber::$rendered[0][1] ) );
		file_put_contents( getenv( 'LS_DUMP_CTX' ) . '/ctx-oop.ser', serialize( Timber\Timber::$rendered[1][1] ) );
	}

	echo $fail ? "  اختبار صفحة الكورس فشل\n" : "  اختبار تشغيل صفحة الكورس عدّى\n";
	exit( $fail );
}
