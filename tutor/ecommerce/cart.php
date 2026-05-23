<?php
/**
 * Custom Cart Template — Modern Redesign
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tutor_utils' ) ) {
	echo 'Tutor LMS is not active';
	exit;
}

use Tutor\Ecommerce\CartController;
use Tutor\Ecommerce\CheckoutController;
use Tutor\Ecommerce\Tax;
use Tutor\Models\CourseModel;

$cart_controller   = new CartController();
$get_cart          = $cart_controller->get_cart_items();
$courses           = $get_cart['courses'];
$total_count       = $courses['total_count'];
$course_list       = $courses['results'];
$subtotal          = 0;
$tax_exempt_price  = 0;
$checkout_page_url = CheckoutController::get_page_url();
?>

<div class="lc-cart">

	<?php if ( is_array( $course_list ) && count( $course_list ) > 0 ) : ?>

	<!-- ── Page Hero ── -->
	<div class="lc-cart-hero">
		<div class="lc-cart-hero__icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
		</div>
		<div class="lc-cart-hero__text">
			<h1 class="lc-cart-hero__title">سلة التسوق</h1>
			<p class="lc-cart-hero__count">
				<?php echo esc_html( sprintf(
					_n( '%d دورة في سلتك', '%d دورات في سلتك', $total_count, 'tutor' ),
					$total_count
				) ); ?>
			</p>
		</div>
	</div>

	<!-- ── Two-column layout ── -->
	<div class="lc-cart-layout">

		<!-- ── Items Column ── -->
		<div class="lc-items-col">
			<div class="lc-items-list">
				<?php foreach ( $course_list as $course ) :
					$course_duration  = get_tutor_course_duration_context( $course->ID, true );
					$course_price     = tutor_utils()->get_raw_course_price( $course->ID );
					$regular_price    = $course_price->regular_price;
					$sale_price       = $course_price->sale_price;
					$display_price    = $sale_price ? $sale_price : $regular_price;
					$course_image     = get_tutor_course_thumbnail_src( '', $course->ID );
					$course_permalink = get_permalink( $course->ID );
					$course_level     = get_tutor_course_level( $course->ID );
					$course_author    = get_the_author_meta( 'display_name', $course->post_author );

					$subtotal += $display_price;

					$tax_collection = CourseModel::is_tax_enabled_for_single_purchase( $course->ID );
					if ( ! $tax_collection ) {
						$tax_exempt_price += $display_price;
					}
				?>
				<div class="lc-item" data-course-id="<?php echo esc_attr( $course->ID ); ?>">

					<a href="<?php echo esc_url( $course_permalink ); ?>" class="lc-item__thumb">
						<img src="<?php echo esc_url( $course_image ); ?>" alt="<?php echo esc_attr( $course->post_title ); ?>" loading="lazy">
					</a>

					<div class="lc-item__body">
						<h3 class="lc-item__title">
							<a href="<?php echo esc_url( $course_permalink ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
						</h3>
						<div class="lc-item__meta">
							<?php if ( $course_author ) : ?>
								<span class="lc-item__meta-tag">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
									<?php echo esc_html( $course_author ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $course_duration ) : ?>
								<span class="lc-item__meta-tag">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
									<?php echo esc_html( tutor_utils()->clean_html_content( $course_duration ) ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $course_level ) : ?>
								<span class="lc-item__meta-tag">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
									<?php echo esc_html( $course_level ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<div class="lc-item__side">
						<div class="lc-item__price">
							<?php if ( $regular_price && $sale_price && $sale_price !== $regular_price ) : ?>
								<span class="lc-item__price-old"><?php tutor_print_formatted_price( $regular_price ); ?></span>
							<?php endif; ?>
							<span class="lc-item__price-current"><?php tutor_print_formatted_price( $display_price ); ?></span>
						</div>
						<button
							type="button"
							class="custom-remove-item-btn"
							data-course-id="<?php echo esc_attr( $course->ID ); ?>"
							aria-label="إزالة <?php echo esc_attr( $course->post_title ); ?> من السلة"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="3 6 5 6 21 6"/>
								<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
								<line x1="10" y1="11" x2="10" y2="17"/>
								<line x1="14" y1="11" x2="14" y2="17"/>
							</svg>
							<span>إزالة</span>
						</button>
					</div>

				</div><!-- .lc-item -->
				<?php endforeach; ?>
			</div><!-- .lc-items-list -->

			<a href="<?php echo esc_url( tutor_utils()->course_archive_page_url() ); ?>" class="lc-continue-link">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
				متابعة التسوق
			</a>
		</div><!-- .lc-items-col -->

		<!-- ── Summary Column ── -->
		<div class="lc-summary-col">
			<div class="lc-summary-card">

				<div class="lc-summary-card__header">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
						<polyline points="14 2 14 8 20 8"/>
						<line x1="16" y1="13" x2="8" y2="13"/>
						<line x1="16" y1="17" x2="8" y2="17"/>
					</svg>
					ملخص الطلب
				</div>

				<?php
				$should_calc_tax = Tax::should_calculate_tax();
				$is_tax_included = Tax::is_tax_included_in_price();
				$tax_rate        = Tax::get_user_tax_rate();
				$tax_amount      = 0;

				if ( $should_calc_tax ) {
					$tax_amount        = Tax::calculate_tax( $subtotal, $tax_rate );
					$tax_exempt_amount = Tax::calculate_tax( $tax_exempt_price, $tax_rate );
					$tax_amount        = $tax_amount - $tax_exempt_amount;
				}

				$grand_total = $subtotal;
				if ( ! $is_tax_included ) {
					$grand_total += $tax_amount;
				}
				?>

				<div class="lc-summary-rows">
					<div class="lc-summary-row">
						<span class="lc-summary-row__label">المجموع الفرعي</span>
						<span class="lc-summary-row__val"><?php tutor_print_formatted_price( $subtotal ); ?></span>
					</div>
					<?php if ( $should_calc_tax && $tax_rate > 0 && ! $is_tax_included ) : ?>
						<div class="lc-summary-row">
							<span class="lc-summary-row__label">ضريبة (<?php echo esc_html( $tax_rate ); ?>%)</span>
							<span class="lc-summary-row__val"><?php tutor_print_formatted_price( $tax_amount ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $should_calc_tax && $tax_rate > 0 && $is_tax_included ) : ?>
						<p class="lc-summary-tax-note">
							<?php echo esc_html( sprintf( __( '(شامل الضريبة %s)', 'tutor' ), tutor_get_formatted_price( $tax_amount ) ) ); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="lc-summary-total">
					<span class="lc-summary-total__label">الإجمالي</span>
					<span class="lc-summary-total__val"><?php tutor_print_formatted_price( $grand_total ); ?></span>
				</div>

				<?php if ( $checkout_page_url ) : ?>
					<a href="<?php echo esc_url( $checkout_page_url ); ?>" class="lc-checkout-btn">
						<span>إتمام الشراء الآن</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transform:scaleX(-1)">
							<line x1="5" y1="12" x2="19" y2="12"/>
							<polyline points="12 5 19 12 12 19"/>
						</svg>
					</a>
				<?php else : ?>
					<p class="lc-error">صفحة الدفع غير مُعرّفة</p>
				<?php endif; ?>

				<div class="lc-trust-badges">
					<div class="lc-trust-badge">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
						دفع آمن ومشفر
					</div>
					<div class="lc-trust-badge">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg>
						ضمان استرداد 7 أيام
					</div>
					<div class="lc-trust-badge">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						وصول فوري للمحتوى
					</div>
				</div>

			</div><!-- .lc-summary-card -->
		</div><!-- .lc-summary-col -->

	</div><!-- .lc-cart-layout -->

	<?php else : ?>

	<!-- ── Empty Cart ── -->
	<div class="lc-empty">
		<div class="lc-empty__inner">
			<div class="lc-empty__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
					<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
				</svg>
			</div>
			<h2 class="lc-empty__title">السلة فارغة!</h2>
			<p class="lc-empty__sub">لم تقم بإضافة أي دورة بعد.<br>تصفح الدورات وابدأ رحلتك التعليمية.</p>
			<a href="<?php echo esc_url( tutor_utils()->course_archive_page_url() ); ?>" class="lc-empty__btn">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
				تصفح الدورات
			</a>
		</div>
	</div>

	<?php endif; ?>

</div><!-- .lc-cart -->
