<?php
/**
 * صفحة الباقة — تجهيز الـcontext (MOSH-BLUEPRINT.md · الموجة د · صفحة الباقة).
 *
 * الباقة = منتج ووكومرس + عناصره في جدول بلجن الباقات (inc/bundles.php). الصفحة بتعرض
 * الكورسات اللي جوه الباقة **مرتّبة بمستواها في الأكاديمية** (مش بترتيب الإدخال)، وأرقام
 * مجمّعة (ساعات · دروس · طلاب · تقييم)، وكام بيوفّر الطالب مقارنة بشرا الكورسات لوحدها.
 * كل رقم بيتقرا وقت العرض من ووكومرس وTutor — تغيير السعر من الأدمن بيبان على طول.
 *
 * العنصر اللي مش كورس (كتاب PDF مثلًا) بيتعرض كـ«هدية مع الباقة».
 *
 * الاختبار: tests/bundle-page-test.php
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * باقات المسارات المعروفة: السلاج → الباقة اللي بتيجي بعدها في الرحلة.
 * (بتتقرا بالسلاج عشان الـIDs ممكن تتغيّر على السيرفر.)
 */
function learnsimply_bundle_next_map() {
	return array(
		'java-basics-oop-bundle' => array( rawurlencode( 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ), 'هياكل-البيانات-الكاملة-data-structure-level-1-2' ),
		'هياكل-البيانات-الكاملة-data-structure-level-1-2' => array( 'all_in_one' ),
	);
}

/**
 * «جميع الدورات» (`all_in_one`) مش باقة في بلجن الباقات (صفر عناصر) — بس هي باقة فعلًا:
 * كل كورسات الأكاديمية. هنا بنعرّفها بالسلاج، وعناصرها = منتجات كل الكورسات المنشورة بترتيب المسار.
 */
function learnsimply_all_courses_bundle_slugs() {
	return array( 'all_in_one' );
}

/**
 * المنتج ده باقة؟ — عناصر في بلجن الباقات، أو «جميع الدورات» بالسلاج.
 * القاعدة الواحدة اللي الكونترولر واختيار ملفات CSS (functions.php) لازم يتفقوا عليها —
 * لما اختلفوا (٥/٩) الصفحة اترندرت بقالب الباقة من غير CSS بتاعه.
 */
function learnsimply_is_bundle_product( $post_id ) {
	$post_id = (int) $post_id;
	if ( function_exists( 'learnsimply_bundle_item_count' ) && learnsimply_bundle_item_count( $post_id ) > 0 ) {
		return true;
	}
	$slug = urldecode( (string) get_post_field( 'post_name', $post_id ) );
	return in_array( $slug, learnsimply_all_courses_bundle_slugs(), true );
}

/**
 * منتجات ووكومرس لكل كورسات الأكاديمية المنشورة، بترتيب الأقسام والمستويات.
 *
 * @return int[]
 */
function learnsimply_all_courses_product_ids() {
	$out = array();
	if ( ! function_exists( 'learnsimply_academy_departments' ) ) {
		return $out;
	}
	foreach ( learnsimply_academy_departments() as $dept ) {
		foreach ( $dept['courses'] as $cid ) {
			if ( 'publish' !== get_post_status( $cid ) ) {
				continue;
			}
			$pid = (int) get_post_meta( $cid, '_tutor_course_product_id', true );
			if ( $pid && ! in_array( $pid, $out, true ) ) {
				$out[] = $pid;
			}
		}
	}
	return $out;
}

/**
 * أسئلة الباقة — نفس أجوبة الكورسات (الضمان · التحديثات) + سؤالين عن الباقة نفسها.
 *
 * @param string[] $names أسماء الكورسات بالترتيب.
 * @param bool     $has_gift فيه كتاب هدية؟
 */
