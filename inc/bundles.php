<?php
/**
 * عدّاد عناصر الباقات — مصدر واحد بدل خمس استعلامات مبعثرة.
 *
 * الجدول `asnp_wepb_simple_bundle_items` تبع بلجن خارجي (Easy Product Bundles).
 * الكود كان بيستعلم عليه مباشرة في خمس مواضع من غير أي حارس: لو البلجن اتشال
 * أو غيّر اسم جدوله، الاستعلام بيرجّع null **بصمت** والباقات بتختفي من غير أي
 * رسالة خطأ — وده أسوأ نوع عطل لأن محدش بياخد باله.
 *
 * وفي الرئيسية كان أسوأ: استعلام منفصل لكل باقة جوه الحلقة (N+1)، فالرقم
 * بيكبر مع كل باقة تتضاف.
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * هل جدول البلجن موجود؟ النتيجة متكاشة عشان منسألش الداتابيز كل مرة.
 *
 * @return bool
 */
function learnsimply_bundles_table_exists() {
	static $exists = null;
	if ( null !== $exists ) {
		return $exists;
	}

	$cached = get_transient( 'ls_bundles_table_exists' );
	if ( false !== $cached ) {
		$exists = ( '1' === $cached );
		return $exists;
	}

	global $wpdb;
	$table  = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
	$found  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	$exists = ( $found === $table );

	set_transient( 'ls_bundles_table_exists', $exists ? '1' : '0', HOUR_IN_SECONDS );

	return $exists;
}

/**
 * عدد عناصر كل باقة — **استعلام واحد** لكل المعرّفات مهما كان عددها.
 *
 * @param int[] $bundle_ids معرّفات منتجات الباقات.
 * @return array<int,int> معرّف => عدد العناصر. الباقة الفاضية بتيجي 0.
 */
function learnsimply_bundle_item_counts( array $bundle_ids ) {
	// intval مش absint: absint(-3) بترجع 3 — يعني معرّف سالب كان بيتحوّل لمعرّف
	// تاني صالح ويجيب بيانات باقة غلط، بدل ما يترمي.
	$bundle_ids = array_map( 'intval', $bundle_ids );
	$bundle_ids = array_values( array_unique( array_filter( $bundle_ids, static function ( $id ) {
		return $id > 0;
	} ) ) );
	if ( empty( $bundle_ids ) || ! learnsimply_bundles_table_exists() ) {
		return array();
	}

	global $wpdb;
	$table        = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
	$placeholders = implode( ',', array_fill( 0, count( $bundle_ids ), '%d' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- اسم الجدول والعناصر النائبة مبنيين هنا، والقيم بتعدّي من prepare
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT bundle_id, COUNT(*) AS items FROM {$table} WHERE bundle_id IN ({$placeholders}) GROUP BY bundle_id",
			$bundle_ids
		)
	);

	$out = array();
	foreach ( $bundle_ids as $id ) {
		$out[ $id ] = 0; // الباقة اللي مالهاش صفوف لازم ترجع 0 مش تغيب
	}
	foreach ( (array) $rows as $row ) {
		$out[ (int) $row->bundle_id ] = (int) $row->items;
	}

	return $out;
}

/**
 * عدد عناصر باقة واحدة.
 *
 * @param int $bundle_id معرّف المنتج.
 * @return int
 */
function learnsimply_bundle_item_count( $bundle_id ) {
	$counts = learnsimply_bundle_item_counts( array( $bundle_id ) );
	return isset( $counts[ (int) $bundle_id ] ) ? $counts[ (int) $bundle_id ] : 0;
}

/**
 * معرّفات المنتجات جوه كل باقة — **استعلام واحد** لكل المعرّفات، بنفس حارس الجدول.
 *
 * الرئيسية بتستخدمها عشان تعرض «إيه اللي جوه الباقة» (أسماء الكورسات ومجموع ساعاتها).
 *
 * @param int[] $bundle_ids معرّفات منتجات الباقات.
 * @return array<int,int[]> معرّف الباقة => معرّفات المنتجات بترتيب الإدخال. الباقة الفاضية مصفوفة فاضية.
 */
function learnsimply_bundle_item_product_ids( array $bundle_ids ) {
	$bundle_ids = array_map( 'intval', $bundle_ids );
	$bundle_ids = array_values( array_unique( array_filter( $bundle_ids, static function ( $id ) {
		return $id > 0;
	} ) ) );
	$out = array();
	foreach ( $bundle_ids as $id ) {
		$out[ $id ] = array();
	}
	if ( empty( $bundle_ids ) || ! learnsimply_bundles_table_exists() ) {
		return $out;
	}

	global $wpdb;
	$table        = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
	$placeholders = implode( ',', array_fill( 0, count( $bundle_ids ), '%d' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- اسم الجدول والعناصر النائبة مبنيين هنا، والقيم بتعدّي من prepare
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT bundle_id, product_id FROM {$table} WHERE bundle_id IN ({$placeholders}) ORDER BY id ASC",
			$bundle_ids
		)
	);
	foreach ( (array) $rows as $row ) {
		$bundle_id = (int) $row->bundle_id;
		if ( isset( $out[ $bundle_id ] ) && (int) $row->product_id > 0 ) {
			$out[ $bundle_id ][] = (int) $row->product_id;
		}
	}
	return $out;
}
