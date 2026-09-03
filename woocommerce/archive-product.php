<?php
/**
 * Template for displaying product archives - Custom Raw HTML
 *
 * @package EduBlink_Child
 */

defined( 'ABSPATH' ) || exit;

// IMPORTANT: This template is ONLY for product archives (shop pages)
// Exit early if this is NOT a shop archive page (e.g., cart, checkout, single product, regular pages)

// Check URL directly FIRST - most reliable method
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
if ( strpos( $request_uri, '/product/' ) !== false ||
     strpos( $request_uri, '/cart' ) !== false || 
     strpos( $request_uri, '/checkout' ) !== false || 
     strpos( $request_uri, '/my-account' ) !== false ) {
    // Not a shop archive, let WordPress use the default page template
    return;
}

// Check if we're actually on a shop archive page
if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_post_type_archive( 'product' ) ) {
    // Not a shop archive, let WordPress use the default page template
    // Don't execute any code in this file
    return;
}

// Also check if this is a single product page
if ( is_product() || is_single() ) {
    // Not a shop archive, let WordPress use the default page template
    return;
}

// Check if shop-2 is the shop page and use custom template
// IMPORTANT: Only apply to shop archive pages, not regular pages
if ( is_shop() || is_product_category() || is_product_tag() || is_post_type_archive( 'product' ) ) {
    $woocommerce_shop_page_id = 0;
    if ( function_exists( 'wc_get_page_id' ) ) {
        $woocommerce_shop_page_id = wc_get_page_id( 'shop' );
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    $is_shop_2_url = ( strpos( $request_uri, '/shop-2' ) !== false || strpos( $request_uri, '/shop-2/' ) !== false );

    if ( $woocommerce_shop_page_id == 22662 || $is_shop_2_url ) {
        // Use custom shop-2 template
        if ( class_exists( 'Timber\Timber' ) ) {
            $shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';
            if ( file_exists( $shop_2_template ) ) {
                // Prevent Elementor
                add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
                add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
                add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
                add_filter( 'hfe_header_enabled', '__return_false' );
                add_filter( 'hfe_footer_enabled', '__return_false' );

                include( $shop_2_template );
                exit;
            }
        }
    }
}

// Continue with normal archive-product.php template for other shop pages
get_header();
?>

<div class="site-content">
	<div class="site-content-inner">
		
		<!-- Custom Shop Header -->
		<header class="custom-shop-header">
			<h1 class="custom-shop-title"><?php woocommerce_page_title(); ?></h1>
		</header>

		<!-- Custom Product Archive Content - Raw HTML -->
		<div class="custom-product-archive-content">
			<?php if ( woocommerce_product_loop() ) : ?>
				<div class="custom-products-list">
					<?php
					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();
							
							global $product;
							if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
								$product = wc_get_product( get_the_ID() );
							}
							
							if ( ! $product ) {
								continue;
							}
							
							$product_id = get_the_ID();
							$product_title = get_the_title();
							$product_excerpt = get_the_excerpt();
							$product_permalink = get_permalink();
							$product_image_id = $product->get_image_id();
							$product_price = $product->get_price_html();
							$product_regular_price = $product->get_regular_price();
							$product_sale_price = $product->get_sale_price();
							$is_on_sale = $product->is_on_sale();
							$product_rating = $product->get_average_rating();
							$product_rating_count = $product->get_rating_count();
							$product_sku = $product->get_sku();
							$product_stock_status = $product->get_stock_status();
							$product_categories = wc_get_product_terms( $product_id, 'product_cat', array( 'fields' => 'all' ) );
							$product_tags = wc_get_product_terms( $product_id, 'product_tag', array( 'fields' => 'all' ) );
					?>
						<article class="custom-product-item">
							<?php if ( $product_image_id ) : ?>
								<div class="custom-product-image">
									<a href="<?php echo esc_url( $product_permalink ); ?>">
										<?php echo wp_get_attachment_image( $product_image_id, 'full', false, array( 'class' => 'custom-product-img' ) ); ?>
									</a>
									<?php if ( $is_on_sale ) : ?>
										<span class="custom-sale-badge">Sale</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							
							<div class="custom-product-info">
								<h2 class="custom-product-title">
									<a href="<?php echo esc_url( $product_permalink ); ?>">
										<?php echo esc_html( $product_title ); ?>
									</a>
								</h2>
								
								<?php if ( $product_excerpt ) : ?>
									<div class="custom-product-excerpt">
										<?php echo esc_html( $product_excerpt ); ?>
									</div>
								<?php endif; ?>
								
								<?php if ( $product_rating && $product_rating > 0 ) : ?>
									<div class="custom-product-rating">
										<span class="custom-rating-value"><?php echo esc_html( number_format( $product_rating, 1 ) ); ?></span>
										<span class="custom-rating-count">(<?php echo esc_html( $product_rating_count ); ?> reviews)</span>
									</div>
								<?php endif; ?>
								
								<div class="custom-product-price-section">
									<?php if ( $is_on_sale && $product_sale_price ) : ?>
										<span class="custom-price-sale"><?php echo wc_price( $product_sale_price ); ?></span>
										<?php if ( $product_regular_price ) : ?>
											<del class="custom-price-regular"><?php echo wc_price( $product_regular_price ); ?></del>
										<?php endif; ?>
									<?php else : ?>
										<span class="custom-price"><?php echo $product_price; ?></span>
									<?php endif; ?>
								</div>
								
								<div class="custom-product-stock-status">
									<?php if ( 'instock' === $product_stock_status ) : ?>
										<span class="custom-stock-in-stock">In Stock</span>
									<?php else : ?>
										<span class="custom-stock-out-of-stock">Out of Stock</span>
									<?php endif; ?>
								</div>
								
								<?php if ( $product_sku ) : ?>
									<div class="custom-product-sku">
										<span class="custom-sku-label">SKU:</span>
										<span class="custom-sku-value"><?php echo esc_html( $product_sku ); ?></span>
									</div>
								<?php endif; ?>
								
								<?php if ( $product_categories && ! is_wp_error( $product_categories ) ) : ?>
									<div class="custom-product-categories">
										<span class="custom-categories-label">Categories:</span>
										<?php foreach ( $product_categories as $category ) : ?>
											<span class="custom-category-item"><?php echo esc_html( $category->name ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								
								<?php if ( $product_tags && ! is_wp_error( $product_tags ) ) : ?>
									<div class="custom-product-tags">
										<span class="custom-tags-label">Tags:</span>
										<?php foreach ( $product_tags as $tag ) : ?>
											<span class="custom-tag-item"><?php echo esc_html( $tag->name ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								
								<div class="custom-product-link">
									<a href="<?php echo esc_url( $product_permalink ); ?>" class="custom-view-product-btn">
										View Product
									</a>
								</div>
							</div>
						</article>
					<?php
						}
					}
					?>
				</div>
				
				<!-- Custom Pagination -->
				<div class="custom-pagination">
					<?php
					the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => __( 'Previous', 'edublink-child' ),
						'next_text' => __( 'Next', 'edublink-child' ),
					) );
					?>
				</div>
			<?php else : ?>
				<div class="custom-no-products">
					<p class="custom-no-products-message">No products found.</p>
				</div>
			<?php endif; ?>
		</div>

	</div><!-- .site-content-inner -->
</div><!-- .site-content -->

<?php
get_footer();

