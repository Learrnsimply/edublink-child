<?php
/**
 * فاحص مراجع قوالب Twig.
 *
 * ليه: Twig بيحل الـ{% include %} وقت التشغيل مش وقت الترجمة، فترجمة القالب
 * بتعدّي حتى لو القالب المضمَّن اتمسح. الفاحص ده بيقرا كل مرجع نصي في كل
 * قالب ويتأكد إن الملف موجود — قبل ما المستخدم يكتشفها بصفحة بيضا.
 */
$root = __DIR__ . '/views';
$tags = 'include|extends|embed|import|from|use';
$missing = []; $dynamic = []; $refs = 0; $files = 0;

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if ($f->getExtension() !== 'twig') continue;
    $files++;
    $rel = str_replace($root . '/', '', $f->getPathname());
    $src = file_get_contents($f->getPathname());

    preg_match_all('/\{%-?\s*(' . $tags . ')\s+(.*?)\s*-?%\}/s', $src, $m, PREG_SET_ORDER);
    foreach ($m as $hit) {
        [$whole, $tag, $arg] = $hit;
        // مرجع نصي: 'x.twig' أو "x.twig"
        if (preg_match_all('/[\'"]([^\'"]+\.twig)[\'"]/', $arg, $lits)) {
            foreach ($lits[1] as $t) {
                $refs++;
                if (!file_exists($root . '/' . $t)) $missing[] = "$rel  →  $tag '$t'";
            }
        } elseif (preg_match('/[~{]|\bloop\b/', $arg)) {
            $dynamic[] = "$rel  →  $tag $arg";
        }
    }
}
echo "  $files قالب · $refs مرجع نصي\n";
foreach ($dynamic as $d) echo "  ⚠️  مرجع ديناميكي (لازم يتراجع بالعين): $d\n";
foreach ($missing as $x) echo "  🔴 قالب ناقص: $x\n";
if (!$missing) echo "  ✅ كل مرجع بيوصل لملف موجود\n";
exit($missing ? 1 : 0);
