/**
 * Cart Page — Interactions
 * Handles WooCommerce cart item removal with smooth animation
 * The remove link uses WooCommerce's remove URL (server-side redirect with nonce).
 * We animate the item out before following the link.
 */
(function ($) {
    "use strict";

    $(document).ready(function () {

        // ── Animate out before navigating to WC remove URL ──
        $(document).on("click", ".lc-item__remove", function (e) {
            e.preventDefault();

            var $link = $(this);
            var $item = $link.closest(".lc-item");
            var href  = $link.attr("href");

            if ( ! href ) return;

            // Animate out
            $item.addClass("is-removing");
            setTimeout(function () {
                $item.addClass("is-collapsed");
            }, 260);

            // Navigate after animation
            setTimeout(function () {
                window.location.href = href;
            }, 480);
        });

    });

}(jQuery));
    // ─── Tutor LMS: Remove item button ───
    $(document).on("click", ".custom-remove-item-btn", function (e) {
      e.preventDefault();

      var $btn = $(this);
      var $item = $btn.closest(".custom-cart-item");
      var courseId = $btn.data("course-id");

      if (!courseId) return;

      // Disable button and show loading
      $btn.prop("disabled", true).css("opacity", "0.5");

      // Animate out
      $item.css({
        transition: "all 0.35s cubic-bezier(0.4,0,0.2,1)",
        opacity: "0",
        transform: "translateX(30px) scale(0.97)",
        maxHeight: $item.outerHeight() + "px",
        overflow: "hidden",
      });

      setTimeout(function () {
        $item.css({
          maxHeight: "0",
          padding: "0",
          margin: "0",
          borderWidth: "0",
        });
      }, 250);

      // Send AJAX request (Tutor LMS cart removal)
      $.ajax({
        url: window._tutorobject ? window._tutorobject.ajaxurl : "/wp-admin/admin-ajax.php",
        type: "POST",
        data: {
          action: "tutor_cart_remove_item",
          course_id: courseId,
          _tutor_nonce: window._tutorobject ? window._tutorobject.nonce : "",
        },
        success: function () {
          setTimeout(function () {
            $item.remove();
            updateCartCount();
          }, 400);
        },
        error: function () {
          // Revert animation on error
          $item.css({
            opacity: "1",
            transform: "none",
            maxHeight: "",
            padding: "",
            margin: "",
            borderWidth: "",
          });
          $btn.prop("disabled", false).css("opacity", "1");
        },
      });
    });

    // Update cart count after removal
    function updateCartCount() {
      var remaining = $(".custom-cart-item").length;
      if (remaining === 0) {
        // Fade out all sections and reload for empty state
        $(".custom-cart-items-section, .custom-cart-summary-section").css({
          transition: "opacity 0.3s",
          opacity: "0",
        });
        setTimeout(function () {
          location.reload();
        }, 400);
      } else {
        $(".custom-cart-count").text(remaining + (remaining === 1 ? " دورة في السلة" : " دورات في السلة"));
      }
    }

    // ─── WooCommerce: Enhance remove buttons (×) ───
    $("table.shop_table td.product-remove a.remove").each(function () {
      var $link = $(this);
      // Already has × character, just track for animation
      $link.on("click", function () {
        var $row = $(this).closest("tr");
        $row.css({
          transition: "opacity 0.3s, transform 0.3s",
          opacity: "0",
          transform: "translateX(20px)",
        });
      });
    });
  });
})(jQuery);
