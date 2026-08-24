<?php
/**
 * Template Name: Vibe Coding Course — YouTube Companion
 *
 * Companion / reference page for the YouTube video "كورس ال Vibe Coding".
 * Walks the visitor through the 5 steps from the video with every link
 * in one place, copy-friendly, mobile-first.
 *
 * @package EduBlink_Child
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Timber\Timber')) {
	echo 'Timber plugin is not installed.';
	return;
}

/**
 * ─────────────────────────────────────────────────────────────────
 * YouTube video ID — the only thing you need to edit to publish.
 *
 * Paste the part of any YouTube URL that comes after `v=` here.
 * Example: https://www.youtube.com/watch?v=dQw4w9WgXcQ  →  dQw4w9WgXcQ
 *
 * Leave empty ('') to show the static poster image with a "Watch on YouTube"
 * button instead of an inline player. The poster image lives at
 * assets/img/vibe-coding-course-thumb.png (already in the project).
 * ─────────────────────────────────────────────────────────────────
 */
$ls_vibe_coding_video_id = 'YOUR_VIDEO_ID_HERE';

$context = Timber::get_context();
$context['theme_uri']      = get_stylesheet_directory_uri();
$context['video_id']       = trim($ls_vibe_coding_video_id);
$context['has_embed']      = $context['video_id'] !== '' && $context['video_id'] !== 'YOUR_VIDEO_ID_HERE';
$context['poster_url']     = $context['theme_uri'] . '/assets/img/vibe-coding-course-thumb.png';
$context['youtube_watch']  = $context['has_embed']
	? 'https://www.youtube.com/watch?v=' . rawurlencode($context['video_id'])
	: 'https://www.youtube.com/@Learn_Simply';

/**
 * The 5 steps from the YouTube video, plus the 3 internal course offers
 * shown at the bottom. Kept here (not in the Twig) so we can swap copy
 * or add analytics without touching the template.
 */
$context['steps'] = array(
	array(
		'number'      => 1,
		'icon'        => 'bulb',
		'title'       => 'جهّز فكرتك',
		'description' => 'ادخل على كلود شات وحمّل مهارة "Grill Me" واعمل عصف ذهني للفكرة بتاعتك — هتطلع منها ملفات المشروع كاملة: اسم، هدف، جمهور، الـ features الأساسية.',
		'tip'         => 'متحاولش تطلع بتفاصيل تقنية — بس فكّر "أنا عايز أداة تعمل إيه للناس"، والباقي على الـ AI.',
		'tools'       => array(
			array('name' => 'كلود شات',             'url' => 'https://claude.ai/new'),
			array('name' => 'مهارة Grill Me',       'url' => 'https://awesomeskill.ai/skill/julianoczkowski-designer-skills-grill-me'),
		),
	),
	array(
		'number'      => 2,
		'icon'        => 'download',
		'title'       => 'سَطِّب البيئة',
		'description' => 'حمّل Claude Code (الوكيل اللي بيكتب وبيشغل كود) و Visual Studio Code (المحرر اللي بتشتغل جوّاه). الفولدر اللي هتفتحه في VS Code هو "مشروعك".',
		'tip'         => 'بعد ما تخلّص التسطيب، جرّب تقول لـ Claude Code "اعمل فولدر باسم my-vibe-project" — لو اشتغل، أنت جاهز.',
		'tools'       => array(
			array('name' => 'تحميل Claude Code',   'url' => 'https://claude.com/download'),
			array('name' => 'تحميل VS Code',       'url' => 'https://code.visualstudio.com/download'),
		),
	),
	array(
		'number'      => 3,
		'icon'        => 'plan',
		'title'       => 'اعمل خطة قبل ما تكتب كود',
		'description' => 'خلّي Claude Code يقرأ ملفات المشروع (اللي طلعت من الخطوة 1) ويعمل خطة تنفيذ مرحلية — المهام، الترتيب، معايير القبول لكل Feature.',
		'tip'         => 'الخطة دي هي "العقد" بينك وبين الـ AI. كل ما تغيّر حاجة، قوله يرجع للخطة ويحدّثها.',
		'tools'       => array(),
	),
	array(
		'number'      => 4,
		'icon'        => 'build',
		'title'       => 'ابني + اختبر',
		'description' => 'خلّي Claude Code يبني النسخة الأولى Feature بـ Feature. استخدم "المهارات" من commandcode.ai دي لما تحتاج مساعدة (UI، API، Database...). ولما تخلّص، اختبر كل حاجة بـ TestSprite — Automated Testing هيوصلك تقرير بكل الـ bugs.',
		'tip'         => 'لا تقبل output من Claude Code من غير ما تقرأه — لو فيه ملاحظات قولها فوراً، أحسن من إنك تتراكم مشاكل.',
		'tools'       => array(
			array('name' => 'مكتبة المهارات + Find Skills', 'url' => 'https://commandcode.ai/skills'),
			array('name' => 'TestSprite',                    'url' => 'https://www.testsprite.com/?via=learrnsimply'),
			array('name' => 'TestSprite CLI',                'url' => 'https://github.com/TestSprite/testsprite-cli'),
		),
	),
	array(
		'number'      => 5,
		'icon'        => 'rocket',
		'title'       => 'انشر موقعك',
		'description' => 'ارفع الكود على GitHub (نسخة احتياطية + شير مع ناس تانية)، وبعدين استضفه مجاناً على GitHub Pages — يبقى عندك لينك حقيقي تشاركه.',
		'tip'         => 'لو محتاج Domain مخصص (.com / .eg)، خد واحد من Namecheap أو Cloudflare — بيبقى أرخص من شراء Domain من المنصات التانية.',
		'tools'       => array(
			array('name' => 'GitHub',          'url' => 'https://github.com/'),
			array('name' => 'GitHub Pages Docs', 'url' => 'https://docs.github.com/en/pages'),
		),
	),
);

