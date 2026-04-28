<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'learnsimply_list_course_topics' );
function learnsimply_list_course_topics() {
	if ( ! isset( $_GET['create_test_quiz'] ) || $_GET['create_test_quiz'] !== '1' ) {
		return;
	}

	global $wpdb;

	$topic_id = 24444;

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID, post_title, post_type, menu_order
		 FROM {$wpdb->posts}
		 WHERE post_parent = %d
		 AND post_status = 'publish'
		 ORDER BY menu_order ASC",
		$topic_id
	) );

	echo '<pre>';
	echo "Children of topic ID {$topic_id}:\n\n";

	if ( empty( $results ) ) {
		echo "No published posts found with post_parent = {$topic_id}.\n";
	} else {
		foreach ( $results as $row ) {
			echo "ID: {$row->ID}  |  post_type: {$row->post_type}  |  menu_order: {$row->menu_order}  |  Title: {$row->post_title}\n";
		}
	}

	echo '</pre>';
	die();
}
