<?php
/**
 * اختبار محلّل محتوى الكورس — على النصوص الحقيقية للكورسات الستة.
 *
 * ليه: كل كورس مبني بشكل مختلف جوه حقل «ماذا ستتعلم» (عناوين بإيموجي · نثر · عناوين
 * قصيرة · نقط مرقّمة · نضيف). أي تعديل في قواعد المحلّل ممكن يصلّح كورس ويكسر التاني —
 * الاختبار ده بيثبّت شكل الستة مرة واحدة.
 *
 * التشغيل: php tests/course-content-test.php   (بيتنادى من tools-lint.sh)
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }

define( 'ABSPATH', '/' );
function wp_strip_all_tags( $t ) { return strip_tags( $t ); }

require __DIR__ . '/../inc/course-content.php';
$fixtures = require __DIR__ . '/course-content-fixtures.php';

$fail = 0;
function ls_check( $ok, $label ) { global $fail; echo ( $ok ? '  ✅ ' : '  ❌ ' ) . $label . "\n"; if ( ! $ok ) { $fail = 1; } }
function ls_groups( $learn ) { $g = array(); foreach ( $learn as $i ) { if ( null !== $i['group'] && ! in_array( $i['group'], $g, true ) ) { $g[] = $i['group']; } } return $g; }

$r = array();
foreach ( $fixtures as $key => $lines ) {
	$r[ $key ] = learnsimply_parse_course_text( $lines );
}

// ── جافا: فقرة تعريف · ٧ مجموعات مواضيع · ٥ فئات جمهور · جملة ختامية ──
$j = $r['java'];
ls_check( 1 === count( $j['about'] ) && 0 === mb_strpos( $j['about'][0], 'في كورس جافا' ), 'جافا: فقرة التعريف اتفصلت عن النقط' );
ls_check( 16 === count( $j['learn'] ) && 7 === count( ls_groups( $j['learn'] ) ), 'جافا: 16 نقطة في 7 مجموعات مواضيع' );
ls_check( 'الأساسيات (Java Basics)' === ls_groups( $j['learn'] )[0], 'جافا: اسم المجموعة من غير `????`' );
ls_check( 5 === count( $j['audience'] ), 'جافا: ٥ فئات «لمن هذا الكورس»' );
ls_check( 1 === count( $j['outcome'] ) && false === mb_strpos( $j['outcome'][0], '?' ), 'جافا: الجملة الختامية من غير `????` في آخرها' );
ls_check( 'ماذا ستتعلم في كورس جافا للمبتدئين' === $j['headings']['learn'], 'جافا: عنوان «ماذا ستتعلم» محفوظ للقالب' );

// ── OOP: نثر كله · جملة «الكورس مناسب» → جمهور · «مع نهاية الكورس» → نتيجة ──
$o = $r['oop'];
ls_check( 5 === count( $o['about'] ) && empty( $o['learn'] ), 'OOP: ٥ فقرات تعريف ومفيش قائمة «هتتعلم»' );
ls_check( 1 === count( $o['audience'] ) && 0 === mb_strpos( $o['audience'][0], 'الكورس مناسب' ), 'OOP: جملة «الكورس مناسب جدًا…» اتفصلت كجمهور' );
ls_check( 3 === count( $o['outcome'] ) && 'مع نهاية الكورس هتكون' === $o['headings']['outcome'], 'OOP: ٣ نقط «مع نهاية الكورس هتكون»' );
ls_check( false === mb_strpos( $o['about'][3], '????' ), 'OOP: `????` في وسط الفقرة اتشالت' );

// ── هياكل ١: عنوان أول بيتشال · فقرتين · ٤ مجموعات · جمهور · مميزات · الروابط بتتشال ──
$d = $r['ds1'];
ls_check( 2 === count( $d['about'] ) && 0 === mb_strpos( $d['about'][0], 'كورس هياكل البيانات' ), 'هياكل ١: سطر العنوان اتشال والفقرتين اتحفظوا' );
ls_check( 12 === count( $d['learn'] ) && 4 === count( ls_groups( $d['learn'] ) ), 'هياكل ١: 12 نقطة في 4 مجموعات' );
ls_check( 'الطابور (Queue)' === ls_groups( $d['learn'] )[2], 'هياكل ١: بقايا `‍♂️` اتشالت من اسم المجموعة' );
ls_check( 4 === count( $d['audience'] ) && 3 === count( $d['why'] ), 'هياكل ١: ٤ جمهور · ٣ مميزات' );
ls_check( empty( $d['includes'] ) && empty( $d['requirements'] ), 'هياكل ١: «روابط مفيدة» متعرضتش' );

// ── هياكل ٢: نقط مرقّمة · مواد · متطلبات · جمهور بعنوانين ──
$e = $r['ds2'];
ls_check( 7 === count( $e['learn'] ) && empty( ls_groups( $e['learn'] ) ), 'هياكل ٢: ٧ نقط مرقّمة من غير مجموعات' );
ls_check( 0 === mb_strpos( $e['learn'][0]['text'], 'مراجعة Linked List' ), 'هياكل ٢: الكي-كاب 1️⃣ اتشال من أول النقطة' );
ls_check( 2 === count( $e['includes'] ) && 2 === count( $e['requirements'] ) && 2 === count( $e['audience'] ), 'هياكل ٢: مواد ٢ · متطلبات ٢ · جمهور ٢ (العنوانين المتتاليين اتعاملوا كعنوان واحد)' );
ls_check( 'كود أمثلة جاهز للتجربة' === $e['includes'][0], 'هياكل ٢: نقطة بإيموجي جوه «مواد الدورة» فضلت نقطة مش عنوان' );

// ── Dart: «مش محتاج» → متطلب · عناوين بنقطتين · «الكورس بيشمل» → مواد ──
$t = $r['dart'];
ls_check( 1 === count( $t['requirements'] ) && 0 === mb_strpos( $t['requirements'][0], 'مش محتاج' ), 'Dart: «مش محتاج تكون عارف…» اتفصلت كمتطلب' );
ls_check( 1 === count( $t['about'] ) && 4 === count( $t['learn'] ) && 3 === count( $t['audience'] ), 'Dart: فقرة · ٤ نقط · ٣ جمهور' );
ls_check( 5 === count( $t['includes'] ) && 1 === count( $t['why'] ) && 1 === count( $t['outcome'] ), 'Dart: ٥ مواد · ١ ليه · ١ بعد الكورس' );

// ── بايثون: من غير عناوين = كله «هتتعلم» ──
$p = $r['python'];
ls_check( 5 === count( $p['learn'] ) && empty( $p['about'] ) && empty( $p['audience'] ), 'بايثون: ٥ نقط «هتتعلم» ومفيش حاجة تانية' );

// ── حالات حدّية ──
ls_check( empty( learnsimply_parse_course_text( array() )['learn'] ), 'حقل فاضي → مفيش أقسام' );
ls_check( 'الدوال (Functions)' === learnsimply_course_text_clean( '???? الدوال (Functions)' ), 'تنضيف `????` من الأول' );
ls_check( 'الشروط (If Statements)' === learnsimply_course_text_clean( '⚡ الشروط (If Statements)' ), 'تنضيف إيموجي سليمة من الأول' );

echo $fail ? "  اختبار محتوى الكورس فشل\n" : "  اختبار محتوى الكورس عدّى (٦ كورسات)\n";
exit( $fail );
