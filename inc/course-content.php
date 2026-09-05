<?php
/**
 * محتوى صفحة الكورس — استخراج الأقسام من حقول Tutor.
 *
 * الحقيقة اللي بنشتغل عليها (اتقاست من الصفحات الحية ٥ سبتمبر ٢٠٢٦): الوصف الطويل
 * فاضي في ٥ من ٦ كورسات، وكلام التعريف والجمهور والمتطلبات كله محشور في حقل
 * «ماذا ستتعلم» (`_tutor_course_benefits`) — وكل كورس مقسّمه بطريقته: جافا بعناوين
 * مواضيع بإيموجي، OOP نثر، هياكل ٢ عناوين قصيرة، Dart عناوين بنقطتين، بايثون نضيف.
 *
 * القاعدة المشتركة بينهم كلهم: **العناوين**. المحلّل ده بيعرف العناوين من قاموس
 * متجمّع من النصوص الستة نفسها، وبيوزّع السطور على ٧ أقسام ثابتة. الحقول الأصلية في
 * Tutor (الوصف · الجمهور · المتطلبات · المواد) لو متملية بتاخد الأولوية — فأي كورس
 * يتنضّف في الأدمن بيخرج من المحلّل لوحده.
 *
 * الاختبار: tests/course-content-test.php — النصوص الستة الحقيقية كـfixture.
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * الإيموجي في أول السطر — وبقايا الإيموجي المكسورة.
 *
 * الداتابيز فيها إيموجي رباعية البايت متخزّنة `????` (اتحفظت بترميز utf8 مش utf8mb4)،
 * وأحيانًا بقايا zero-width joiner و♂ بعدها. بنشيل ده كله من العرض.
 */
function learnsimply_course_text_clean( $line ) {
	$line = trim( (string) $line );
	// إيموجي أو `????` في الأول (مع بقايا ZWJ/VS16/♂/♀ وكي-كاب الأرقام)
	$line = preg_replace( '/^(\?{2,}|[\x{2600}-\x{27BF}\x{1F000}-\x{1FAFF}\x{FE0F}\x{200D}\x{2640}\x{2642}\x{20E3}]+|\d\x{FE0F}?\x{20E3})[\s\x{FE0F}\x{200D}\x{2640}\x{2642}]*/u', '', $line );
	// `????` في الآخر أو في النص
	$line = preg_replace( '/\s*\?{2,}\s*$/u', '', $line );
	$line = preg_replace( '/\s\?{2,}\s/u', ' ', $line );
	return trim( $line );
}

/**
 * هل السطر بيبدأ بإيموجي (أو ببقاياها)؟ — علامة «عنوان مجموعة» جوه قسم «ماذا ستتعلم».
 */
function learnsimply_course_line_has_marker( $line ) {
	return (bool) preg_match( '/^(\?{2,}|[\x{2600}-\x{27BF}\x{1F000}-\x{1FAFF}])/u', trim( (string) $line ) );
}

/**
 * قاموس العناوين → القسم. الصيغ متجمّعة من النصوص الحقيقية للكورسات الستة.
 */
function learnsimply_course_heading_map() {
	return array(
		'learn'        => array( 'ماذا ستتعلم', 'ماذا سوف تتعلم', 'هتتعلم إيه', 'هتتعلم ايه' ),
		'audience'     => array( 'لمن هذا الكورس', 'مناسب لمين', 'الجمهور', 'الكورس ده ليك' ),
		'requirements' => array( 'المتطلبات', 'محتاج تعرف إيه' ),
		'includes'     => array( 'مواد الدورة', 'الكورس بيشمل', 'المواد المشمولة' ),
		'why'          => array( 'مميزات الكورس', 'الكورس ده مختلف' ),
		'outcome'      => array( 'بعد الكورس', 'مع نهاية الكورس' ),
		'drop'         => array( 'روابط مفيدة' ),
	);
}

/**
 * السطر ده عنوان قسم؟ بيرجع مفتاح القسم أو null.
 */
function learnsimply_course_heading_of( $line ) {
	$t = learnsimply_course_text_clean( $line );
	if ( '' === $t || mb_strlen( $t ) >= 60 ) {
		return null;
	}
	foreach ( learnsimply_course_heading_map() as $key => $words ) {
		foreach ( $words as $w ) {
			if ( 0 === mb_strpos( $t, $w ) ) {
				return $key;
			}
		}
	}
	return null;
}

/**
 * وزّع سطور «ماذا ستتعلم» على الأقسام السبعة.
 *
 * @param string[] $lines سطور الحقل كما هي.
 * @return array{
 *   about: string[], learn: array<int,array{text:string,group:?string}>, audience: string[],
 *   requirements: string[], includes: string[], why: string[], outcome: string[],
 *   headings: array<string,string>
 * }
 */
