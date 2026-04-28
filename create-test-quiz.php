<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'learnsimply_list_course_topics' );
function learnsimply_list_course_topics() {
	if ( ! isset( $_GET['create_test_quiz'] ) || $_GET['create_test_quiz'] !== '1' ) {
		return;
	}

	$course_id = 24443;

	$topics = get_posts( [
		'post_type'      => 'tutor_topics',
		'post_parent'    => $course_id,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	] );

	echo '<pre>';
	echo "Topics for course ID {$course_id}:\n\n";

	if ( empty( $topics ) ) {
		echo "No topics found for this course.\n";
	} else {
		foreach ( $topics as $topic ) {
			echo "ID: {$topic->ID}  |  Title: {$topic->post_title}\n";
		}
	}

	echo '</pre>';
	die();
}
