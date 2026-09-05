<?php
/**
 * Front Page Template — الرئيسية
 *
 * إعادة بناء الرئيسية على بنية Code with Mosh (سبتمبر ٢٠٢٦):
 * هيرو ← الكورسات ← المسارات + الباقة الكاملة ← آراء الطلاب ← ليه اتعلم ببساطة
 * ← المدرّس ← الأسئلة ← CTA. راجع docs/MOSH-BLUEPRINT.md في ورشة التوثيق.
 *
 * كل النصوص من المنصة نفسها، وكل الأرقام من Tutor وWooCommerce وقت الطلب.
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

$context['theme_uri'] = get_stylesheet_directory_uri();

// ─────────────────────────────────────────────────────────────────────────────
// دوال مساعدة خاصة بالرئيسية — لازم تتعرّف هنا قبل أي استخدام:
// التعريف الشرطي (function_exists) مبيتحمّلش مسبقًا زي التعريف العادي، فلو جت في آخر الملف
// بتبقى غير معرّفة وقت النداء = صفحة بيضا. (ده اللي حصل على السيرفر ٥/٩.)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * إجمالي ساعات الكورس من meta المدة في Tutor (`_course_duration` = hours/minutes/seconds).
 *
 * @param int $course_id معرّف الكورس.
 * @return string مثل "13h" — فاضي لو مفيش مدة. الدقايق بتتجاهل عشان يطابق صفحة الكورس.
 */
if ( ! function_exists( 'learnsimply_home_course_hours' ) ) {
	function learnsimply_home_course_hours( $course_id ) {
		$duration = get_post_meta( $course_id, '_course_duration', true );
		if ( ! is_array( $duration ) ) {
			return '';
		}
		// الساعات كما هي من غير تقريب — نفس الرقم اللي صفحة الكورس بتعرضه («13 ساعات»).
		$hours = isset( $duration['hours'] ) ? (int) $duration['hours'] : 0;
		return $hours > 0 ? $hours . 'h' : '';
	}
}

/**
 * تسمية المستوى بالعربي. Tutor بيخزّن مفاتيح إنجليزي (beginner/intermediate/expert)
 * وأحيانًا نص عربي مباشر — الاتنين بيتقبلوا.
 */
if ( ! function_exists( 'learnsimply_home_level_label' ) ) {
	function learnsimply_home_level_label( $level ) {
		$map = array(
			'beginner'     => 'مبتدئ',
			'intermediate' => 'متوسط',
			'expert'       => 'متقدم',
			'all_levels'   => 'كل المستويات',
		);
		$key = strtolower( trim( (string) $level ) );
		if ( isset( $map[ $key ] ) ) {
			return $map[ $key ];
		}
		return '' !== $key ? $level : 'مبتدئ';
	}
}

/**
 * مفتاح CSS للمستوى (beginner / intermediate / expert).
 */
if ( ! function_exists( 'learnsimply_home_level_key' ) ) {
	function learnsimply_home_level_key( $level ) {
		$level = strtolower( trim( (string) $level ) );
		if ( in_array( $level, array( 'intermediate', 'متوسط' ), true ) ) {
			return 'intermediate';
		}
		if ( in_array( $level, array( 'expert', 'متقدم' ), true ) ) {
			return 'expert';
		}
		return 'beginner';
	}
}

/**
 * خطوات مسار من قسم في الأكاديمية، بترتيب المستوى، مع بيانات الكارت.
 *
 * @param string $department_slug سلاج القسم.
 * @param array  $courses_by_id   كورسات الصفحة مفهرسة بالمعرّف (للساعات والمستوى).
 * @return array<int,array<string,mixed>>
 */
if ( ! function_exists( 'learnsimply_home_path_steps' ) ) {
	function learnsimply_home_path_steps( $department_slug, $courses_by_id ) {
		if ( ! function_exists( 'learnsimply_get_department_courses' ) ) {
			return array();
		}
		$steps = array();
		foreach ( learnsimply_get_department_courses( $department_slug ) as $item ) {
			$course = isset( $courses_by_id[ $item['id'] ] ) ? $courses_by_id[ $item['id'] ] : null;
			$meta   = array();
			if ( $course && $course->hours ) {
				$meta[] = $course->hours;
			}
			if ( $course && $course->level_label ) {
				$meta[] = $course->level_label;
			}
			$short = learnsimply_home_short_title( $item['title'] );
			$steps[] = array(
				'id'          => $item['id'],
				'title'       => $short,
				'label'       => function_exists( 'learnsimply_course_short_label' ) ? learnsimply_course_short_label( $item['id'], $short ) : $short,
				'meta'        => implode( ' · ', $meta ),
				'url'         => $item['url'],
				'thumbnail'   => $item['thumbnail'] ? $item['thumbnail'] : ( $course ? $course->thumbnail : '' ),
				'coming_soon' => false,
			);
		}
		return $steps;
	}
}

