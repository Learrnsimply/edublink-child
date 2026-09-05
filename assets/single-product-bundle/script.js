/**
 * صفحة الباقة — عرض باقي الآراء + نجوم فورم تقييم ووكومرس.
 * الفورم نفسه فورم ووكومرس العادي (comment_form) بيتبعت عادي وبيرجع لنفس الصفحة.
 */
(function () {
	"use strict";

	var moreBtn = document.querySelector("[data-more-btn]");
	if (moreBtn) {
		moreBtn.addEventListener("click", function () {
			document.querySelectorAll("[data-more]").forEach(function (el) { el.hidden = false; });
			moreBtn.hidden = true;
		});
	}

	// نجوم الفورم: بنملّي <select name="rating"> المخفي بتاع ووكومرس
	var box = document.getElementById("product-star-rating-input");
	var select = document.getElementById("rating");
	if (!box || !select) return;
	var wrap = box.closest(".comment-form-rating");
	if (wrap) wrap.querySelectorAll("p.stars").forEach(function (el) { el.remove(); });
	var stars = Array.prototype.slice.call(box.querySelectorAll(".star-icon"));
	var paint = function (n, hover) {
		stars.forEach(function (s) {
			var r = parseInt(s.dataset.rating, 10);
			s.classList.remove("filled", "hovered");
			if (r <= n) s.classList.add(hover ? "hovered" : "filled");
		});
	};
	var current = parseInt(select.value || "0", 10) || 0;
	paint(current);
	stars.forEach(function (s) {
		s.addEventListener("click", function () { current = parseInt(s.dataset.rating, 10); select.value = String(current); paint(current); });
		s.addEventListener("mouseenter", function () { paint(parseInt(s.dataset.rating, 10), true); });
		s.addEventListener("mouseleave", function () { paint(current); });
	});

	var form = box.closest("form");
	if (form) {
		form.addEventListener("submit", function () {
			var btn = form.querySelector('input[type="submit"], button[type="submit"]');
			if (btn) btn.disabled = true;
		});
	}
})();
