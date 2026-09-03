<?php
/**
 * مشغّل اختبارات الباقات — بيشغّل كل حالة في عملية لوحدها.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

$php   = PHP_BINARY;
$case  = __DIR__ . '/bundles-case.php';
$tests = [
    ['normal',         '{"10":3,"11":5,"12":0}', 'أعداد صح · والباقة الفاضية بترجع 0 مش تغيب'],
    ['queries',        '{"queries":2}',          '٥ باقات = استعلامين بس، مش ٥ (مفيش N+1)'],
    ['no_table',       '[]',                     'البلجن اتشال → مصفوفة فاضية مش خطأ'],
    ['empty',          '[]',                     'مدخل فاضي → مفيش استعلام أصلاً'],
    ['dirty',          '{"7":4}',                'تكرار وأصفار وnull ونص وسالب → اتنضفوا'],
    ['single',         '{"n":6}',                'باقة واحدة'],
    ['single_missing', '{"n":0}',                'باقة مش موجودة → 0 مش null'],
    ['cached',         '{"first":2,"total":3}',  'فحص وجود الجدول بيتكاش'],
];

$fail = 0;
foreach ($tests as [$name, $want, $desc]) {
    $got = trim((string) shell_exec(escapeshellarg($php) . ' ' . escapeshellarg($case) . ' ' . escapeshellarg($name) . ' 2>&1'));
    if ($got === $want) {
        printf("  ✅ %s\n", $desc);
    } else {
        printf("  ❌ %s\n     جه: %s\n     متوقع: %s\n", $desc, $got, $want);
        $fail++;
    }
}
echo $fail === 0 ? "  " . count($tests) . "/" . count($tests) . " عدّوا\n" : "  $fail فشلوا\n";
exit($fail ? 1 : 0);