function learnsimply_parse_course_text( array $lines ) {
	$out = array(
		'about'        => array(),
		'learn'        => array(),
		'audience'     => array(),
		'requirements' => array(),
		'includes'     => array(),
		'why'          => array(),
		'outcome'      => array(),
		'headings'     => array(),
	);

	$lines = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
	if ( empty( $lines ) ) {
		return $out;
	}

	$has_headings = false;
	foreach ( $lines as $l ) {
		if ( learnsimply_course_heading_of( $l ) ) {
			$has_headings = true;
			break;
		}
	}

	// من غير عناوين (بايثون): الحقل كله نقط «هتتعلم إيه».
	if ( ! $has_headings ) {
		foreach ( $lines as $l ) {
			$out['learn'][] = array( 'text' => learnsimply_course_text_clean( $l ), 'group' => null );
		}
		return $out;
	}

	$section = null;
	$group   = null;

	foreach ( $lines as $i => $l ) {
		$heading = learnsimply_course_heading_of( $l );
		if ( $heading ) {
			$section = $heading;
			$group   = null;
			if ( ! isset( $out['headings'][ $heading ] ) ) {
				$out['headings'][ $heading ] = rtrim( learnsimply_course_text_clean( $l ), ':؟? ' );
			}
			continue;
		}

		$t   = learnsimply_course_text_clean( $l );
		$len = mb_strlen( $t );
		if ( '' === $t ) {
			continue;
		}

		// قبل أول عنوان: فقرات التعريف — وسطر العنوان الأول (قصير بلا نقطة) بيتشال.
		if ( null === $section ) {
			if ( 0 === $i && $len < 40 && ! preg_match( '/[.!؟?]$/u', $t ) ) {
				continue;
			}
			if ( 0 === mb_strpos( $t, 'الكورس مناسب' ) ) {
				$out['audience'][] = $t;
			} elseif ( 0 === mb_strpos( $t, 'مش محتاج' ) ) {
				$out['requirements'][] = $t;
			} else {
				$out['about'][] = $t;
			}
			continue;
		}

		// سطر طويل = فقرة مش نقطة: تعريف لو لسه في أول «هتتعلم»، وإلا خاتمة.
		if ( $len >= 90 ) {
			if ( 'learn' === $section && empty( $out['learn'] ) ) {
				$out['about'][] = $t;
			} elseif ( in_array( $section, array( 'why', 'outcome' ), true ) ) {
				$out[ $section ][] = $t;
			} else {
				$out['outcome'][] = $t;
			}
			continue;
		}

		if ( 'drop' === $section ) {
			continue;
		}

		// جوه «هتتعلم»: سطر قصير بإيموجي من غير نقطة = عنوان مجموعة مواضيع.
		if ( 'learn' === $section && learnsimply_course_line_has_marker( $l ) && $len < 45
			&& ! preg_match( '/[.:]$/u', $t ) && ! preg_match( '/^\d/u', $l ) ) {
			$group = $t;
			continue;
		}

		if ( 'learn' === $section ) {
			$out['learn'][] = array( 'text' => $t, 'group' => $group );
		} else {
			$out[ $section ][] = $t;
		}
	}

	return $out;
}

/**
 * حقل Tutor اللي ممكن يبقى نص بأسطر أو مصفوفة → مصفوفة أسطر نضيفة.
 */
function learnsimply_course_field_lines( $value ) {
	if ( is_array( $value ) ) {
		$lines = $value;
	} else {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
	}
	$lines = array_map( 'trim', array_map( 'wp_strip_all_tags', $lines ) );
	return array_values( array_filter( $lines, 'strlen' ) );
}

/**
 * أقسام صفحة الكورس جاهزة للقالب — من حقول Tutor مع الاستخراج عند الحاجة.
 *
 * الأولوية: الحقل الأصلي لو متملي، وإلا المستخرج من «ماذا ستتعلم».
 *
 * @param int $course_id معرّف الكورس.
 * @return array<string,mixed>
 */
