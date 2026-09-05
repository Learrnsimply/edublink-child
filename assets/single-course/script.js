/**
 * صفحة الكورس — سلوك بسيط: عرض باقي التقييمات، نجوم فورم التقييم، وإرسال التقييم لـTutor.
 * الأكورديون (المنهج والأسئلة) بـ<details> من غير JS.
 */
(function () {
	"use strict";

	// كل التقييمات — الأول ٣ ظاهرين والباقي hidden
	var moreBtn = document.querySelector("[data-more-btn]");
	if (moreBtn) {
		moreBtn.addEventListener("click", function () {
			document.querySelectorAll("[data-more]").forEach(function (el) { el.hidden = false; });
			moreBtn.hidden = true;
		});
	}

	// نجوم فورم التقييم
	var starsBox = document.getElementById("star-rating-input");
	var ratingInput = document.getElementById("tutor_rating_gen_input");
	if (starsBox && ratingInput) {
		var stars = Array.prototype.slice.call(starsBox.querySelectorAll(".lsc-stars-input__s"));
		var paint = function (n) {
			stars.forEach(function (s) { s.classList.toggle("is-on", parseInt(s.dataset.rating, 10) <= n); });
		};
		stars.forEach(function (s) {
			s.addEventListener("click", function () { ratingInput.value = s.dataset.rating; paint(parseInt(s.dataset.rating, 10)); });
			s.addEventListener("mouseenter", function () { paint(parseInt(s.dataset.rating, 10)); });
			s.addEventListener("mouseleave", function () { paint(parseInt(ratingInput.value, 10) || 0); });
		});
	}

	// إرسال التقييم — نفس endpoint Tutor (tutor_place_rating) عبر admin-ajax
	var form = document.getElementById("tutor-review-form");
	if (!form) return;
	form.addEventListener("submit", function (e) {
		e.preventDefault();
		var btn = form.querySelector(".submit-review-btn");
		var msg = document.getElementById("review-message");
		var say = function (text, ok) { if (!msg) return; msg.textContent = text; msg.hidden = false; msg.classList.toggle("is-ok", !!ok); msg.classList.toggle("is-err", !ok); };

		if (!ratingInput || parseInt(ratingInput.value, 10) < 1) { say("اختار عدد النجوم الأول", false); return; }
		var text = form.querySelector('textarea[name="review"]');
		if (!text || !text.value.trim()) { say("اكتب تقييمك", false); return; }

		btn.disabled = true;
		var url = (window.lsCourseAjax && window.lsCourseAjax.url) || window.ajaxurl || "/wp-admin/admin-ajax.php";
		fetch(url, { method: "POST", body: new FormData(form), credentials: "same-origin" })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data && data.success) {
					say("وصل تقييمك، شكرًا. هيظهر بعد المراجعة.", true);
					setTimeout(function () { window.location.reload(); }, 1800);
				} else {
					say((data && data.data && data.data.message) || "حصل خطأ، جرّب تاني.", false);
					btn.disabled = false;
				}
			})
			.catch(function () { say("مفيش اتصال، جرّب تاني.", false); btn.disabled = false; });
	});
})();