function learnsimply_bundle_faq( array $names, $has_gift ) {
	$faq   = array();
	$faq[] = array(
		'q' => 'هاخد الكورسات مع بعض ولا واحد واحد؟',
		'a' => 'أول ما الدفع يتم، ' . ( count( $names ) > 1 ? 'الكورسات كلها' : 'الكورس' ) . ( $has_gift ? ' والكتاب' : '' ) . ' بيتفتحولك في نفس اللحظة في حسابك، من غير انتظار.',
	);
	if ( count( $names ) > 1 ) {
		$faq[] = array(
			'q' => 'أبدأ بأنهي كورس؟',
			'a' => 'ابدأ بـ«' . $names[0] . '» وبعده «' . $names[1] . '». الترتيب ده مقصود: كل كورس بيبني على اللي قبله.',
		);
	}
	if ( $has_gift ) {
		$faq[] = array(
			'q' => 'الكتاب بيتسلّم إزاي؟',
			'a' => 'ملف PDF بيتضاف لحسابك مع الباقة، تحمّله من لوحة التحكم في أي وقت.',
		);
	}
	$faq[] = array(
		'q' => 'هل الكورسات بتتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
		'a' => 'أيوه، الكورسات بتتحدث بشكل دوري. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا، مدى الحياة، من غير أي مصاريف إضافية.',
	);
	$faq[] = array(
		'q' => 'لو مش فهمت درس، فيه دعم؟',
		'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب، وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
	);
	$faq[] = array(
		'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
		'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الباقة مش مناسبة ليك، ابعتلنا وهنرجعلك فلوسك بالكامل، من غير أي أسئلة.',
	);
	return $faq;
}

/**
 * منتج ووكومرس → الكورس المربوط بيه.
 *
 * `tutor_utils()->get_course_id_by_product()` رجّعت صفر على السيرفر (٥/٩) مع إن الربط موجود،
 * فبنبني الخريطة بالاتجاه اللي الرئيسية بتستخدمه وشغّال: لكل كورس في الأكاديمية →
 * `_tutor_course_product_id`. الدالة الأصلية بتتجرّب الأول.
 *
 * @return int معرّف الكورس أو 0.
 */
function learnsimply_course_id_for_product( $product_id ) {
	static $map = null;
	$product_id = (int) $product_id;
	if ( function_exists( 'tutor_utils' ) ) {
		$cid = (int) tutor_utils()->get_course_id_by_product( $product_id );
		if ( $cid ) {
			return $cid;
		}
	}
	if ( null === $map ) {
		$map = array();
		if ( function_exists( 'learnsimply_academy_departments' ) ) {
			foreach ( learnsimply_academy_departments() as $dept ) {
				foreach ( $dept['courses'] as $cid ) {
					$pid = (int) get_post_meta( $cid, '_tutor_course_product_id', true );
					if ( $pid ) {
						$map[ $pid ] = (int) $cid;
					}
				}
			}
		}
	}
	return isset( $map[ $product_id ] ) ? $map[ $product_id ] : 0;
}

/**
 * وصف المنتج → فقرات نضيفة. سطور السعر بتتشال لأن السعر بيتقرا من ووكومرس
 * (وصف باقة جافا فيه «850 بدل 2150» قديم).
 *
 * @return string[]
 */
function learnsimply_bundle_about( $description ) {
	$text  = wp_strip_all_tags( (string) $description );
	// الوصف على السيرفر سطر واحد وجمله مفصولة بإيموجي مكسورة `????` — بنقسم عليها كمان.
	$lines = preg_split( '/\r\n|\r|\n|\?{2,}/u', $text );
	$out   = array();
	foreach ( $lines as $line ) {
		$line = learnsimply_course_text_clean( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/السعر|جنيه|خصم/u', $line ) ) {
			continue;
		}
		$out[] = $line;
	}
	return $out;
}

/**
 * الـcontext بتاع صفحة الباقة.
 *
 * @param WC_Product $product    منتج الباقة.
 * @param int[]      $item_ids   معرّفات منتجات العناصر (من جدول البلجن).
 * @return array<string,mixed>
 */
