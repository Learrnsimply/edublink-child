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
	$context['course_level'] = 'Ù…Ø¨ØªØ¯Ø¦';
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
				
				// Lesson permalink â€” used by the twig for the enrolled "go to lesson" link
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

// Get course reviews (include current user's review even if pending, so they see "Ù‚ÙŠØ¯ Ø§Ù„Ù…Ø±Ø§Ø¬Ø¹Ø©")
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

// Target audience + material includes: same normalization as benefits above â€”
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
   COURSE-SPECIFIC EXTRAS â€” "What you'll build" + FAQ per course
   --------------------------------------------------------------------------
   The "what-you-build" and "course-faq" sections in single-course.twig are
   driven from here. Each course has its own copy (Java â‰  Dart â‰  Python).
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

		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
		// DART â€” Flutter / Mobile focus
		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
		'dart' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">void</span> main() {\n  print(<span class="lvc-str">\'Hello, Dart!\'</span>);\n  <span class="lvc-kw">for</span> (<span class="lvc-kw">var</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n    print(i);\n  }\n}',
					'tag'   => 'ØªØ·Ø¨ÙŠÙ‚ÙŠ',
					'title' => 'Ø£ÙˆÙ„ Dart app Ù„ÙŠÙƒ',
					'desc'  => 'Ù‡Ù†ÙƒØªØ¨ Ø£ÙˆÙ„ Ø¨Ø±Ù†Ø§Ù…Ø¬ Ù„ÙŠÙƒ Ù…Ù† Ø§Ù„ØµÙØ± â€” variables, loops, functions.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'Ù‡Ù†Ø¨Ù†ÙŠ class ÙƒØ§Ù…Ù„ Ø¨Ù€ inheritance Ùˆ polymorphism â€” ÙˆÙ‡Ù†ÙÙ‡Ù… Ù„ÙŠÙ‡ OOP Ù…Ù‡Ù… Ù‚Ø¨Ù„ Ù…Ø§ ØªØ¯Ø®Ù„ Flutter.',
				),
				array(
					'kind'  => 'flutter',
					'tag'   => 'Ø¬Ø§Ù‡Ø²ÙŠØ©',
					'title' => 'Ù…Ø³ØªØ¹Ø¯ Ù„Ù€ Flutter',
					'desc'  => 'Ù„Ù…Ø§ ØªØ®Ù„Øµ Ø§Ù„ÙƒÙˆØ±Ø³ØŒ Ù‡ØªÙƒÙˆÙ† Ø¬Ø§Ù‡Ø² ØªØ¨Ø¯Ø£ Ø±Ø­Ù„ØªÙƒ ÙÙŠ Flutter â€” ÙˆØ¯ÙŠ Ø§Ù„Ø®Ø·ÙˆØ© Ø§Ù„Ø·Ø¨ÙŠØ¹ÙŠØ© Ø§Ù„Ø¬Ø§ÙŠØ©.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ Ù…Ù†Ø§Ø³Ø¨ Ù„Ùˆ Ø¹Ù…Ø±ÙŠ Ù…Ø§ Ø¨Ø±Ù…Ø¬Øª Ù‚Ø¨Ù„ ÙƒØ¯Ù‡ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡ØŒ Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø¹Ù…ÙˆÙ„ Ù„Ù„Ù…Ø¨ØªØ¯Ø¦ÙŠÙ† ØªÙ…Ø§Ù…Ø§Ù‹. Ø¨Ù†Ø¨Ø¯Ø£ Ù…Ù† "Ø¥ÙŠÙ‡ Ù‡Ùˆ Ø§Ù„Ø¨Ø±Ù…Ø¬Ø©" ÙˆÙ†ÙˆØµÙ„ Ù„Ø­Ø¯ Ø¥Ù†Ùƒ ØªØ¨Ù†ÙŠ ØªØ·Ø¨ÙŠÙ‚Ø§Øª Dart ÙƒØ§Ù…Ù„Ø©. Ù…Ø´ Ù…Ø­ØªØ§Ø¬ Ø£ÙŠ Ø®Ù„ÙÙŠØ© Ø¨Ø±Ù…Ø¬ÙŠØ©.',
				),
				array(
					'q' => 'Ø¥ÙŠÙ‡ Ø§Ù„ÙØ±Ù‚ Ø¨ÙŠÙ† Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ ÙˆÙƒÙˆØ±Ø³Ø§Øª Java Ø§Ù„Ù„ÙŠ Ø¹Ù†Ø¯ÙƒÙ…ØŸ',
					'a' => 'Java Ù„Ù„ØªØ·Ø¨ÙŠÙ‚Ø§Øª Ø§Ù„Ø¹Ø§Ù…Ø© (Backend, Android Enterprise, Big Data). Dart/Flutter Ù„Ù„Ù…ÙˆØ¨Ø§ÙŠÙ„ ÙˆØ§Ù„ÙˆÙŠØ¨ Ø§Ù„Ø­Ø¯ÙŠØ«. Ù„Ùˆ Ù‡Ø¯ÙÙƒ Ø§Ù„Ù…ÙˆØ¨Ø§ÙŠÙ„ØŒ Ø§Ø¨Ø¯Ø£ Ø¨Ù€ Dart. Ù„Ùˆ Ù‡Ø¯ÙÙƒ BackendØŒ Ø§Ø¨Ø¯Ø£ Ø¨Ù€ Java. Ø§Ù„Ø§ØªÙ†ÙŠÙ† ÙÙŠ Ø§Ù„Ø¨Ø§Ù‚Ø© Ù„Ùˆ Ø­Ø§Ø¨Ø¨ ØªØ¬Ø±Ø¨ Ø§Ù„Ø§ØªÙ†ÙŠÙ†.',
				),
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¨ÙŠØªØ¬Ø¯Ø¯ØŸ ÙˆÙ„Ùˆ Ø§Ø´ØªØ±ÙŠØªØŒ Ù‡Ø§Ø®Ø¯ Ø§Ù„ØªØ­Ø¯ÙŠØ«Ø§Øª Ø¨Ø¨Ù„Ø§Ø´ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡ØŒ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¨ÙŠØªØ­Ø¯Ø« Ø¨Ø´ÙƒÙ„ Ø¯ÙˆØ±ÙŠ Ù…Ø¹ ÙƒÙ„ Ø¥ØµØ¯Ø§Ø± Ø¬Ø¯ÙŠØ¯ Ù…Ù† Dart. Ø£ÙŠ ØªØ­Ø¯ÙŠØ« Ø¨Ù†Ø¶ÙŠÙÙ‡ Ø¨ÙŠÙƒÙˆÙ† Ù…ØªØ§Ø­ Ù„ÙƒÙ„ Ø§Ù„Ù„ÙŠ Ø§Ø´ØªØ±ÙˆØ§ Ø§Ù„ÙƒÙˆØ±Ø³ â€” Ù…Ø¯Ù‰ Ø§Ù„Ø­ÙŠØ§Ø©ØŒ Ù…Ù† ØºÙŠØ± Ø£ÙŠ Ù…ØµØ§Ø±ÙŠÙ Ø¥Ø¶Ø§ÙÙŠØ©.',
				),
				array(
					'q' => 'Ù„Ùˆ Ù…Ø´ ÙÙ‡Ù…Øª Ø¯Ø±Ø³ØŒ ÙÙŠÙ‡ Ø¯Ø¹Ù…ØŸ',
					'a' => 'Ø£ÙƒÙŠØ¯. Ø£ÙŠ Ø³Ø¤Ø§Ù„ ÙŠØ¬ÙŠÙ„ÙƒØŒ ØªÙ‚Ø¯Ø± ØªØ¨Ø¹Øª Ù…Ù† Ø®Ù„Ø§Ù„ Ø¬Ø±ÙˆØ¨ Ø§Ù„Ù€ Telegram Ø§Ù„Ù…Ø®ØµØµ Ù„Ù„Ø·Ù„Ø§Ø¨ â€” ÙˆØºØ§Ù„Ø¨Ø§Ù‹ Ø¨ÙŠØ±Ø¯ Ø¹Ù„ÙŠÙƒ Ø§Ù„Ù…Ø¯Ø±Ø¨ Ù†ÙØ³Ù‡ (Ø£Ø­Ù…Ø¯) Ø£Ùˆ Ø­Ø¯ Ù…Ù† Ø§Ù„Ù…Ø³Ø§Ø¹Ø¯ÙŠÙ† ÙÙŠ Ø£Ù‚Ù„ Ù…Ù† 24 Ø³Ø§Ø¹Ø©.',
				),
				array(
					'q' => 'Ø¶Ù…Ø§Ù† Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ø§Ù„ÙÙ„ÙˆØ³ Ø´ØºØ§Ù„ Ø¥Ø²Ø§ÙŠØŸ',
					'a' => 'Ù„Ùˆ Ø®Ù„Ø§Ù„ Ø£ÙˆÙ„ 7 Ø£ÙŠØ§Ù… Ù…Ù† Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ Ø­Ø³ÙŠØª Ø¥Ù† Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø´ Ù…Ù†Ø§Ø³Ø¨ÙƒØŒ Ø§Ø¨Ø¹ØªÙ„Ù†Ø§ ÙˆÙ‡Ù†Ø±Ø¬Ø¹Ù„Ùƒ ÙÙ„ÙˆØ³Ùƒ Ø¨Ø§Ù„ÙƒØ§Ù…Ù„ â€” Ù…Ù† ØºÙŠØ± Ø£ÙŠ Ø£Ø³Ø¦Ù„Ø©.',
				),
			),
		),

		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
		// JAVA â€” Backend / general-purpose focus
		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
		'java-course-level1' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">public class</span> Main {\n  <span class="lvc-kw">public static void</span> main(String[] args) {\n    System.out.<span class="lvc-kw">println</span>(<span class="lvc-str">"Hello, Java!"</span>);\n    <span class="lvc-kw">for</span> (<span class="lvc-kw">int</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n      System.out.<span class="lvc-kw">println</span>(i);\n    }\n  }\n}',
					'tag'   => 'ØªØ·Ø¨ÙŠÙ‚ÙŠ',
					'title' => 'Ø£ÙˆÙ„ Java app Ù„ÙŠÙƒ',
					'desc'  => 'Ù‡Ù†ÙƒØªØ¨ Ø£ÙˆÙ„ Java program Ù…Ù† Ø§Ù„ØµÙØ± â€” variables, loops, methods, Ø§Ù„Ù€ main class.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'Java = Ù„ØºØ© OOP Ù…Ù† Ø§Ù„Ø·Ø±Ø§Ø² Ø§Ù„Ø£ÙˆÙ„. Ù‡Ù†ØºØ·ÙŠ encapsulation, inheritance, polymorphism, interfaces Ø¨Ø§Ù„ØªÙØµÙŠÙ„.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'Ø¬Ø§Ù‡Ø²ÙŠØ©',
					'title' => 'Ù…Ø³ØªØ¹Ø¯ Ù„Ù€ Backend',
					'desc'  => 'Ù„Ù…Ø§ ØªØ®Ù„Øµ Ø§Ù„ÙƒÙˆØ±Ø³ØŒ Ù‡ØªÙƒÙˆÙ† Ø¬Ø§Ù‡Ø² ØªØªØ¹Ù„Ù… Spring Boot Ø£Ùˆ Ø£ÙŠ framework ØªØ§Ù†ÙŠ.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ Ù…Ù†Ø§Ø³Ø¨ Ù„Ùˆ Ø¹Ù…Ø±ÙŠ Ù…Ø§ Ø¨Ø±Ù…Ø¬Øª Ù‚Ø¨Ù„ ÙƒØ¯Ù‡ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡ØŒ Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø¹Ù…ÙˆÙ„ Ù„Ù„Ù…Ø¨ØªØ¯Ø¦ÙŠÙ† ØªÙ…Ø§Ù…Ø§Ù‹. Java Ù„ØºØ© Ù…Ù…ØªØ§Ø²Ø© ÙƒØ£ÙˆÙ„ Ù„ØºØ© Ù„Ø£Ù†Ù‡Ø§ Ø¨ØªØ¹Ù„Ù…Ùƒ Ø§Ù„Ù€ fundamentals Ø¨Ø´ÙƒÙ„ ØµØ§Ø±Ù….',
				),
				array(
					'q' => 'Ø¥ÙŠÙ‡ Ø§Ù„Ù„ÙŠ Ø£Ù‚Ø¯Ø± Ø£Ø¹Ù…Ù„Ù‡ Ø¨Ø¹Ø¯ Ù…Ø§ Ø£Ø®Ù„Øµ Ø§Ù„ÙƒÙˆØ±Ø³ØŸ',
					'a' => 'ØªÙ‚Ø¯Ø± ØªØªÙ‚Ø¯Ù… Ù„Ø´ØºÙ„ Junior Java DeveloperØŒ ØªØªØ¹Ù„Ù… Spring Boot Ù„Ù„Ù€ BackendØŒ Ø£Ùˆ ØªØ¯Ø®Ù„ Ù…Ø¬Ø§Ù„ Ø§Ù„Ù€ Android Ø¨Ù€ Java.',
				),
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¨ÙŠØªØ¬Ø¯Ø¯ØŸ ÙˆÙ„Ùˆ Ø§Ø´ØªØ±ÙŠØªØŒ Ù‡Ø§Ø®Ø¯ Ø§Ù„ØªØ­Ø¯ÙŠØ«Ø§Øª Ø¨Ø¨Ù„Ø§Ø´ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡ØŒ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¨ÙŠØªØ­Ø¯Ø« Ø¨Ø´ÙƒÙ„ Ø¯ÙˆØ±ÙŠ Ù…Ø¹ ÙƒÙ„ Ø¥ØµØ¯Ø§Ø± Ø¬Ø¯ÙŠØ¯ Ù…Ù† Java. Ø£ÙŠ ØªØ­Ø¯ÙŠØ« Ø¨Ù†Ø¶ÙŠÙÙ‡ Ø¨ÙŠÙƒÙˆÙ† Ù…ØªØ§Ø­ Ù„ÙƒÙ„ Ø§Ù„Ù„ÙŠ Ø§Ø´ØªØ±ÙˆØ§ Ø§Ù„ÙƒÙˆØ±Ø³ â€” Ù…Ø¯Ù‰ Ø§Ù„Ø­ÙŠØ§Ø©.',
				),
				array(
					'q' => 'Ù„Ùˆ Ù…Ø´ ÙÙ‡Ù…Øª Ø¯Ø±Ø³ØŒ ÙÙŠÙ‡ Ø¯Ø¹Ù…ØŸ',
					'a' => 'Ø£ÙƒÙŠØ¯. Ø£ÙŠ Ø³Ø¤Ø§Ù„ ÙŠØ¬ÙŠÙ„ÙƒØŒ ØªÙ‚Ø¯Ø± ØªØ¨Ø¹Øª Ù…Ù† Ø®Ù„Ø§Ù„ Ø¬Ø±ÙˆØ¨ Ø§Ù„Ù€ Telegram Ø§Ù„Ù…Ø®ØµØµ Ù„Ù„Ø·Ù„Ø§Ø¨ â€” ÙˆØºØ§Ù„Ø¨Ø§Ù‹ Ø¨ÙŠØ±Ø¯ Ø¹Ù„ÙŠÙƒ Ø§Ù„Ù…Ø¯Ø±Ø¨ Ù†ÙØ³Ù‡ (Ø£Ø­Ù…Ø¯) Ø£Ùˆ Ø­Ø¯ Ù…Ù† Ø§Ù„Ù…Ø³Ø§Ø¹Ø¯ÙŠÙ† ÙÙŠ Ø£Ù‚Ù„ Ù…Ù† 24 Ø³Ø§Ø¹Ø©.',
				),
				array(
					'q' => 'Ø¶Ù…Ø§Ù† Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ø§Ù„ÙÙ„ÙˆØ³ Ø´ØºØ§Ù„ Ø¥Ø²Ø§ÙŠØŸ',
					'a' => 'Ù„Ùˆ Ø®Ù„Ø§Ù„ Ø£ÙˆÙ„ 7 Ø£ÙŠØ§Ù… Ù…Ù† Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ Ø­Ø³ÙŠØª Ø¥Ù† Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø´ Ù…Ù†Ø§Ø³Ø¨ÙƒØŒ Ø§Ø¨Ø¹ØªÙ„Ù†Ø§ ÙˆÙ‡Ù†Ø±Ø¬Ø¹Ù„Ùƒ ÙÙ„ÙˆØ³Ùƒ Ø¨Ø§Ù„ÙƒØ§Ù…Ù„ â€” Ù…Ù† ØºÙŠØ± Ø£ÙŠ Ø£Ø³Ø¦Ù„Ø©.',
				),
			),
		),

		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

		// ───────────────────────────────────────────────
		// JAVA OOP — Object-Oriented Programming focus
		// ───────────────────────────────────────────────
		'javaoop' => array(
			'build_items' => array(
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'تعلم OOP Java',
					'desc'  => 'هتفهم الـ classes, objects, inheritance, polymorphism, encapsulation — كل اللي محتاجه عشان تبني Java code محترف.',
				),
				array(
					'kind'  => 'python',  // projects card style (gradient icon)
					'tag'   => 'مشاريع',
					'title' => 'مشاريع OOP',
					'desc'  => 'مشاريع تطبيقية بتحاكي مشاكل حقيقية — هنبني banking system, employee management, game characters.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'الخطوة التانية',
					'title' => 'جاهز للـ Backend',
					'desc'  => 'بعد ما تتقن OOP، هتكون جاهز تتعلم Spring Boot أو تدخل مجال الـ software development بشكل احترافي.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل لازم أكون خلصت كورس Java الأساسي قبل ما أبدأ OOP؟',
					'a' => 'الأفضل تكون عارف أساسيات Java (variables, loops, methods, if/else). لو مش عارفهم، كورس "جافا للمبتدئين" هيكون أحسن بداية ليك.',
				),
				array(
					'q' => 'استرداد الفلوس خلال 7 أيام من شراء الكورس — إزاي بيشتغل؟',
					'a' => 'بعد ما تشترك في الكورس، عندك 7 أيام كاملة تجربه براحتك. لو خلالهم حسيت إن الكورس مش مناسبك لأي سبب، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة أو شروط معقدة. كل اللي محتاجه رسالة واحدة وبس.',
				),
				array(
					'q' => 'هل الكورس ده نظري ولا تطبيقي؟',
					'a' => 'الكورس تطبيقي 100%. كل درس بينتهي بمشروع صغير أو تمرين تطبقه بنفسك. مش هنقعد نقرأ theory بس — هنبني حاجات فعلية.',
				),
				array(
					'q' => 'إيه اللي يقدر يعمله بعد الكورس ده؟',
					'a' => 'هتقدر تتقدم لشغل Junior Backend Developer، تكمل على Spring Boot، أو تاخد أي interview OOP-related بثقة.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس يتحدث مع كل إصدار جديد من Java. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة، من غير أي مصاريف إضافية.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي عملياً؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا من خلال صفحة "تواصل معنا" أو الإيميل، وهنرد عليك في أقل من 24 ساعة ونرجعلك فلوسك بالكامل.',
				),
			),
		),
		// PYTHON PROJECTS â€” Free / project-focused
		// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
		'Ù…Ø´Ø§Ø±ÙŠØ¹-Ø¨Ø§ÙŠØ«ÙˆÙ†-Ù„Ù„Ù…Ø¨ØªØ¯Ø¦ÙŠÙ†' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">def</span> greet(name):\n    <span class="lvc-kw">return</span> <span class="lvc-str">f\'Hello, {name}!\'</span>\n\n<span class="lvc-kw">for</span> i <span class="lvc-kw">in</span> <span class="lvc-kw">range</span>(<span class="lvc-num">5</span>):\n    print(greet(<span class="lvc-str">f\'World {i}\'</span>))',
					'tag'   => 'ØªØ·Ø¨ÙŠÙ‚ÙŠ',
					'title' => 'Ø£ÙˆÙ„ Python script',
					'desc'  => 'Ù‡Ù†ÙƒØªØ¨ Ø£ÙˆÙ„ Python script Ù…Ù† Ø§Ù„ØµÙØ± â€” functions, loops, string formatting.',
				),
				array(
					'kind'  => 'python',
					'tag'   => 'Ù…Ø´Ø§Ø±ÙŠØ¹',
					'title' => 'Ù…Ø´Ø§Ø±ÙŠØ¹ Ø­Ù‚ÙŠÙ‚ÙŠØ©',
					'desc'  => 'Ù…Ø´Ø§Ø±ÙŠØ¹ ØªØ·Ø¨ÙŠÙ‚ÙŠØ© Ø¨ØªØ­Ø§ÙƒÙŠ Ù…Ø´Ø§ÙƒÙ„ Ø­Ù‚ÙŠÙ‚ÙŠØ© â€” to-do list, calculator, simple game.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'Ø¬Ø§Ù‡Ø²ÙŠØ©',
					'title' => 'Ù…Ø³ØªØ¹Ø¯ Ù„Ù€ Flask / Django',
					'desc'  => 'Ù„Ù…Ø§ ØªØ®Ù„ØµØŒ Ù‡ØªÙƒÙˆÙ† Ø¬Ø§Ù‡Ø² ØªØªØ¹Ù„Ù… Ø£ÙŠ web framework Ø£Ùˆ Ø­ØªÙ‰ ØªØ¯Ø®Ù„ Ù…Ø¬Ø§Ù„ Ø§Ù„Ù€ data science.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ Ù…Ù†Ø§Ø³Ø¨ Ù„Ùˆ Ø¹Ù…Ø±ÙŠ Ù…Ø§ Ø¨Ø±Ù…Ø¬Øª Ù‚Ø¨Ù„ ÙƒØ¯Ù‡ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡! Python Ù‡ÙŠ Ø£Ø³Ù‡Ù„ Ù„ØºØ© ØªØ¨Ø¯Ø£ Ø¨ÙŠÙ‡Ø§. Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø¹Ù…ÙˆÙ„ Ø®ØµÙŠØµØ§Ù‹ Ù„Ù„Ù…Ø¨ØªØ¯Ø¦ÙŠÙ† â€” Ù‡Ù†Ø¨Ø¯Ø£ Ù…Ù† Ø§Ù„ØµÙØ±.',
				),
				array(
					'q' => 'Ø¥ÙŠÙ‡ Ø§Ù„ÙØ±Ù‚ Ø¨ÙŠÙ† Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ ÙˆØ¨Ø§Ù‚ÙŠ ÙƒÙˆØ±Ø³Ø§Øª Python Ø¹Ù„Ù‰ Ø§Ù„ÙŠÙˆØªÙŠÙˆØ¨ØŸ',
					'a' => 'Ø§Ù„ÙØ±Ù‚ Ø¥Ù† Ø§Ù„ÙƒÙˆØ±Ø³ Ø¯Ù‡ Ù…Ø¨Ù†ÙŠ Ø¹Ù„Ù‰ Ù…Ø´Ø§Ø±ÙŠØ¹ ØªØ·Ø¨ÙŠÙ‚ÙŠØ©. Ù…Ø´ Ø¨Ø³ syntax â€” ÙƒÙ„ Ø¯Ø±Ø³ Ø¨ÙŠÙ†ØªÙ‡ÙŠ Ø¨Ù…Ø´Ø±ÙˆØ¹ ØµØºÙŠØ± ØªØ¶ÙŠÙÙ‡ Ù„Ù„Ù€ Portfolio Ø¨ØªØ§Ø¹Ùƒ.',
				),
				array(
					'q' => 'Ù‡Ù„ Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø¬Ø§Ù†ÙŠ ÙØ¹Ù„Ø§Ù‹ØŸ',
					'a' => 'Ø£ÙŠÙˆÙ‡ØŒ Ø§Ù„ÙƒÙˆØ±Ø³ Ù…Ø¬Ø§Ù†ÙŠ ØªÙ…Ø§Ù…Ø§Ù‹. Ù…Ù† ØºÙŠØ± Ø§Ø´ØªØ±Ø§ÙƒØŒ Ù…Ù† ØºÙŠØ± Ø¨Ø·Ø§Ù‚Ø© Ø§Ø¦ØªÙ…Ø§Ù†. ÙƒÙ„ Ø§Ù„Ù„ÙŠ Ù…Ø­ØªØ§Ø¬Ù‡ Ø¥Ù†Ùƒ ØªØ³Ø¬Ù„ ÙÙŠ Ø§Ù„Ù…ÙˆÙ‚Ø¹.',
				),
				array(
					'q' => 'Ù„Ùˆ Ù…Ø´ ÙÙ‡Ù…Øª Ø¯Ø±Ø³ØŒ ÙÙŠÙ‡ Ø¯Ø¹Ù…ØŸ',
					'a' => 'Ø£ÙƒÙŠØ¯. Ø£ÙŠ Ø³Ø¤Ø§Ù„ ÙŠØ¬ÙŠÙ„ÙƒØŒ ØªÙ‚Ø¯Ø± ØªØ¨Ø¹Øª Ù…Ù† Ø®Ù„Ø§Ù„ Ø¬Ø±ÙˆØ¨ Ø§Ù„Ù€ Telegram Ø§Ù„Ù…Ø®ØµØµ Ù„Ù„Ø·Ù„Ø§Ø¨.',
				),
				array(
					'q' => 'Ø¥ÙŠÙ‡ Ø§Ù„ÙƒÙˆØ±Ø³ Ø§Ù„Ù…Ù†Ø§Ø³Ø¨ Ø§Ù„Ù„ÙŠ Ø¨Ø¹Ø¯Ù‡ØŸ',
					'a' => 'Ø¨Ø¹Ø¯ Ù…Ø§ ØªØ®Ù„ØµØŒ Ø£Ù†ØµØ­Ùƒ Ø¨Ù€ "Ø£Ø³Ø§Ø³ÙŠØ§Øª Dart" Ù„Ùˆ Ù‡Ø¯ÙÙƒ Ø§Ù„Ù…ÙˆØ¨Ø§ÙŠÙ„ØŒ Ø£Ùˆ "Ø¬Ø§ÙØ§" Ù„Ùˆ Ù‡Ø¯ÙÙƒ Backend Ù…ØªÙƒØ§Ù…Ù„.',
				),
			),
		),

	);

	// Fallback: use the Dart content for any course we haven't mapped yet.
	// This way a new course never shows wrong content â€” it shows the most
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