$context['courses'] = array(
	array(
		'badge'       => 'الأكثر مبيعاً',
		'title'       => 'كورس جافا للمبتدئين + كتاب هدية',
		'description' => 'ابدأ رحلتك في البرمجة من الصفر بلغة جافا — هتفهم الـ AI كود لما يبني ليك، وتقدر تعدّل فيه بنفسك.',
		'meta'        => '74 درس · 13 ساعة محتوى',
		'old_price'   => '700',
		'new_price'   => '550',
		'discount'    => '21%',
		'url'         => 'https://learrnsimply.com/courses/java-course-level1/',
		'cta'         => 'اشترك دلوقتي',
		'highlight'   => false,
	),
	array(
		'badge'       => 'الأفضل لتطبيقات الموبايل',
		'title'       => 'أساسيات Dart من الصفر لـ OOP — أول خطوة لـ Flutter',
		'description' => 'لو عايز تبني تطبيقات موبايل، ده أول خطوة ليك — هتبدأ من Dart (لغة Flutter) وتفهم الـ OOP قبل ما تدخل في الـ Framework.',
		'meta'        => '112 درس · 20 ساعة محتوى',
		'old_price'   => '1,200',
		'new_price'   => '600',
		'discount'    => '50%',
		'url'         => 'https://learrnsimply.com/courses/dart/',
		'cta'         => 'اشترك دلوقتي',
		'highlight'   => false,
	),
	array(
		'badge'       => 'أفضل قيمة',
		'title'       => 'باقة كل الكورسات',
		'description' => '6 كورسات كاملة، 422 درس، وصول مدى الحياة — جافا + OOP + هياكل بيانات (مستويين) + Dart + مشاريع بايثون. وفّر 2,300 جنيه.',
		'meta'        => '6 كورسات · 422 درس · وصول مدى الحياة',
		'old_price'   => '4,800',
		'new_price'   => '2,500',
		'discount'    => '48%',
		'url'         => 'https://learrnsimply.com/course-bundle/allcourse/',
		'cta'         => 'اشترك في الباقة',
		'highlight'   => true,
	),
);

Timber::render('page-vibe-coding-course.twig', $context);
