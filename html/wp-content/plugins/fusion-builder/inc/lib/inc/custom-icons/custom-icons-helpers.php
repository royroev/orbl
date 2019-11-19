<?php
/**
 * Custom Icons helper functions.3
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion-Library
 * @since      2.2
 */

/**
 * Get Icon Set CSS URL.
 *
 * @since 6.2
 * @param int $post_id Post ID.
 * @return string URL.
 */
function fusion_get_custom_icons_css_url( $post_id = 0 ) {

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$icon_set = get_post_meta( $post_id, FUSION_ICONS_META_KEY, true );

	return ! empty( $icon_set['icon_set_dir_name'] ) ? FUSION_ICONS_BASE_URL . $icon_set['icon_set_dir_name'] . '/style.css' : '';
}

/**
 * WIP.
 *
 * @since 6.2
 * @return array Icon array.
 */
function fusion_get_custom_icons_array() {
	global $post;

	$upload_dir         = wp_upload_dir();
	$icons_base_dir_url = trailingslashit( $upload_dir['baseurl'] ) . 'fusion-icons/';

	$_post        = $post;
	$custom_icons = [];
	$args         = [
		'post_type'      => 'fusion_icons',
		'posts_per_page' => -1, // phpcs:ignore WPThemeReview.CoreFunctionality.PostsPerPage.posts_per_page_posts_per_page
	];

	$query = new WP_Query( $args );
	while ( $query->have_posts() ) :
		$query->the_post();
		$custom_icons[ $post->post_name ]            = get_post_meta( $post->ID, '_fusion_custom_icon_set', true );
		$custom_icons[ $post->post_name ]['name']    = get_the_title();
		$custom_icons[ $post->post_name ]['css_url'] = fusion_get_custom_icons_css_url();
	endwhile;
	wp_reset_postdata();
	$post = $_post;

	return apply_filters( 'fusion_custom_icons', $custom_icons );
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
