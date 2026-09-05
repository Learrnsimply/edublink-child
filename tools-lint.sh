#!/bin/bash
# فحص محلي سريع بـPHP بتاع MAMP. الاستخدام: ./tools-lint.sh
PHP=${PHP:-/Applications/MAMP/bin/php/php8.3.30/bin/php}
[ -x "$PHP" ] || { echo "PHP مش موجود في $PHP — عدّل المسار أو حط PHP=..."; exit 1; }
fail=0; n=0
while IFS= read -r f; do
  n=$((n+1))
  out=$("$PHP" -l "$f" 2>&1) || { echo "❌ $f"; echo "$out" | head -3; fail=1; }
done < <(find . -path ./vendor -prune -o -name '*.php' -print)
if [ "$fail" -eq 0 ]; then echo "✅ $n ملف PHP · صفر أخطاء"; else echo "⚠️  أخطاء PHP — متعملش commit."; fi

# مراجع قوالب Twig. Twig بيحل الـinclude وقت التشغيل، فترجمة القالب بتعدّي
# حتى لو الملف المضمَّن اتمسح — الفاحص ده بيمسك ده قبل ما يوصل السيرفر.
"$PHP" tools-twig-check.php || fail=1

# اختبارات سلوك — الفحص البنيوي بيقول إن الكود بيترجم، مش إنه بيشتغل صح.
if [ -f tests/bundles-test.php ]; then
  "$PHP" tests/bundles-test.php || fail=1
fi

# اختبار تشغيل الرئيسية — php -l بيمسك الصياغة بس. ده بيشغّل front-page.php فعليًا بـstubs
# ويمسك أخطاء وقت التشغيل (دالة غير معرّفة · تضمين مزدوج · شكل الـcontext).
if [ -f tests/front-page-smoke.php ]; then
  "$PHP" tests/front-page-smoke.php || fail=1
fi

exit $fail
