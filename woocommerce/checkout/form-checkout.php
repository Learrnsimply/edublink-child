<?php
/**
 * Checkout Form — LearnSimply custom redesign (v2)
 *
 * Overrides the default WooCommerce form-checkout template to render the
 * single-column dark layout: title → "طلبك" → order review (with thumbnails)
 * → payment methods → pledge checkbox → place order → trust box.
 *
 * IMPORTANT: All WooCommerce hooks, IDs and classes are preserved
 * (form.checkout, #customer_details, #order_review, #payment, #place_order)
 * so checkout.js, gateway scripts and AJAX updates keep working.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 */

if (!defined('ABSPATH')) {
	exit;
}

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
	echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
	return;
}

?>

<div class="ls-checkout-v2">

	<h1 class="ls-checkout-title"><?php esc_html_e('إتمام الطلب', 'edublink-child'); ?></h1>
	<div class="ls-checkout-divider" aria-hidden="true"></div>

	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">

		<?php if ($checkout->get_checkout_fields()) : ?>

			<?php do_action('woocommerce_checkout_before_customer_details'); ?>

			<?php
			// A signed-in buyer has their name and email on file, so the block
			// stays hidden and submits the pre-filled values. A first-time buyer
			// has to type them — the account is created from what they enter —
			// so for them the block is visible. Hiding it from a guest would
			// leave them staring at a required-field error with no field to fill.
			$ls_details_class = is_user_logged_in() ? 'ls-customer-details-hidden' : 'ls-customer-details-visible';
			?>
			<div class="<?php echo esc_attr($ls_details_class); ?>">
				<div class="col2-set" id="customer_details">
					<div class="col-1">
						<?php do_action('woocommerce_checkout_billing'); ?>
					</div>

					<div class="col-2">
						<?php do_action('woocommerce_checkout_shipping'); ?>
					</div>
				</div>
			</div>

			<?php do_action('woocommerce_checkout_after_customer_details'); ?>

		<?php endif; ?>

		<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

		<h3 id="order_review_heading" class="ls-checkout-subtitle"><?php esc_html_e('طلبك', 'edublink-child'); ?></h3>

		<?php do_action('woocommerce_checkout_before_order_review'); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action('woocommerce_checkout_order_review'); ?>
		</div>

		<?php do_action('woocommerce_checkout_after_order_review'); ?>

	</form>

</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
