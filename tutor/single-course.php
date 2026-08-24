<?php
/**
 * Template for displaying single course - Custom HTML Structure
 * 
 * Custom template with different HTML elements and classes (not using Tutor default structure)
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Timber is available
if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

// Check if Tutor LMS is active
if ( ! function_exists( 'tutor_utils' ) ) {
	echo 'Tutor LMS is not active';
	exit;
}


$course_id = get_the_ID();

// Get Timber context
$context = Timber::context();

// Add theme directory URI to context
$context['theme_uri'] = get_stylesheet_directory_uri();

// Add cart URL for JavaScript redirects
$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
if ( $cart_page_id && $cart_page_id > 0 ) {
	$context['cart_url'] = get_permalink( $cart_page_id );
} else {
	$context['cart_url'] = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/';
}

// Get course post using Timber
$course = Timber::get_post( $course_id );
$context['course'] = $course;

// Get course basic data
$context['course_title'] = get_the_title( $course_id );
$context['course_content'] = get_the_content( null, false, $course_id );
$context['course_excerpt'] = get_the_excerpt( $course_id );
// Editor-formatted description (paragraphs, lists, headings) for the description card
$context['course_description_html'] = apply_filters( 'the_content', get_the_content( null, false, $course_id ) );

// Get course rating
$course_rating = tutor_utils()->get_course_rating( $course_id );
$context['course_rating'] = $course_rating;
$context['rating_avg'] = $course_rating ? number_format( $course_rating->rating_avg, 1 ) : 0;
$context['rating_count'] = $course_rating ? $course_rating->rating_count : 0;

// Get enrollment status
$context['is_enrolled'] = tutor_utils()->is_enrolled( $course_id, get_current_user_id() );
$context['is_public'] = get_post_meta( $course_id, '_tutor_is_public_course', true ) == 'yes';
$context['is_purchasable'] = tutor_utils()->is_course_purchasable( $course_id );
$context['tutor_course_sell_by'] = apply_filters( 'tutor_course_sell_by', null );
$context['lesson_url'] = tutor_utils()->get_course_first_lesson( $course_id );

// Get course price (raw prices for proper formatting)
$price_info = tutor_utils()->get_raw_course_price( $course_id );
$context['regular_price'] = $price_info->regular_price ? floatval( $price_info->regular_price ) : null;
$context['sale_price'] = $price_info->sale_price ? floatval( $price_info->sale_price ) : null;
$context['display_price'] = $price_info->display_price;

// Format prices for display
if ( $context['regular_price'] ) {
	$context['regular_price_formatted'] = number_format( $context['regular_price'], 0, '.', ',' );
} else {
	$context['regular_price_formatted'] = null;
}

if ( $context['sale_price'] ) {
	$context['sale_price_formatted'] = number_format( $context['sale_price'], 0, '.', ',' );
} else {
	$context['sale_price_formatted'] = null;
}

// Calculate discount percentage
if ( $context['sale_price'] && $context['regular_price'] && $context['regular_price'] > 0 ) {
	$context['discount_percent'] = round( ( ( $context['regular_price'] - $context['sale_price'] ) / $context['regular_price'] ) * 100 );
} else {
	$context['discount_percent'] = 0;
}

// Check if course is free
$price_type = tutor_utils()->price_type( $course_id );
$context['is_free'] = ( $price_type === 'free' || ( ! $context['regular_price'] && ! $context['sale_price'] ) );

// Get course meta data
$context['course_duration'] = get_tutor_course_duration_context( $course_id );
$context['lesson_count'] = tutor_utils()->get_lesson_count_by_course( $course_id );
$context['quiz_count'] = tutor_utils()->get_quiz_count_by_course( $course_id );
$context['assignment_count'] = tutor_utils()->get_assignment_count_by_course( $course_id );
$context['course_level'] = get_tutor_course_level( $course_id );
if ( empty( $context['course_level'] ) ) {
	$context['course_level'] = 'مبتدئ';
}

// Get instructors
$instructors = tutor_utils()->get_instructors_by_course( $course_id );
$context['instructors'] = array();
if ( ! empty( $instructors ) ) {
	foreach ( $instructors as $instructor ) {
		if ( isset( $instructor->ID ) ) {
			$context['instructors'][] = Timber::get_user( $instructor->ID );
		}
	}
}

// Get students count
$context['students_count'] = tutor_utils()->count_enrolled_users_by_course( $course_id );

// Get enrolled students data (first 3 for avatars display)
$enrolled_students = tutor_utils()->get_students_data_by_course_id( $course_id, 'ID', true );
$context['students_avatars'] = array();
if ( ! empty( $enrolled_students ) && is_array( $enrolled_students ) ) {
	// Get first 3 students
	$students_to_show = array_slice( $enrolled_students, 0, 3 );
	foreach ( $students_to_show as $student ) {
		// Get student ID - it might be in ID property or as the object itself
		$student_id = isset( $student->ID ) ? $student->ID : ( is_object( $student ) ? $student->ID : $student );
		if ( $student_id ) {
			$avatar_url = get_avatar_url( $student_id, array( 'size' => 40 ) );
			$display_name = isset( $student->display_name ) ? $student->display_name : '';
			if ( $avatar_url ) {
				$context['students_avatars'][] = array(
					'id' => $student_id,
					'avatar' => $avatar_url,
					'name' => $display_name,
				);
			}
		}
	}
}

// Get course topics and content
$topics = tutor_utils()->get_topics( $course_id );
$context['topics'] = array();
$context['first_five_contents'] = array(); // First 5 content items for features list
$content_count = 0;
if ( $topics && $topics->have_posts() ) {
	while ( $topics->have_posts() ) {
		$topics->the_post();
		$topic_id = get_the_ID();
		$topic_contents = tutor_utils()->get_course_contents_by_topic( $topic_id, -1 );
		
		$topic_data = array(
			'id' => $topic_id,
			'title' => get_the_title(),
			'content' => get_the_content(),
			'contents' => array(),
		);
		
		if ( $topic_contents && $topic_contents->have_posts() ) {
			while ( $topic_contents->have_posts() ) {
				$topic_contents->the_post();
				$content_type = get_post_type();
				$content_id = get_the_ID();
				$content_duration = '';
				
				if ( 'lesson' === $content_type || 'tutor_lesson' === $content_type ) {
					$content_duration = get_post_meta( $content_id, '_video_duration', true );
				}
				
				// Check if lesson is a free preview
				$is_preview = get_post_meta( $content_id, '_is_preview', true );
				
				// Lesson permalink — used by the twig for the enrolled "go to lesson" link
				// and the non-enrolled preview link. Non-enrolled LOCKED rows never render
				// it (they link to #course-buy), so no paid-content URL is exposed.
				$content_url = get_permalink( $content_id );

				$content_item = array(
					'id' => $content_id,
					'type' => $content_type,
					'title' => get_the_title(),
					'duration' => $content_duration,
					'is_preview' => (bool) $is_preview,
					'url' => $content_url,
				);
				
				$topic_data['contents'][] = $content_item;
				
				// Collect first 5 content items for features list
				if ( $content_count < 5 ) {
					$context['first_five_contents'][] = $content_item;
					$content_count++;
				}
			}
			wp_reset_postdata();
		}
		
		$context['topics'][] = $topic_data;
	}
	wp_reset_postdata();
}

// Get course reviews (include current user's review even if pending, so they see "قيد المراجعة")
$current_user_id_for_reviews = get_current_user_id();
$course_reviews = tutor_utils()->get_course_reviews( $course_id, 0, 10, false, array( 'approved' ), $current_user_id_for_reviews );
$context['course_reviews'] = array();
if ( $course_reviews && is_array( $course_reviews ) ) {
	foreach ( $course_reviews as $review ) {
		$context['course_reviews'][] = array(
			'id' => $review->comment_ID,
			'author' => $review->display_name,
			'rating' => $review->rating,
			'content' => $review->comment_content,
			'date' => $review->comment_date,
			'avatar' => get_avatar_url( $review->user_id, array( 'size' => 40 ) ),
			'pending' => isset( $review->comment_status ) && $review->comment_status === 'hold',
		);
	}
}

// Check if user can write a review (enrolled users only)
$context['can_write_review'] = false;
$context['user_review'] = null;
$context['is_user_logged_in'] = is_user_logged_in();

// Add Tutor nonce for review form
$context['tutor_nonce'] = wp_create_nonce( tutor()->nonce_action );
$context['tutor_nonce_field'] = tutor()->nonce;

if ( $context['is_enrolled'] && is_user_logged_in() ) {
	$context['can_write_review'] = true;
	
	// Check if user already has a review
	$current_user_id = get_current_user_id();
	$my_rating = tutor_utils()->get_reviews_by_user( 0, 0, 150, false, $course_id, array( 'approved', 'hold' ) );
	
	if ( $my_rating && ! empty( $my_rating->rating ) ) {
		$context['user_review'] = array(
			'id' => $my_rating->comment_ID,
			'rating' => $my_rating->rating,
			'content' => stripslashes( $my_rating->comment_content ),
		);
	}
}

// Get course categories and tags
$categories = get_the_terms( $course_id, 'course-category' );
$context['categories'] = $categories && ! is_wp_error( $categories ) ? $categories : array();

$tags = get_the_terms( $course_id, 'course-tag' );
$context['tags'] = $tags && ! is_wp_error( $tags ) ? $tags : array();

// Get course meta fields
// Use tutor_course_benefits() function which returns an array of benefits
if ( function_exists( 'tutor_course_benefits' ) ) {
	$benefits_array = tutor_course_benefits( $course_id );
	// Convert array back to string with newlines for Twig template compatibility
	$context['course_benefits'] = ! empty( $benefits_array ) ? implode( "\n", $benefits_array ) : '';
} else {
$context['course_benefits'] = get_post_meta( $course_id, '_tutor_course_benefits', true );
}
$context['course_requirements'] = get_post_meta( $course_id, '_tutor_course_requirements', true );

// Target audience + material includes: same normalization as benefits above —
// Tutor may store these as arrays, and the twig splits them on newlines.
if ( function_exists( 'tutor_course_target_audience' ) ) {
	$audience_array = tutor_course_target_audience( $course_id );
	$context['course_target_audience'] = ! empty( $audience_array ) ? implode( "\n", (array) $audience_array ) : '';
} else {
	$audience_raw = get_post_meta( $course_id, '_tutor_course_target_audience', true );
	$context['course_target_audience'] = is_array( $audience_raw ) ? implode( "\n", $audience_raw ) : $audience_raw;
}

if ( function_exists( 'tutor_course_material_includes' ) ) {
	$materials_array = tutor_course_material_includes( $course_id );
	$context['course_material_includes'] = ! empty( $materials_array ) ? implode( "\n", (array) $materials_array ) : '';
} else {
	$materials_raw = get_post_meta( $course_id, '_tutor_course_material_includes', true );
	$context['course_material_includes'] = is_array( $materials_raw ) ? implode( "\n", $materials_raw ) : $materials_raw;
}

// Get course image
$context['course_image'] = get_the_post_thumbnail_url( $course_id, 'full' );
if ( ! $context['course_image'] ) {
	$context['course_image'] = learnsimply_no_image_url(); // Fallback image
}

// Get course intro video (if available)
$context['course_video'] = null;
$video_info = get_post_meta( $course_id, '_video', true );
if ( $video_info && is_array( $video_info ) && ! empty( $video_info['source'] ) ) {
	$video_data = array(
		'source' => $video_info['source'],
	);
	
	switch ( $video_info['source'] ) {
		case 'html5':
			// HTML5 video - get attachment URL
			if ( ! empty( $video_info['source_video_id'] ) ) {
				$video_data['url'] = wp_get_attachment_url( $video_info['source_video_id'] );
				$video_data['type'] = 'video/mp4';
			}
			// Get poster image
			if ( ! empty( $video_info['poster'] ) ) {
				$video_data['poster'] = wp_get_attachment_url( $video_info['poster'] );
			}
			break;
			
		case 'external_url':
			// External URL video
			if ( ! empty( $video_info['source_external_url'] ) ) {
				$video_data['url'] = $video_info['source_external_url'];
				$video_data['type'] = 'video/mp4';
			}
			// Get poster image
			if ( ! empty( $video_info['poster'] ) ) {
				$video_data['poster'] = wp_get_attachment_url( $video_info['poster'] );
			}
			break;
			
		case 'youtube':
			// YouTube video
			if ( ! empty( $video_info['source_youtube'] ) ) {
				// Extract video ID from YouTube URL
				$youtube_url = $video_info['source_youtube'];
				$video_id = '';
				
				// Match various YouTube URL formats
				if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches ) ) {
					$video_id = $matches[1];
				}
				
				if ( $video_id ) {
					$video_data['video_id'] = $video_id;
					$video_data['embed_url'] = 'https://www.youtube.com/embed/' . $video_id;
				}
			}
			break;
			
		case 'vimeo':
			// Vimeo video
			if ( ! empty( $video_info['source_vimeo'] ) ) {
				// Extract video ID from Vimeo URL
				$vimeo_url = $video_info['source_vimeo'];
				$video_id = '';
				
				if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $vimeo_url, $matches ) ) {
					$video_id = $matches[1];
				}
				
				if ( $video_id ) {
					$video_data['video_id'] = $video_id;
					$video_data['embed_url'] = 'https://player.vimeo.com/video/' . $video_id;
				}
			}
			break;
			
		case 'embedded':
			// Embedded code
			if ( ! empty( $video_info['source_embedded'] ) ) {
				$video_data['embed_code'] = $video_info['source_embedded'];
			}
			break;
	}
	
	// Only set video if we have valid data
	if ( isset( $video_data['url'] ) || isset( $video_data['embed_url'] ) || isset( $video_data['embed_code'] ) ) {
		$context['course_video'] = $video_data;
	}
}

// Get course updated date
$context['course_updated'] = get_the_modified_date( 'F j, Y', $course_id );

// Check if course has certificate
// Tutor Pro Certificate addon uses 'tutor_course_certificate_template' meta field
$certificate_template = get_post_meta( $course_id, 'tutor_course_certificate_template', true );
$context['has_certificate'] = ! empty( $certificate_template );

// Get WooCommerce product ID if course is sold via WooCommerce
$context['product_id'] = null;
if ( $context['tutor_course_sell_by'] === 'woocommerce' && class_exists( 'WooCommerce' ) ) {
	$context['product_id'] = tutor_utils()->get_course_product_id( $course_id );
}

// Get course content count (lessons + quizzes + assignments)
$context['content_count'] = $context['lesson_count'] + $context['quiz_count'] + $context['assignment_count'];

// Count preview lessons and get first preview URL
$preview_count = 0;
$first_preview_url = '';
if ( ! empty( $context['topics'] ) ) {
	foreach ( $context['topics'] as $topic ) {
		if ( ! empty( $topic['contents'] ) ) {
			foreach ( $topic['contents'] as $content ) {
				if ( ! empty( $content['is_preview'] ) ) {
					$preview_count++;
					if ( empty( $first_preview_url ) && ! empty( $content['url'] ) ) {
						$first_preview_url = $content['url'];
					}
				}
			}
		}
	}
}
$context['preview_count'] = $preview_count;
$context['first_preview_url'] = $first_preview_url;

/* ==========================================================================
   COURSE-SPECIFIC EXTRAS — "What you'll build" + FAQ per course
   --------------------------------------------------------------------------
   The "what-you-build" and "course-faq" sections in single-course.twig are
   driven from here. Each course has its own copy (Java ≠ Dart ≠ Python).
   The map is keyed by the course SLUG (post_name). Unknown courses fall
   back to a sensible default so a new course never shows wrong content.
   ========================================================================== */
