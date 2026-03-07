/**
 * Single Product Bundle Page JavaScript
 * Handles interactive functionality for the bundle product page
 */

document.addEventListener("DOMContentLoaded", function () {
	// Handle review form submit loading state
	const reviewForms = document.querySelectorAll(".add-review-section form");
	reviewForms.forEach((form) => {
		form.addEventListener("submit", function () {
			const submitBtn =
				form.querySelector('button[type="submit"]') ||
				form.querySelector('input[type="submit"]');
			if (!submitBtn) return;

			if (!submitBtn.dataset.originalText) {
				submitBtn.dataset.originalText = submitBtn.textContent;
          }

			submitBtn.classList.add("is-loading");
			submitBtn.disabled = true;
      });
    });

	// Product star rating: same as single-product (run after WooCommerce)
	const initProductStars = () => {
		const productStarInput = document.getElementById("product-star-rating-input");
		if (!productStarInput) return;
		const container = productStarInput.closest(".comment-form-rating");
		if (container) container.querySelectorAll("p.stars").forEach((el) => el.remove());
		const stars = productStarInput.querySelectorAll(".star-icon");
		const ratingSelect = document.getElementById("rating");
		if (!stars.length || !ratingSelect) return;
		let currentRating = parseInt(ratingSelect.value || "0", 10) || 0;
		const updateStarsVisual = (rating, isHover = false) => {
			stars.forEach((star) => {
				const r = parseInt(star.dataset.rating, 10);
				star.classList.remove("filled", "hovered");
				if (r <= rating) star.classList.add(isHover ? "hovered" : "filled");
			});
		};
		updateStarsVisual(currentRating);
		stars.forEach((star) => {
			star.addEventListener("mouseenter", () => updateStarsVisual(parseInt(star.dataset.rating, 10), true));
			star.addEventListener("mouseleave", () => updateStarsVisual(currentRating));
			star.addEventListener("click", (e) => {
				e.preventDefault();
				currentRating = parseInt(star.dataset.rating, 10);
				ratingSelect.value = String(currentRating);
				updateStarsVisual(currentRating);
			});
		});
	};
	setTimeout(initProductStars, 50);

	// Description card expand/collapse functionality
	const showMoreBtn = document.querySelector(".show-more-btn");
	const descriptionCard = document.querySelector(".description-card");
	
	if (showMoreBtn && descriptionCard) {
		showMoreBtn.addEventListener("click", function () {
			const isExpanded = descriptionCard.classList.contains("expanded");
			
			if (isExpanded) {
				descriptionCard.classList.remove("expanded");
				this.textContent = "عرض المزيد";
    } else {
				descriptionCard.classList.add("expanded");
				this.textContent = "عرض أقل";
          }
        });
	}
});

// Share product function
function shareProduct() {
	if (navigator.share) {
		navigator
			.share({
				title: document.title,
				text: document.querySelector(".course-title")?.textContent || "",
				url: window.location.href,
			})
			.catch((error) => {
				console.log("Error sharing:", error);
      });
	} else {
		// Fallback: copy to clipboard
		navigator.clipboard.writeText(window.location.href).then(
			() => {
				alert("تم نسخ رابط المنتج!");
			},
			() => {
				alert("فشل نسخ الرابط");
    }
		);
  }
}