/**
 * عنوان مختصر لكارت الخطوة: الجزء قبل أول «|» أو «+» — نفس عنوان الكورس بس من غير اللاحقة.
 */
if ( ! function_exists( 'learnsimply_home_short_title' ) ) {
	function learnsimply_home_short_title( $title ) {
		$short = preg_split( '/\s[|+]\s/u', (string) $title );
		return trim( $short[0] );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// الكورسات — استعلام واحد محدود، وترتيب بمنطق الأكاديمية مش بالتاريخ.
// الترتيب: الأساسيات (جافا ← OOP ← هياكل ١ ← هياكل ٢) ← تطوير التطبيقات (دارت)
// ← ابدأ من هنا (بايثون). أي كورس مش في الأقسام بييجي في الآخر بترتيب النشر.
// ─────────────────────────────────────────────────────────────────────────────
$context['courses'] = array();

$academy_order = array();
if ( function_exists( 'learnsimply_academy_departments' ) ) {
	$departments = learnsimply_academy_departments();
	uasort(
		$departments,
		function ( $a, $b ) {
			return $a['order'] - $b['order'];
		}
	);
	$position = 0;
	foreach ( $departments as $department ) {
		foreach ( $department['courses'] as $course_id ) {
			$academy_order[ (int) $course_id ] = $position++;
		}
	}
}

if ( function_exists( 'tutor_utils' ) ) {
	$courses_query = new WP_Query(
		array(
			'post_type'      => tutor()->course_post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$monetize_by_wc = ( 'wc' === tutor_utils()->get_option( 'monetize_by' ) ) && class_exists( 'WooCommerce' );

	foreach ( $courses_query->posts as $course_post ) {
		$course_id = $course_post->ID;
		$course    = Timber::get_post( $course_id );
		if ( ! $course ) {
			continue;
		}

		$rating               = tutor_utils()->get_course_rating( $course_id );
		$course->rating_avg   = ( $rating && $rating->rating_count > 0 ) ? round( (float) $rating->rating_avg, 1 ) : 0;
		$course->rating_count = $rating ? (int) $rating->rating_count : 0;

		// السعر: رقم واحد. الرئيسية الجديدة مبتعرضش «بدل» ولا شارة خصم (قرار أحمد ٥/٩).
		$price_info    = tutor_utils()->get_raw_course_price( $course_id );
		$regular_price = $price_info->regular_price ? (float) $price_info->regular_price : 0;
		$sale_price    = $price_info->sale_price ? (float) $price_info->sale_price : 0;
		$course->price = $sale_price > 0 ? $sale_price : $regular_price;
		$course->is_free = ( 'free' === tutor_utils()->price_type( $course_id ) ) || $course->price <= 0;
		$course->price_label = $course->is_free ? '' : number_format( $course->price, 0, '.', ',' );

		$course->hours          = learnsimply_home_course_hours( $course_id );
		$course->lesson_count   = (int) tutor_utils()->get_lesson_count_by_course( $course_id );
		$course->students_count = (int) tutor_utils()->count_enrolled_users_by_course( $course_id );
		$course->level_label    = learnsimply_home_level_label( get_post_meta( $course_id, '_tutor_course_level', true ) );
		$course->level_key      = learnsimply_home_level_key( get_post_meta( $course_id, '_tutor_course_level', true ) );

		// جملة الكارت: مقتطف الكورس من ووردبريس (نص المنصة نفسه)، مقصوص لـ١٨ كلمة.
		$excerpt = has_excerpt( $course_id ) ? get_the_excerpt( $course_id ) : '';
		$course->card_text = $excerpt ? wp_trim_words( wp_strip_all_tags( $excerpt ), 18, '…' ) : '';

		$course->thumbnail = get_the_post_thumbnail_url( $course_id, 'large' );
		if ( ! $course->thumbnail ) {
			$course->thumbnail = learnsimply_no_image_url();
		}

		$course->product_id = null;
		if ( $monetize_by_wc ) {
			$product_id = tutor_utils()->get_course_product_id( $course_id );
			$course->product_id = $product_id ? (int) $product_id : null;
		}

		$course->is_enrolled = is_user_logged_in() && tutor_utils()->is_enrolled( $course_id, get_current_user_id() );

		// موضعه في مسار الأكاديمية — بيتحكم في الترتيب على الصفحة.
		$course->academy_position = isset( $academy_order[ $course_id ] ) ? $academy_order[ $course_id ] : 1000;

		$context['courses'][] = $course;
	}
	wp_reset_postdata();

	usort(
		$context['courses'],
		function ( $a, $b ) {
			if ( $a->academy_position === $b->academy_position ) {
				return 0;
			}
			return $a->academy_position < $b->academy_position ? -1 : 1;
		}
	);
}

// «الأكثر مبيعًا» = أعلى كورس مدفوع في عدد المشتركين. قاعدة من الداتا مش اسم مكتوب بالإيد.
$bestseller_id = 0;
$bestseller_students = 0;
foreach ( $context['courses'] as $course ) {
	if ( ! $course->is_free && $course->students_count > $bestseller_students ) {
		$bestseller_students = $course->students_count;
		$bestseller_id       = $course->ID;
	}
}
foreach ( $context['courses'] as $course ) {
	$course->is_bestseller = ( $course->ID === $bestseller_id );
}

// ─────────────────────────────────────────────────────────────────────────────
// أرقام الثقة في الهيرو وقسم الآراء.
// التقييم والطلاب المشتركين محسوبين من Tutor. يوتيوب و«+10,000 طالب من 15 دولة»
// أرقام تسويقية من نص أحمد في قسم «عني» — مكانها الوحيد هنا عشان تتعدّل من سطر واحد.
// ─────────────────────────────────────────────────────────────────────────────
$rating_weighted = 0;
$rating_total    = 0;
$students_total  = 0;
foreach ( $context['courses'] as $course ) {
	$rating_weighted += $course->rating_avg * $course->rating_count;
	$rating_total    += $course->rating_count;
	$students_total  += $course->students_count;
}
$context['stats'] = array(
	'rating_avg'      => $rating_total > 0 ? number_format( $rating_weighted / $rating_total, 1 ) : '',
	'rating_count'    => number_format( $rating_total ),
	'students_total'  => number_format( $students_total ),
	'youtube'         => '403K',
	'students_claim'  => '+10,000',
	'countries_claim' => '15',
	'views_claim'     => '17M',
	'experience'      => '7+',
);

// ─────────────────────────────────────────────────────────────────────────────
// المسارات — «تايه مش عارف تبدأ إزاي؟»
// مسار الأساسيات: كورسات قسم الأساسيات ثم أول كورس في تطوير التطبيقات (دارت خطوة ٥).
// مسار التطبيقات: كورسات تطوير التطبيقات + كارت Flutter «قريبًا».
// الكورس الواحد ممكن يظهر في أكتر من مسار — ده مقصود (قرار أحمد ٥/٩).
// ─────────────────────────────────────────────────────────────────────────────
$courses_by_id = array();
foreach ( $context['courses'] as $course ) {
	$courses_by_id[ $course->ID ] = $course;
}

$foundations_steps = array_merge(
	learnsimply_home_path_steps( 'foundations', $courses_by_id ),
	learnsimply_home_path_steps( 'app-development', $courses_by_id )
);
$apps_steps = learnsimply_home_path_steps( 'app-development', $courses_by_id );
$apps_steps[] = array(
	'id'          => 0,
	'title'       => 'Flutter',
	'label'       => 'Flutter',
	'meta'        => 'قريبًا',
	'url'         => '',
	'thumbnail'   => '',
	'coming_soon' => true,
);

$context['paths'] = array(
	array(
		'key'      => 'foundations',
		'label'    => 'مسار الأساسيات',
		'title'    => 'ابدأ بـ Java من الصفر ☕ وبعدها هياكل البيانات 🧠',
		'text'     => 'هنا هتتعلم أساسيات البرمجة صح، وبعدها تدخل على OOP بشكل عملي. الهدف مش إنك تحفظ Syntax، الهدف إنك تفهم إزاي تفكر كمبرمج. وبعد ما تفهم الأساسيات، هياكل البيانات هي المرحلة اللي هتنقل مستواك بجد.',
		'cta'      => 'ابدأ مسار الأساسيات',
		'url'      => get_term_link( 'foundations', 'course-category' ),
		'primary'  => true,
		'steps'    => $foundations_steps,
	),
	array(
		'key'      => 'app-development',
		'label'    => 'مسار تطوير التطبيقات',
		'title'    => 'من Dart لـ Flutter 📱',
		'text'     => 'الأساس اللي أي مطوّر موبايل محترف بيبدأ منه. Dart هي لغة Flutter، فبنتقنها الأول وبعدين ندخل على بناء التطبيقات.',
		'cta'      => 'ابدأ مسار التطبيقات',
		'url'      => get_term_link( 'app-development', 'course-category' ),
		'primary'  => false,
		'steps'    => $apps_steps,
	),
);
foreach ( $context['paths'] as &$path ) {
	if ( is_wp_error( $path['url'] ) || empty( $path['url'] ) ) {
		$path['url'] = home_url( '/courses/' );
	}
}
unset( $path );

// ─────────────────────────────────────────────────────────────────────────────
// الباقات — ٣ كروت: باقة جافا (كورسين) · باقة هياكل البيانات (كورسين) · جميع الدورات.
// المنتجات بتتجاب بالسلاج، والسعر من ووكومرس، والكورسات اللي جوه الباقة من جدول بلجن
// الباقات (استعلام واحد محروس في inc/bundles.php). «جميع الدورات» مش من نوع البلجن،
// فمحتواها = كل كورسات الصفحة.
// ─────────────────────────────────────────────────────────────────────────────
$context['bundles'] = array();
if ( class_exists( 'WooCommerce' ) ) {
	$bundle_specs = array(
		// السلاج العربي متخزّن في ووردبريس مشفّر (percent-encoded) — بنجرّب الاتنين.
		array( 'slugs' => array( 'java-basics-oop-bundle' ), 'label' => 'باقة مسار Java', 'featured' => false, 'all' => false ),
		array( 'slugs' => array( rawurlencode( 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ), 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ), 'label' => 'باقة مسار هياكل البيانات', 'featured' => false, 'all' => false ),
		array( 'slugs' => array( 'all_in_one' ), 'label' => 'الباقة الكاملة', 'featured' => true, 'all' => true ),
	);

	$bundle_posts = array();
	foreach ( $bundle_specs as $i => $spec ) {
		foreach ( $spec['slugs'] as $slug ) {
			$post = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $post && 'publish' === $post->post_status ) {
				$bundle_posts[ $i ] = $post;
				break;
			}
		}
	}

	// كورسات الصفحة مفهرسة بمعرّف منتج ووكومرس بتاعها — عشان نربط عناصر الباقة بالكورسات
	$courses_by_product = array();
	foreach ( $context['courses'] as $course ) {
		if ( ! empty( $course->product_id ) ) {
			$courses_by_product[ (int) $course->product_id ] = $course;
		}
	}

	$plugin_bundle_ids = array();
	foreach ( $bundle_specs as $i => $spec ) {
		if ( ! $spec['all'] && isset( $bundle_posts[ $i ] ) ) {
			$plugin_bundle_ids[] = $bundle_posts[ $i ]->ID;
		}
	}
	$items_by_bundle = ( ! empty( $plugin_bundle_ids ) && function_exists( 'learnsimply_bundle_item_product_ids' ) )
		? learnsimply_bundle_item_product_ids( $plugin_bundle_ids )
		: array();

	foreach ( $bundle_specs as $i => $spec ) {
		if ( ! isset( $bundle_posts[ $i ] ) ) {
			continue;
		}
		$post    = $bundle_posts[ $i ];
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			continue;
		}

		$items = array();
		if ( $spec['all'] ) {
			$items = $context['courses'];
		} else {
			$product_ids = isset( $items_by_bundle[ $post->ID ] ) ? $items_by_bundle[ $post->ID ] : array();
			foreach ( $product_ids as $pid ) {
				if ( isset( $courses_by_product[ $pid ] ) ) {
					$items[] = $courses_by_product[ $pid ];
				}
			}
		}

		$hours = 0;
		$lessons = 0;
		$names = array();
		foreach ( $items as $item ) {
			$hours   += (int) $item->hours; // "13h" → 13
			$lessons += (int) $item->lesson_count;
			$names[]  = learnsimply_home_short_title( $item->title );
		}

		$price = (float) $product->get_price();
		$thumb = get_the_post_thumbnail_url( $post->ID, 'large' );
		$context['bundles'][] = array(
			'id'            => $post->ID,
			'label'         => $spec['label'],
			'title'         => $product->get_name(),
			'url'           => get_permalink( $post->ID ),
			'thumbnail'     => $thumb ? $thumb : ( ! empty( $items ) ? $items[0]->thumbnail : learnsimply_no_image_url() ),
			'price_label'   => $price > 0 ? number_format( $price, 0, '.', ',' ) : '',
			'items'         => $names,
			'courses_count' => count( $items ),
			'hours_total'   => $hours,
			'lessons_total' => $lessons,
			'featured'      => $spec['featured'],
		);
	}
}

Timber::render( 'front-page.twig', $context );