function learnsimply_bundle_page_context( $product, array $item_ids ) {
	$bundle_id = (int) $product->get_id();
	$price     = (float) $product->get_price();

	// ترتيب الأكاديمية — الكورس اللي مش في أي قسم بييجي في الآخر.
	$order = array();
	if ( function_exists( 'learnsimply_academy_departments' ) ) {
		$i = 0;
		foreach ( learnsimply_academy_departments() as $dept ) {
			foreach ( $dept['courses'] as $cid ) {
				if ( ! isset( $order[ $cid ] ) ) {
					$order[ $cid ] = $i++;
				}
			}
		}
	}

	$courses = array();
	$gifts   = array();
	foreach ( $item_ids as $pid ) {
		$pid  = (int) $pid;
		$item = wc_get_product( $pid );
		if ( ! $item ) {
			continue;
		}
		$course_id = learnsimply_course_id_for_product( $pid );
		$image     = wp_get_attachment_image_url( $item->get_image_id(), 'large' );

		if ( ! $course_id || 'publish' !== get_post_status( $course_id ) ) {
			$gifts[] = array(
				'title' => $item->get_name(),
				'url'   => get_permalink( $pid ),
				'image' => $image ? $image : learnsimply_no_image_url(),
				'desc'  => wp_trim_words( wp_strip_all_tags( $item->get_short_description() ? $item->get_short_description() : $item->get_description() ), 18, '…' ),
			);
			continue;
		}

		$sections  = learnsimply_course_sections( $course_id );
		$level     = learnsimply_course_level( $course_id );
		$rating    = tutor_utils()->get_course_rating( $course_id );
		$p         = tutor_utils()->get_raw_course_price( $course_id );
		$reg       = ! empty( $p->regular_price ) ? (float) $p->regular_price : 0;
		$sale      = ! empty( $p->sale_price ) ? (float) $p->sale_price : 0;
		$cprice    = $sale > 0 ? $sale : $reg;
		$thumb     = get_the_post_thumbnail_url( $course_id, 'large' );

		// «هتتعلم إيه» في الباقة: أسماء المجموعات لو موجودة، وإلا أول ٦ نقط.
		$learn = array();
		foreach ( $sections['learn_groups'] as $g ) {
			if ( '' !== $g['title'] ) {
				$learn[] = $g['title'];
			} else {
				foreach ( $g['items'] as $t ) {
					$learn[] = $t;
				}
			}
		}
		$learn = array_slice( $learn, 0, 7 );

		$courses[] = array(
			'id'           => $course_id,
			'product_id'   => $pid,
			'title'        => get_the_title( $course_id ),
			'label'        => function_exists( 'learnsimply_course_short_label' ) ? learnsimply_course_short_label( $course_id, get_the_title( $course_id ) ) : get_the_title( $course_id ),
			'url'          => get_permalink( $course_id ),
			'thumbnail'    => $thumb ? $thumb : ( $image ? $image : learnsimply_no_image_url() ),
			'tagline'      => learnsimply_course_tagline( $course_id, $sections ),
			'hours'        => learnsimply_course_hours( $course_id ),
			'level'        => $level['label'],
			'level_key'    => $level['key'],
			'lesson_count' => (int) tutor_utils()->get_lesson_count_by_course( $course_id ),
			'students'     => (int) tutor_utils()->count_enrolled_users_by_course( $course_id ),
			'rating_avg'   => ( $rating && $rating->rating_count > 0 ) ? (float) $rating->rating_avg : 0,
			'rating_count' => $rating ? (int) $rating->rating_count : 0,
			'price'        => $cprice,
			'price_label'  => $cprice > 0 ? number_format( $cprice, 0, '.', ',' ) : '',
			'learn'        => $learn,
			'audience'     => $sections['audience'],
			'order'        => isset( $order[ $course_id ] ) ? $order[ $course_id ] : 1000,
		);
	}
	usort( $courses, function ( $a, $b ) {
		return $a['order'] <=> $b['order'];
	} );

	// الأرقام المجمّعة
	$hours = 0; $lessons = 0; $students = 0; $rc = 0; $rsum = 0; $separate = 0;
	foreach ( $courses as $c ) {
		$hours    += $c['hours'];
		$lessons  += $c['lesson_count'];
		$students += $c['students'];
		$rc       += $c['rating_count'];
		$rsum     += $c['rating_avg'] * $c['rating_count'];
		$separate += $c['price'];
	}
	$save = ( $price > 0 && $separate > $price ) ? $separate - $price : 0;

	// المسار: من أول كورس في الباقة — والكورسات اللي بعد آخر كورس فيها
	$path = ! empty( $courses ) ? learnsimply_course_path_context( $courses[0]['id'] ) : learnsimply_course_path_context( 0 );
	$positions = array();
	foreach ( $courses as $c ) {
		foreach ( $path['steps'] as $i => $s ) {
			if ( $s['id'] === $c['id'] ) {
				$positions[] = $i + 1;
			}
		}
	}
	$after = array();
	if ( ! empty( $positions ) ) {
		$after = array_slice( $path['steps'], max( $positions ), 2 );
		$after = array_values( array_filter( $after, function ( $s ) {
			return empty( $s['coming_soon'] );
		} ) );
		foreach ( $after as $i => $s ) {
			$p    = tutor_utils()->get_raw_course_price( $s['id'] );
			$reg  = ! empty( $p->regular_price ) ? (float) $p->regular_price : 0;
			$sale = ! empty( $p->sale_price ) ? (float) $p->sale_price : 0;
			$sp   = $sale > 0 ? $sale : $reg;
			$after[ $i ]['price_label']  = $sp > 0 ? number_format( $sp, 0, '.', ',' ) : '';
			$after[ $i ]['lesson_count'] = (int) tutor_utils()->get_lesson_count_by_course( $s['id'] );
		}
	}

	// الباقة اللي بعدها
	$next_bundle = null;
	$slug        = urldecode( (string) $product->get_slug() );
	$next_map    = learnsimply_bundle_next_map();
	if ( isset( $next_map[ $slug ] ) ) {
		foreach ( $next_map[ $slug ] as $next_slug ) {
			$post = get_page_by_path( $next_slug, OBJECT, 'product' );
			if ( $post && 'publish' === $post->post_status ) {
				$np = wc_get_product( $post->ID );
				if ( $np ) {
					$nprice      = (float) $np->get_price();
					$next_bundle = array(
						'title'       => $np->get_name(),
						'url'         => get_permalink( $post->ID ),
						'image'       => wp_get_attachment_image_url( $np->get_image_id(), 'large' ),
						'price_label' => $nprice > 0 ? number_format( $nprice, 0, '.', ',' ) : '',
					);
				}
				break;
			}
		}
	}

	// لمين: أول كورس عنده قائمة جمهور
	$audience = array();
	foreach ( $courses as $c ) {
		if ( ! empty( $c['audience'] ) ) {
			$audience = $c['audience'];
			break;
		}
	}

	// آراء الكورسات (لو الباقة نفسها مفيهاش كفاية) — رأيين من كل كورس
	$course_reviews = array();
	foreach ( $courses as $c ) {
		$revs = tutor_utils()->get_course_reviews( $c['id'], 0, 6, false, array( 'approved' ), 0 );
		$n    = 0;
		foreach ( (array) $revs as $r ) {
			$text = trim( stripslashes( (string) $r->comment_content ) );
			if ( mb_strlen( $text ) < 25 ) {
				continue; // «ممتاز» لوحدها مش رأي يتعرض
			}
			$course_reviews[] = array(
				'author'  => $r->display_name,
				'rating'  => (int) $r->rating,
				'content' => $text,
				'date'    => $r->comment_date,
				'source'  => $c['label'],
			);
			if ( ++$n >= 2 ) {
				break;
			}
		}
	}

	$names  = array_map( function ( $c ) { return $c['label']; }, $courses );
	$about  = learnsimply_bundle_about( $product->get_description() );
	$short  = learnsimply_bundle_about( $product->get_short_description() );
	$is_all = in_array( urldecode( (string) $product->get_slug() ), learnsimply_all_courses_bundle_slugs(), true );
	// جملة الهيرو: أول سطر نضيف من المقتطف، وإلا أول سطر من الوصف، وإلا أسماء الكورسات.
	$fallback = $is_all
		? 'كل كورسات المنصة (' . count( $courses ) . ' كورسات) بسعر واحد: ' . implode( '، ' , $names ) . '.'
		: implode( ' + ', $names );
	$tagline = ! empty( $short ) ? $short[0] : ( ! empty( $about ) ? $about[0] : $fallback );
	$tagline = wp_trim_words( $tagline, 26, '…' );

	// العنوان: «كورس Java Basics + OOP | من الأساسيات إلى الاحتراف» → عنوان + سطر تحته.
	$title_parts = preg_split( '/\s[|–—-]\s/u', (string) $product->get_name(), 2 );
	$title       = trim( $title_parts[0] );
	$subtitle    = isset( $title_parts[1] ) ? trim( $title_parts[1] ) : '';

	// ليه الباقة؟ — من الداتا نفسها، مش نص تسويقي جديد.
	$why = array();
	if ( $save > 0 ) {
		$why[] = array( 'h' => 'بتوفّر ' . number_format( $save, 0, '.', ',' ) . ' ج.م', 'p' => 'الكورسات لوحدها بـ' . number_format( $separate, 0, '.', ',' ) . ' ج.م، والباقة بـ' . number_format( $price, 0, '.', ',' ) . ' ج.م.' );
	}
	if ( count( $courses ) > 1 ) {
		$why[] = array( 'h' => 'الترتيب الصح جاهز', 'p' => implode( ' ← ', $names ) . '. كل كورس بيبني على اللي قبله، مش هتحتار تبدأ منين.' );
	}
	if ( ! empty( $gifts ) ) {
		$why[] = array( 'h' => $gifts[0]['title'] . ' هدية', 'p' => 'بيتضاف لحسابك مع الباقة من غير أي مصاريف.' );
	}
	$why[] = array( 'h' => 'مرة واحدة، مدى الحياة', 'p' => ( $hours > 0 ? $hours . ' ساعة و' : '' ) . $lessons . ' درس بكل التحديثات الجاية، وضمان استرداد خلال 7 أيام.' );

	return array(
		'bundle_id'      => $bundle_id,
		'title'          => $title,
		'subtitle'       => $subtitle,
		'is_all'         => $is_all,
		'tagline'        => $tagline,
		'why'            => $why,
		'image'          => wp_get_attachment_image_url( $product->get_image_id(), 'full' ) ?: learnsimply_no_image_url(),
		'price'          => $price,
		'price_label'    => $price > 0 ? number_format( $price, 0, '.', ',' ) : '',
		'separate_label' => $separate > 0 ? number_format( $separate, 0, '.', ',' ) : '',
		'save_label'     => $save > 0 ? number_format( $save, 0, '.', ',' ) : '',
		'courses'        => $courses,
		'gifts'          => $gifts,
		'names'          => $names,
		'hours'          => $hours,
		'lessons'        => $lessons,
		'students'       => $students,
		'rating_avg'     => $rc > 0 ? number_format( $rsum / $rc, 1 ) : '',
		'rating_count'   => $rc,
		'levels'         => array_values( array_unique( array_map( function ( $c ) { return $c['level']; }, $courses ) ) ),
		'path_label'     => $path['label'],
		'path_url'       => $path['url'],
		'positions'      => $positions,
		'after'          => $after,
		'next_bundle'    => $next_bundle,
		'about'          => $about,
		'audience'       => $audience,
		'course_reviews' => $course_reviews,
		'faq'            => learnsimply_bundle_faq( $names, ! empty( $gifts ) ),
	);
}
