# Data Model: Premium Course Experience

> Phase 1. الكيانات من النظام القائم (Tutor LMS + WooCommerce) — التغيير الأساسي = **حقلين جديدين على عنصر الدرس**.

## Entities

### Course (Tutor CPT `courses`)
- `id`, `title`, `excerpt`, `content` (description/safe_html)
- `instructors[]`, `duration`, `lesson_count`, `level`, `language`
- `price`, `regular_price`, `sale_price`, `discount_percent`
- `benefits[]` (ماذا ستتعلم) · `intro video` (`course_video`) · `rating_avg`/`rating_count` · `students_count`/`students_avatars[]`
- `is_enrolled`, `lesson_url` (أول درس — للمسجّل)
- **مصدر:** `tutor/single-course.php` context (موجود بالكامل)

### Topic (Tutor `topics`)
- `id`, `title`, `contents[]` (الدروس داخله)
- **مصدر:** `tutor_utils()->get_topics()` (موجود)

### Lesson / Content item ⭐ (التغيير الأساسي)
الحقول الحالية: `id`, `type` (`lesson`/`tutor_lesson`/`tutor_quiz`), `title`, `duration` (`_video_duration`).
**حقلان جديدان لازم يتضافوا في `tutor/single-course.php`:**

| حقل جديد | المصدر | الاستخدام |
|---|---|---|
| `permalink` | `get_permalink($content_id)` | لينك الدرس/المعاينة (clickable) |
| `is_preview` | `get_post_meta($content_id, '_is_preview', true)` | يحدّد القابل للمعاينة (مشغّل) vs المقفول (قفل) |

> **Invariant:** درس `is_preview=1` MUST يبقى clickable + يفتح مشغّل؛ غيره MUST يفضل مقفول (لا لينك مكشوف لمحتوى مدفوع). 19 درس عندهم `is_preview=1` حالياً.

### Bundle (WooCommerce `easy_product_bundle`)
- `id`, `title`, `included_products[]` (≥2 كورسات), `bundle_price`, `regular_sum` (مجموع المنفصل), `savings`
- **نموذج موجود:** 33336 («Java+OOP»، 849 ج). **المطلوب:** منتج جديد (جافا + DS).
- **Invariant:** `bundle_price` < `regular_sum` (قيمة مجمّعة حقيقية — FR-012).

### Course Card (عرض هومبيج — مشتق، مش كيان مخزّن)
- `course_or_bundle_ref`, `title`, `image`, `price` (حي), `discount` (حي), `url`
- **مصدر:** `front-page.php` context → `featured-courses-section.twig`
- **Invariant:** السعر/الخصم حي من WooCommerce (لا خصم وهمي/منتهي — FR-010).

## Relationships

```
Course 1───* Topic 1───* Lesson(content)        (Tutor curriculum)
Course *───* Bundle      (الباقة بتجمّع كورسات)   (easy_product_bundle)
Course/Bundle 1───1 Course Card                  (عرض هومبيج مشتق)
```

## State / Visibility rules
- درس: `is_preview` → **playable** · غيره → **locked** (بغضّ النظر عن `is_enrolled`؛ المسجّل عنده «ابدأ التعلم» المنفصل).
- Course Card: يعرض الخصم **فقط لو** نشط وحقيقي.
