<?php
/**
 * Template for AI Website Prompt tool page
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

$context = Timber::get_context();
$context['theme_uri'] = get_stylesheet_directory_uri();

Timber::render( 'page-ai-website-prompt.twig', $context );
