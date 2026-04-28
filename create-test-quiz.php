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

	$course_id = 24443;

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID, post_title, post_type, post_parent
		 FROM {$wpdb->posts}
		 WHERE post_parent = %d
		 AND post_status = 'publish'",
		$course_id
	) );

	echo '<pre>';
	echo "Children of course ID {$course_id}:\n\n";

	if ( empty( $results ) ) {
		echo "No published posts found with post_parent = {$course_id}.\n";
	} else {
		foreach ( $results as $row ) {
			echo "ID: {$row->ID}  |  post_type: {$row->post_type}  |  post_parent: {$row->post_parent}  |  Title: {$row->post_title}\n";
		}
	}

	echo '</pre>';
	die();
}