function learnsimply_course_sections( $course_id ) {
	$course_id = (int) $course_id;

	$benefits_raw = function_exists( 'tutor_course_benefits' )
		? tutor_course_benefits( $course_id )
		: get_post_meta( $course_id, '_tutor_course_benefits', true );
	$parsed = learnsimply_parse_course_text( learnsimply_course_field_lines( $benefits_raw ) );

	$audience_raw = function_exists( 'tutor_course_target_audience' )
		? tutor_course_target_audience( $course_id )
		: get_post_meta( $course_id, '_tutor_course_target_audience', true );
	$audience = learnsimply_course_field_lines( $audience_raw );
	$audience = array_map( 'learnsimply_course_text_clean', $audience );

	$requirements = array_map( 'learnsimply_course_text_clean', learnsimply_course_field_lines( get_post_meta( $course_id, '_tutor_course_requirements', true ) ) );

	$includes_raw = function_exists( 'tutor_course_material_includes' )
		? tutor_course_material_includes( $course_id )
		: get_post_meta( $course_id, '_tutor_course_material_includes', true );
	$includes = array_map( 'learnsimply_course_text_clean', learnsimply_course_field_lines( $includes_raw ) );
	// سطر «المواد المشمولة في الكورس» هو عنوان مش عنصر.
	$includes = array_values( array_filter( $includes, function ( $l ) {
		return ! learnsimply_course_heading_of( $l );
	} ) );

	// الوصف الطويل: لو متملي بيتعرض زي ما هو (HTML المحرّر)، وإلا الفقرات المستخرجة.
	$content    = trim( (string) get_post_field( 'post_content', $course_id ) );
	$about_html = '' !== $content ? apply_filters( 'the_content', $content ) : '';

	$learn       = $parsed['learn'];
	$learn_title = isset( $parsed['headings']['learn'] ) ? $parsed['headings']['learn'] : 'ماذا ستتعلم في الكورس';
	$outcome     = $parsed['outcome'];
	// OOP: مفيش قائمة «هتتعلم» بس فيه «مع نهاية الكورس هتكون:» — دي قائمته.
	if ( empty( $learn ) && ! empty( $outcome ) && isset( $parsed['headings']['outcome'] ) ) {
		$learn = array_map( function ( $t ) {
			return array( 'text' => $t, 'group' => null );
		}, $outcome );
		$learn_title = $parsed['headings']['outcome'];
		$outcome     = array();
	}

	// مجموعات «هتتعلم» بترتيبها — للقالب.
	$groups = array();
	foreach ( $learn as $item ) {
		$g = null === $item['group'] ? '' : $item['group'];
		if ( ! isset( $groups[ $g ] ) ) {
			$groups[ $g ] = array( 'title' => $g, 'items' => array() );
		}
		$groups[ $g ]['items'][] = $item['text'];
	}

	return array(
		'about_html'    => $about_html,
		'about'         => $parsed['about'],
		'outcome'       => $outcome,
		'learn_title'   => $learn_title,
		'learn_groups'  => array_values( $groups ),
		'learn_count'   => count( $learn ),
		'audience'      => ! empty( $audience ) ? $audience : $parsed['audience'],
		'requirements'  => ! empty( $requirements ) ? $requirements : $parsed['requirements'],
		'includes'      => ! empty( $includes ) ? $includes : $parsed['includes'],
		'why'           => $parsed['why'],
		'headings'      => $parsed['headings'],
	);
}

/**
 * جملة الهيرو: المقتطف لو موجود، وإلا أول جملة من أول فقرة تعريف.
 */
function learnsimply_course_tagline( $course_id, array $sections ) {
	if ( has_excerpt( $course_id ) ) {
		return wp_strip_all_tags( get_the_excerpt( $course_id ) );
	}
	$first = '';
	if ( ! empty( $sections['about'] ) ) {
		$first = $sections['about'][0];
	} elseif ( '' !== $sections['about_html'] ) {
		$first = trim( wp_strip_all_tags( $sections['about_html'] ) );
	}
	if ( '' === $first ) {
		return '';
	}
	$parts = preg_split( '/(?<=[.!؟])\s+/u', $first, 2 );
	return wp_trim_words( $parts[0], 24, '…' );
}

/**
 * إجمالي ساعات الكورس من meta المدة (`_course_duration` = hours/minutes/seconds).
 *
 * @return int الساعات — صفر لو مفيش.
 */
function learnsimply_course_hours( $course_id ) {
	$duration = get_post_meta( (int) $course_id, '_course_duration', true );
	if ( ! is_array( $duration ) ) {
		return 0;
	}
	return isset( $duration['hours'] ) ? (int) $duration['hours'] : 0;
}

/**
 * تسمية المستوى بالعربي + مفتاح CSS. Tutor بيخزّن مفاتيح إنجليزي وأحيانًا عربي مباشر.
 *
 * @return array{label:string,key:string}
 */
function learnsimply_course_level( $course_id ) {
	$raw = strtolower( trim( (string) get_post_meta( (int) $course_id, '_tutor_course_level', true ) ) );
	$map = array(
		'beginner'     => array( 'مبتدئ', 'beginner' ),
		'intermediate' => array( 'متوسط', 'intermediate' ),
		'expert'       => array( 'متقدم', 'expert' ),
		'all_levels'   => array( 'كل المستويات', 'beginner' ),
		'متوسط'        => array( 'متوسط', 'intermediate' ),
		'متقدم'        => array( 'متقدم', 'expert' ),
	);
	if ( isset( $map[ $raw ] ) ) {
		return array( 'label' => $map[ $raw ][0], 'key' => $map[ $raw ][1] );
	}
	return array( 'label' => '' !== $raw ? $raw : 'مبتدئ', 'key' => 'beginner' );
}

