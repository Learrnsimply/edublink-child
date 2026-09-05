<?php
/**
 * بنية الأكاديمية — الأقسام والمسارات
 *
 * الموقع كان فيه ٦ "تصنيفات" كل واحد جواه كورس واحد — يعني أسماء الكورسات
 * متكررة مرتين، مش تصنيف. ومفيش تصنيف لـDart أصلاً. عشان كده الموقع بيقرا
 * كقائمة منتجات مش كأكاديمية.
 *
 * الملف ده بيعرّف الأقسام كتصنيف حقيقي، وبيرتّب كورسات كل قسم بمستوى ومتطلب
 * سابق، فيبقى فيه مسار يمشي عليه الطالب.
 *
 * التسمية بالمخرَج مش بالموضوع: كل قسم له عنوان قصير (للتنقّل) وجملة
 * بتقول "هتقدر تعمل إيه" (للإقناع).
 *
 * @package EduBlink_Child
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * تعريف الأكاديمية كله في مكان واحد.
 *
 * غيّر الأسماء هنا وهي هتتغير في كل مكان — التصنيفات وصفحات الأقسام والقائمة.
 * `courses` بتاخد ID الكورس بالترتيب اللي الطالب المفروض يمشي بيه.
 */
function learnsimply_academy_departments()
{
	return array(

		'foundations' => array(
			'name'    => 'الأساسيات',
			'outcome' => 'منهج كليات الحاسبات — مشروح صح. من أول سطر كود لحد هياكل البيانات.',
			// الربط بالمنهج الجامعي مقصود: طالب الحاسبات بيدوّر على «جافا» و«هياكل
			// بيانات» بالاسم ده، ومحدش من المنافسين بيقولها صراحة.
			'order'   => 10,
			'courses' => array(
				24443, // كورس جافا للمبتدئين + كتاب هدية
				31578, // البرمجة الكائنية (OOP) بلغة Java
				11287, // هياكل البيانات — المستوى الأول
				30816, // هياكل البيانات — المستوى الثاني
			),
		),

		'app-development' => array(
			'name'    => 'تطوير التطبيقات',
			'outcome' => 'تبني تطبيق موبايل بإيدك — من أول شاشة لحد النشر.',
			'order'   => 20,
			'courses' => array(
				39654, // أساسيات Dart من الصفر لـ OOP
			),
		),

		'ai' => array(
			'name'    => 'الذكاء الاصطناعي',
			'outcome' => 'تشتغل بأدوات الـAI وتبني بيها حاجات حقيقية — مش تتفرج عليها.',
			'order'   => 30,
			'courses' => array(),
		),

		'start-here' => array(
			'name'    => 'ابدأ من هنا',
			'outcome' => 'جرّب بنفسك قبل ما تدفع جنيه.',
			'order'   => 40,
			'courses' => array(
				29368, // مشاريع بايثون للمبتدئين
			),
		),

	);
}

/**
 * أنشئ تصنيفات الأقسام لو مش موجودة، وحدّث وصفها لو اتغيّر.
 *
 * بيشتغل مرة واحدة فعليًا — الـflag بيمنع التكرار، والاسم في الـflag فيه رقم
 * نسخة عشان لو غيّرنا التعريف نقدر نعيد التشغيل بتغيير الرقم بس.
 */
add_action('init', 'learnsimply_register_academy_departments', 20);
function learnsimply_register_academy_departments()
{
	if (!taxonomy_exists('course-category')) {
		return; // Tutor LMS مش شغّال
	}

	$version = '1';
	if (get_option('learnsimply_academy_structure_version') === $version) {
		return;
	}

	foreach (learnsimply_academy_departments() as $slug => $dept) {
		$term = get_term_by('slug', $slug, 'course-category');

		if (!$term) {
			$created = wp_insert_term($dept['name'], 'course-category', array(
				'slug'        => $slug,
				'description' => $dept['outcome'],
			));
			if (is_wp_error($created)) {
				continue;
			}
			$term_id = $created['term_id'];
		} else {
			$term_id = $term->term_id;
			wp_update_term($term_id, 'course-category', array(
				'name'        => $dept['name'],
				'description' => $dept['outcome'],
			));
		}

		update_term_meta($term_id, 'ls_department_order', $dept['order']);
	}

	update_option('learnsimply_academy_structure_version', $version);
}

