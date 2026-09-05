<?php
/**
 * اختبار صفحة الباقة — بيشغّل learnsimply_bundle_page_context() بـstubs.
 *
 * الحالة: باقة جافا زي ما هي على السيرفر — عناصرها بترتيب الإدخال: جافا · الكتاب · OOP.
 * المفروض: الكورسات تتربّ بالمستوى (جافا ← OOP) والكتاب يبقى هدية، والأرقام تتجمّع،
 * والتوفير يتحسب من أسعار الكورسات الحقيقية مش من «السعر قبل الخصم» الوهمي.
 *
 * التشغيل: php tests/bundle-page-test.php   (بيتنادى من tools-lint.sh)
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }

define( 'ABSPATH', '/' );
define( 'OBJECT', 'OBJECT' );

class WP_Error {}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function add_action() {} function add_filter() {} function update_post_meta() {} function update_option() {}
function get_option() { return false; }
function wp_strip_all_tags( $t ) { return strip_tags( $t ); }
function wp_trim_words( $t, $n, $more ) { $w = preg_split( '/\s+/', trim( $t ) ); return count( $w ) > $n ? implode( ' ', array_slice( $w, 0, $n ) ) . $more : $t; }
function get_permalink( $id ) { return "https://x/p/$id"; }
function get_post_status( $id ) { return 'publish'; }
function get_term_link( $slug, $tax ) { return "https://x/course-category/$slug/"; }
function get_the_title( $id ) { $t = array( 24443 => 'كورس جافا للمبتدئين + كتاب هدية', 31578 => 'البرمجة الكائنية (OOP) بلغة Java', 11287 => 'هياكل ١', 30816 => 'هياكل ٢', 39654 => 'Dart' ); return isset( $t[ $id ] ) ? $t[ $id ] : "Course $id"; }
function get_the_post_thumbnail_url( $id, $size ) { return "https://x/img/$id.jpg"; }
function wp_get_attachment_image_url( $id, $size ) { return $id ? "https://x/att/$id.png" : false; }
function learnsimply_no_image_url() { return 'https://x/none.png'; }
function has_excerpt( $id ) { return false; }
function get_the_excerpt( $id ) { return ''; }
function get_post_field( $f, $id ) { return ''; }
function apply_filters( $tag, $v ) { return $v; }
$GLOBALS['ls_fixtures'] = require __DIR__ . '/course-content-fixtures.php';
function get_post_meta( $id, $key, $single ) {
	if ( '_course_duration' === $key ) { return array( 'hours' => 13 ); }
	if ( '_tutor_course_level' === $key ) { return 31578 === $id ? 'intermediate' : 'beginner'; }
	if ( '_tutor_course_benefits' === $key ) { return implode( "\n", $GLOBALS['ls_fixtures'][ 24443 === $id ? 'java' : 'oop' ] ); }
	if ( 'ls_department' === $key ) { return in_array( $id, array( 24443, 31578, 11287, 30816 ), true ) ? 'foundations' : 'app-development'; }
	return '';
}
function get_page_by_path( $slug, $o, $t ) {
	if ( rawurlencode( 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ) === $slug ) { $p = new stdClass(); $p->ID = 39043; $p->post_status = 'publish'; return $p; }
	return null;
}

class LS_WC_Product_Stub {
	private $d;
	public function __construct( $d ) { $this->d = $d; }
	public function get_id() { return $this->d['id']; }
	public function get_name() { return $this->d['name']; }
	public function get_price() { return $this->d['price']; }
	public function get_image_id() { return $this->d['img']; }
	public function get_slug() { return isset( $this->d['slug'] ) ? $this->d['slug'] : ''; }
	public function get_description() { return isset( $this->d['desc'] ) ? $this->d['desc'] : ''; }
	public function get_short_description() { return isset( $this->d['short'] ) ? $this->d['short'] : ''; }
}
function wc_get_product( $id ) {
	$map = array(
		33336 => array( 'id' => 33336, 'name' => 'كورس Java Basics + OOP', 'price' => '999', 'img' => 1, 'slug' => 'java-basics-oop-bundle',
			'desc' => "🎓 حزمة الجافا الكاملة للطلاب والمبرمجين\n📌 تشمل كورس جافا للمبتدئين + كورس OOP المتقدم + كتاب PDF مجاني.\n💰 السعر بعد الخصم: 850 جنيه بدل 2150\n✅ شرح مبسط – تطبيق عملي." ),
		24444 => array( 'id' => 24444, 'name' => 'لغة جافا المستوي الاول', 'price' => '550', 'img' => 2 ),
		28056 => array( 'id' => 28056, 'name' => 'كتاب لغة جافا', 'price' => '120', 'img' => 3, 'short' => 'PDF فيه ملخصات وتمارين' ),
		31579 => array( 'id' => 31579, 'name' => 'Java OOP', 'price' => '700', 'img' => 4 ),
		39043 => array( 'id' => 39043, 'name' => 'هياكل البيانات الكاملة', 'price' => '999', 'img' => 5 ),
	);
	return isset( $map[ $id ] ) ? new LS_WC_Product_Stub( $map[ $id ] ) : null;
}
class LS_Tutor_Utils_Stub {
	public function get_course_id_by_product( $pid ) { return 28056 === $pid ? 0 : $pid - 1; }
	public function get_course_rating( $id ) { $r = new stdClass(); $r->rating_avg = 24443 === $id ? 5.0 : 4.9; $r->rating_count = 24443 === $id ? 57 : 34; return $r; }
	public function get_raw_course_price( $id ) { $p = new stdClass(); $prices = array( 24443 => 550, 31578 => 700, 11287 => 650, 30816 => 499 ); $p->regular_price = isset( $prices[ $id ] ) ? $prices[ $id ] : 600; $p->sale_price = 0; return $p; }
	public function get_lesson_count_by_course( $id ) { return 24443 === $id ? 81 : 96; }
	public function count_enrolled_users_by_course( $id ) { return 24443 === $id ? 612 : 540; }
	public function get_course_reviews() { $r = new stdClass(); $r->display_name = 'طالب'; $r->rating = 5; $r->comment_content = 'شرح ممتاز جدا وبسيط وواضح ومفهوم'; $r->comment_date = '2026-08-01'; return array( $r ); }
}
function tutor_utils() { return new LS_Tutor_Utils_Stub(); }

require __DIR__ . '/../inc/academy-structure.php';
require __DIR__ . '/../inc/course-content.php';
require __DIR__ . '/../inc/bundle-page.php';

$fail = 0;
function ls_check( $ok, $label ) { global $fail; echo ( $ok ? '  ✅ ' : '  ❌ ' ) . $label . "\n"; if ( ! $ok ) { $fail = 1; } }

// ترتيب الإدخال في جدول البلجن عن قصد: جافا · الكتاب · OOP
$ctx = learnsimply_bundle_page_context( wc_get_product( 33336 ), array( 24444, 28056, 31579 ) );

ls_check( array( 24443, 31578 ) === array_column( $ctx['courses'], 'id' ), 'الكورسات مرتّبة بالمستوى: جافا ← OOP' );
ls_check( 1 === count( $ctx['gifts'] ) && 'كتاب لغة جافا' === $ctx['gifts'][0]['title'], 'الكتاب بقى هدية مش كورس' );
ls_check( 26 === $ctx['hours'] && 177 === $ctx['lessons'] && 1152 === $ctx['students'], 'الأرقام المجمّعة: 26 ساعة · 177 درس · 1,152 طالب' );
ls_check( '5.0' === $ctx['rating_avg'] && 91 === $ctx['rating_count'], 'التقييم المجمّع مرجّح: 5.0 من 91' );
ls_check( '999' === $ctx['price_label'] && '1,250' === $ctx['separate_label'] && '251' === $ctx['save_label'], 'السعر من ووكومرس (999) · الكورسات لوحدها 1,250 · بتوفّر 251' );
ls_check( array( 1, 2 ) === $ctx['positions'] && 'مسار الأساسيات' === $ctx['path_label'], 'الباقة = الخطوتين ١ و٢ في مسار الأساسيات' );
ls_check( array( 11287, 30816 ) === array_column( $ctx['after'], 'id' ), 'وبعد الباقة: هياكل ١ ← هياكل ٢' );
ls_check( 'هياكل البيانات الكاملة' === $ctx['next_bundle']['title'] && '999' === $ctx['next_bundle']['price_label'], 'الباقة اللي بعدها: هياكل بسعرها من ووكومرس' );
ls_check( 3 === count( $ctx['about'] ) && false === mb_strpos( implode( ' ', $ctx['about'] ), 'جنيه' ), 'الوصف: سطر «السعر بعد الخصم 850» اتشال والإيموجي اتشالت' );
ls_check( 0 === mb_strpos( $ctx['tagline'], 'حزمة الجافا' ), 'جملة الهيرو من أول سطر في الوصف (مفيش مقتطف)' );
ls_check( 7 === count( $ctx['courses'][0]['learn'] ) && 'الأساسيات (Java Basics)' === $ctx['courses'][0]['learn'][0], 'هتتعلم إيه: مجموعات جافا السبعة' );
ls_check( 5 === count( $ctx['audience'] ), 'لمين: قائمة جافا (أول كورس عنده جمهور)' );
ls_check( 2 === count( $ctx['course_reviews'] ) && 'جافا للمبتدئين' === $ctx['course_reviews'][0]['source'], 'آراء الكورسات بمصدرها' );
ls_check( 6 === count( $ctx['faq'] ) && false !== mb_strpos( $ctx['faq'][1]['a'], 'جافا للمبتدئين' ), 'الأسئلة: ٦ منهم سؤال الكتاب وسؤال الترتيب بأسماء الكورسات' );
ls_check( array( 'مبتدئ', 'متوسط' ) === $ctx['levels'], 'المستويات: مبتدئ ← متوسط' );

// باقة من غير هدية ولا وصف ولا باقة بعدها
$ctx2 = learnsimply_bundle_page_context( new LS_WC_Product_Stub( array( 'id' => 5, 'name' => 'X', 'price' => '2000', 'img' => 0, 'slug' => 'x' ) ), array( 24444, 31579 ) );
ls_check( empty( $ctx2['gifts'] ) && empty( $ctx2['about'] ) && null === $ctx2['next_bundle'] && '' === $ctx2['save_label'], 'باقة أغلى من الكورسات لوحدها: مفيش «بتوفّر» · مفيش هدية · مفيش وصف' );
ls_check( 5 === count( $ctx2['faq'] ), 'من غير كتاب = ٥ أسئلة' );

if ( getenv( 'LS_DUMP_CTX' ) ) {
	file_put_contents( getenv( 'LS_DUMP_CTX' ) . '/ctx-bundle.ser', serialize( $ctx ) );
}

echo $fail ? "  اختبار صفحة الباقة فشل\n" : "  اختبار صفحة الباقة عدّى\n";
exit( $fail );