$course_slug = $course ? $course->post_name : '';

/**
 * @param string $slug  Course slug (post_name).
 * @return array{build_items: array, faq_items: array}
 *   build_items[].kind = 'code' | 'oop' | 'flutter' | 'backend' | 'mobile' | 'python'
 *   build_items[].tag  = small label shown above the card title
 *   build_items[].title, .desc = card body
 *   faq_items[].q, .a   = question and answer
 */
function learnsimply_get_course_extras( $slug ) {
	$map = array(

		// ───────────────────────────────────────────────
		// DART — Flutter / Mobile focus
		// ───────────────────────────────────────────────
		'dart' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">void</span> main() {\n  print(<span class="lvc-str">\'Hello, Dart!\'</span>);\n  <span class="lvc-kw">for</span> (<span class="lvc-kw">var</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n    print(i);\n  }\n}',
					'tag'   => 'تطبيقي',
					'title' => 'أول Dart app ليك',
					'desc'  => 'هنكتب أول برنامج ليك من الصفر — variables, loops, functions.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'هنبني class كامل بـ inheritance و polymorphism — وهنفهم ليه OOP مهم قبل ما تدخل Flutter.',
				),
				array(
					'kind'  => 'flutter',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Flutter',
					'desc'  => 'لما تخلص الكورس، هتكون جاهز تبدأ رحلتك في Flutter — ودي الخطوة الطبيعية الجاية.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه، الكورس معمول للمبتدئين تماماً. بنبدأ من "إيه هو البرمجة" ونوصل لحد إنك تبني تطبيقات Dart كاملة. مش محتاج أي خلفية برمجية.',
				),
				array(
					'q' => 'إيه الفرق بين الكورس ده وكورسات Java اللي عندكم؟',
					'a' => 'Java للتطبيقات العامة (Backend, Android Enterprise, Big Data). Dart/Flutter للموبايل والويب الحديث. لو هدفك الموبايل، ابدأ بـ Dart. لو هدفك Backend، ابدأ بـ Java. الاتنين في الباقة لو حابب تجرب الاتنين.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس بيتحدث بشكل دوري مع كل إصدار جديد من Dart. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة، من غير أي مصاريف إضافية.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب — وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

		// ───────────────────────────────────────────────
		// JAVA — Backend / general-purpose focus
		// ───────────────────────────────────────────────
		'java-course-level1' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">public class</span> Main {\n  <span class="lvc-kw">public static void</span> main(String[] args) {\n    System.out.<span class="lvc-kw">println</span>(<span class="lvc-str">"Hello, Java!"</span>);\n    <span class="lvc-kw">for</span> (<span class="lvc-kw">int</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n      System.out.<span class="lvc-kw">println</span>(i);\n    }\n  }\n}',
					'tag'   => 'تطبيقي',
					'title' => 'أول Java app ليك',
					'desc'  => 'هنكتب أول Java program من الصفر — variables, loops, methods, الـ main class.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'Java = لغة OOP من الطراز الأول. هنغطي encapsulation, inheritance, polymorphism, interfaces بالتفصيل.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Backend',
					'desc'  => 'لما تخلص الكورس، هتكون جاهز تتعلم Spring Boot أو أي framework تاني.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه، الكورس معمول للمبتدئين تماماً. Java لغة ممتازة كأول لغة لأنها بتعلمك الـ fundamentals بشكل صارم.',
				),
				array(
					'q' => 'إيه اللي أقدر أعمله بعد ما أخلص الكورس؟',
					'a' => 'تقدر تتقدم لشغل Junior Java Developer، تتعلم Spring Boot للـ Backend، أو تدخل مجال الـ Android بـ Java.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس بيتحدث بشكل دوري مع كل إصدار جديد من Java. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب — وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

		// ───────────────────────────────────────────────
		// PYTHON PROJECTS — Free / project-focused
		// ───────────────────────────────────────────────
		'مشاريع-بايثون-للمبتدئين' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">def</span> greet(name):\n    <span class="lvc-kw">return</span> <span class="lvc-str">f\'Hello, {name}!\'</span>\n\n<span class="lvc-kw">for</span> i <span class="lvc-kw">in</span> <span class="lvc-kw">range</span>(<span class="lvc-num">5</span>):\n    print(greet(<span class="lvc-str">f\'World {i}\'</span>))',
					'tag'   => 'تطبيقي',
					'title' => 'أول Python script',
					'desc'  => 'هنكتب أول Python script من الصفر — functions, loops, string formatting.',
				),
				array(
					'kind'  => 'python',
					'tag'   => 'مشاريع',
					'title' => 'مشاريع حقيقية',
					'desc'  => 'مشاريع تطبيقية بتحاكي مشاكل حقيقية — to-do list, calculator, simple game.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Flask / Django',
					'desc'  => 'لما تخلص، هتكون جاهز تتعلم أي web framework أو حتى تدخل مجال الـ data science.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه! Python هي أسهل لغة تبدأ بيها. الكورس معمول خصيصاً للمبتدئين — هنبدأ من الصفر.',
				),
				array(
					'q' => 'إيه الفرق بين الكورس ده وباقي كورسات Python على اليوتيوب؟',
					'a' => 'الفرق إن الكورس ده مبني على مشاريع تطبيقية. مش بس syntax — كل درس بينتهي بمشروع صغير تضيفه للـ Portfolio بتاعك.',
				),
				array(
					'q' => 'هل الكورس مجاني فعلاً؟',
					'a' => 'أيوه، الكورس مجاني تماماً. من غير اشتراك، من غير بطاقة ائتمان. كل اللي محتاجه إنك تسجل في الموقع.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب.',
				),
				array(
					'q' => 'إيه الكورس المناسب اللي بعده؟',
					'a' => 'بعد ما تخلص، أنصحك بـ "أساسيات Dart" لو هدفك الموبايل، أو "جافا" لو هدفك Backend متكامل.',
				),
			),
		),

	);

	// Fallback: use the Dart content for any course we haven't mapped yet.
	// This way a new course never shows wrong content — it shows the most
	// useful default. You can add a real entry above for each new course.
	$fallback = isset( $map['dart'] ) ? $map['dart'] : array(
		'build_items' => array(),
		'faq_items'   => array(),
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : $fallback;
}

$context['course_extras'] = learnsimply_get_course_extras( $course_slug );
$context['course_slug']   = $course_slug;

// Render the template
Timber::render( 'single-course.twig', $context );