/**
 * مكان الكورس في مساره — للخريطة وكارتي «المتطلب السابق» و«اللي بعده» و«كمّل المسار».
 *
 * نفس قاعدة الرئيسية: مسار الأساسيات = كورسات قسم الأساسيات + أول كورس في تطوير
 * التطبيقات (دارت خطوة ٥). كورس قسم التطبيقات بيتعرض في مساره هو (دارت ← Flutter قريبًا).
 * كورس من غير مسار (بايثون المجاني) بياخد أول ٣ كورسات في الأساسيات كـ«كمّل».
 *
 * @param int $course_id معرّف الكورس.
 * @return array{label:string,url:string,steps:array,position:int,total:int,prereq:?array,next:?array,rest:array}
 */
function learnsimply_course_path_context( $course_id ) {
	$course_id = (int) $course_id;
	$empty     = array( 'label' => '', 'url' => '', 'steps' => array(), 'position' => 0, 'total' => 0, 'prereq' => null, 'next' => null, 'rest' => array() );
	if ( ! function_exists( 'learnsimply_get_department_courses' ) ) {
		return $empty;
	}

	$department = (string) get_post_meta( $course_id, 'ls_department', true );
	if ( '' === $department && function_exists( 'learnsimply_academy_departments' ) ) {
		foreach ( learnsimply_academy_departments() as $slug => $dept ) {
			if ( in_array( $course_id, $dept['courses'], true ) ) {
				$department = $slug;
				break;
			}
		}
	}

	$build = function ( $items ) {
		$steps = array();
		foreach ( $items as $item ) {
			$level   = learnsimply_course_level( $item['id'] );
			$hours   = learnsimply_course_hours( $item['id'] );
			$steps[] = array(
				'id'          => $item['id'],
				'title'       => $item['title'],
				'label'       => function_exists( 'learnsimply_course_short_label' ) ? learnsimply_course_short_label( $item['id'], $item['title'] ) : $item['title'],
				'url'         => $item['url'],
				'thumbnail'   => $item['thumbnail'],
				'hours'       => $hours,
				'hours_label' => $hours > 0 ? $hours . 'h' : '',
				'level'       => $level['label'],
				'level_key'   => $level['key'],
				'coming_soon' => false,
				'state'       => '',
			);
		}
		return $steps;
	};

	$label = '';
	$url   = '';
	$steps = array();
	if ( 'app-development' === $department ) {
		$label   = 'مسار تطوير التطبيقات';
		$url     = get_term_link( 'app-development', 'course-category' );
		$steps   = $build( learnsimply_get_department_courses( 'app-development' ) );
		$steps[] = array( 'id' => 0, 'title' => 'Flutter', 'label' => 'Flutter', 'url' => '', 'thumbnail' => '', 'hours' => 0, 'hours_label' => '', 'level' => '', 'level_key' => 'beginner', 'coming_soon' => true, 'state' => '' );
	} elseif ( 'foundations' === $department ) {
		$label = 'مسار الأساسيات';
		$url   = get_term_link( 'foundations', 'course-category' );
		$steps = $build( array_merge(
			learnsimply_get_department_courses( 'foundations' ),
			array_slice( learnsimply_get_department_courses( 'app-development' ), 0, 1 )
		) );
	}
	if ( is_wp_error( $url ) ) {
		$url = '';
	}

	$position = 0;
	foreach ( $steps as $i => $s ) {
		if ( $s['id'] === $course_id ) {
			$position = $i + 1;
		}
	}
	foreach ( $steps as $i => $s ) {
		$steps[ $i ]['state'] = $position && $i + 1 < $position ? 'done' : ( $i + 1 === $position ? 'now' : '' );
	}

	$rest = array();
	if ( $position ) {
		$rest = array_slice( $steps, $position, 3 );
	} else {
		$rest = array_slice( $build( learnsimply_get_department_courses( 'foundations' ) ), 0, 3 );
	}
	$rest = array_values( array_filter( $rest, function ( $s ) {
		return empty( $s['coming_soon'] );
	} ) );

	return array(
		'label'    => $label,
		'url'      => $url,
		'steps'    => $steps,
		'position' => $position,
		'total'    => count( $steps ),
		'prereq'   => $position > 1 ? $steps[ $position - 2 ] : null,
		'next'     => ( $position && isset( $steps[ $position ] ) ) ? $steps[ $position ] : null,
		'rest'     => $rest,
	);
}