/**
 * الكورس -> قسمه ومستواه.
 *
 * التصنيفات القديمة (كورس واحد لكل تصنيف) بتفضل مكانها — مبنشيلهاش عشان
 * أي رابط قديم يفضل شغّال. الكورس بيتضاف للقسم، مش بيتنقل.
 */
add_action('init', 'learnsimply_assign_courses_to_departments', 21);
function learnsimply_assign_courses_to_departments()
{
	if (!taxonomy_exists('course-category')) {
		return;
	}

	$version = '1';
	if (get_option('learnsimply_academy_assignment_version') === $version) {
		return;
	}

	foreach (learnsimply_academy_departments() as $slug => $dept) {
		$term = get_term_by('slug', $slug, 'course-category');
		if (!$term) {
			continue;
		}

		$level = 1;
		$prev  = 0; // آخر كورس منشور شفناه — مش اللي قبله في المصفوفة
		foreach ($dept['courses'] as $course_id) {
			if ('publish' !== get_post_status($course_id)) {
				continue; // كورس اتشال أو لسه draft
			}

			// append = true — التصنيف القديم بيفضل
			wp_set_object_terms($course_id, array((int) $term->term_id), 'course-category', true);

			update_post_meta($course_id, 'ls_department', $slug);
			update_post_meta($course_id, 'ls_level', $level);

			// المتطلب السابق لازم يبقى كورس الطالب يقدر يفتحه فعلاً. لو اتفهرس
			// بالمستوى (`courses[$level - 2]`) وكان فيه كورس draft في وسط المسار،
			// المتطلب بيشاور على كورس مخفي والطالب بيقف مستني حاجة مش موجودة.
			update_post_meta($course_id, 'ls_prerequisite', $prev);

			$prev = $course_id;
			$level++;
		}
	}

	update_option('learnsimply_academy_assignment_version', $version);
}

/**
 * كورسات قسم واحد مرتّبة بمستواها — للاستخدام في صفحة القسم والقوالب.
 *
 * @param string $slug سلاج القسم.
 * @return array<int,array<string,mixed>>
 */
function learnsimply_get_department_courses($slug)
{
	$depts = learnsimply_academy_departments();
	if (!isset($depts[$slug])) {
		return array();
	}

	$out   = array();
	$level = 1;
	$prev  = 0; // نفس قاعدة الإسناد: آخر كورس منشور، مش اللي قبله في المصفوفة

	foreach ($depts[$slug]['courses'] as $course_id) {
		if ('publish' !== get_post_status($course_id)) {
			continue;
		}
		$out[] = array(
			'id'           => $course_id,
			'level'        => $level,
			'title'        => get_the_title($course_id),
			'url'          => get_permalink($course_id),
			'thumbnail'    => get_the_post_thumbnail_url($course_id, 'medium_large'),
			'prerequisite' => $prev,
		);
		$prev = $course_id;
		$level++;
	}

	return $out;
}

/**
 * تسمية قصيرة للكورس تتقرا في كارت صغير (خطوة في مسار). العنوان الكامل طويل
 * («البرمجة الكائنية (OOP) بلغة Java من الصفر للاحتراف») ومبينفعش في مربع 150px.
 * المعرّفات نفسها المستخدمة في learnsimply_academy_departments().
 *
 * @param int    $course_id معرّف الكورس.
 * @param string $fallback  لو مفيش تسمية قصيرة.
 * @return string
 */
function learnsimply_course_short_label($course_id, $fallback = '')
{
	$labels = array(
		24443 => 'جافا للمبتدئين',
		31578 => 'Java OOP',
		11287 => 'هياكل البيانات ١',
		30816 => 'هياكل البيانات ٢',
		39654 => 'أساسيات Dart',
		29368 => 'مشاريع بايثون',
	);
	return isset($labels[(int) $course_id]) ? $labels[(int) $course_id] : $fallback;
}
