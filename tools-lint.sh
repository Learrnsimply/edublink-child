#!/bin/bash
# فحص محلي سريع بـPHP بتاع MAMP. الاستخدام: ./tools-lint.sh
PHP=${PHP:-/Applications/MAMP/bin/php/php8.3.30/bin/php}
[ -x "$PHP" ] || { echo "PHP مش موجود في $PHP — عدّل المسار أو حط PHP=..."; exit 1; }
fail=0; n=0
while IFS= read -r f; do
  n=$((n+1))
  out=$("$PHP" -l "$f" 2>&1) || { echo "❌ $f"; echo "$out" | head -3; fail=1; }
done < <(find . -path ./vendor -prune -o -name '*.php' -print)
[ "$fail" -eq 0 ] && echo "✅ $n ملف · صفر أخطاء" || echo "⚠️  فيه أخطاء — متعملش commit."
exit $fail
