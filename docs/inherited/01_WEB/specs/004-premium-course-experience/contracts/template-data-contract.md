# Contract: PHP → Twig template data (single-course)

> العقد بين `tutor/single-course.php` (المزوّد) و `views/single-course.twig` (المستهلك). ده الـ "interface" الداخلي للفيچر — الـ backend MUST يوفّر الحقول دي، والـ frontend MUST يستهلكها زي الموصوف.

## `topics[]` → `topic.contents[]` (عنصر الدرس)

كل عنصر MUST يحتوي:

| حقل | نوع | موجود؟ | ملاحظات |
|---|---|---|---|
| `id` | int | ✅ | post ID |
| `type` | string | ✅ | `lesson` / `tutor_lesson` / `tutor_quiz` / غيره |
| `title` | string | ✅ | |
| `duration` | string | ✅ | `_video_duration` |
| **`permalink`** | string (url) | 🆕 لازم يتضاف | `get_permalink(id)` |
| **`is_preview`** | bool (`"1"`/`""`) | 🆕 لازم يتضاف | `get_post_meta(id,'_is_preview',true)` |

### سلوك الـ Twig المطلوب (FR-004)
```twig
{% for content in topic.contents %}
  {% if content.is_preview %}
    {# clickable → يفتح مشغّل المعاينة (data-attr بالـ permalink/lesson id) #}
    <button class="lecture-item lecture-item--preview" data-preview="{{ content.permalink }}"> … ▶ … </button>
  {% else %}
    {# مقفول — نص + أيقونة قفل، بلا لينك مكشوف #}
    <div class="lecture-item lecture-item--locked"> … 🔒 … </div>
  {% endif %}
{% endfor %}
```

> القاعدة الأمنية: **مفيش لينك مكشوف لمحتوى غير-preview**. مشغّل المعاينة يفتح محتوى المعاينة المسموح من Tutor بس.

## Homepage card context (FR-008/010)

`front-page.php` context → كل كارت كورس MUST يوفّر: `title`, `image`, `permalink`, `price` (حي), `discount_percent` (حي، 0 لو مفيش). الـ twig يعرض الخصم **فقط** لو `discount_percent > 0`.

## Bundle (FR-012)
منتج `easy_product_bundle` (بيانات WooCommerce، مش template) — الكارت/الصفحة يقرأ سعره الحي زي أي منتج. شراء واحد = سلوك البلجن القياسي.
