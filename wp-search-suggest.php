<?php
/**
 * Plugin Name: WP Search Suggest
 * Plugin URI:  http://en.obenland.it/wp-search-suggest/#utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Description: Provides title suggestions while typing a search query, using the built-in jQuery suggest script.
 * Version:     6
 * Author:      Konstantin Obenland
 * Author URI:  http://en.obenland.it/#utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Text Domain: wp-search-suggest
 * Domain Path: /lang
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package wp-search-suggest
 */

/**
 * Registers the script and stylesheet.
 *
 * The scripts and stylesheets can easily be deregistered by calling
 * <code>wp_deregister_script( 'wp-search-suggest' );</code> or
 * <code>wp_deregister_style( 'wp-search-suggest' );</code> on the init hook.
 */
function wpss_init() {
	$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' );
	$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

	wp_register_script( 'wp-search-suggest', plugins_url( "js/wpss-search-suggest$suffix.js", __FILE__ ), array( 'suggest' ), $plugin_data['Version'], true );
	wp_localize_script(
		'wp-search-suggest',
		'wpss_options',
		array(
			'url'     => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpss-post-url' ),
			'ajaxurl' => add_query_arg(
				array(
					'action'   => 'wp-search-suggest',
					'_wpnonce' => wp_create_nonce( 'wp-search-suggest' ),
				),
				admin_url( 'admin-ajax.php' )
			),
		)
	);

	wp_register_style( 'wp-search-suggest', plugins_url( "css/wpss-search-suggest$suffix.css", __FILE__ ), array(), $plugin_data['Version'] );
}
add_action( 'init', 'wpss_init', 9 );

/**
 * Enqueues the script and style.
 */
function wpss_enqueue_scripts() {
	wp_enqueue_script( 'wp-search-suggest' );
	wp_enqueue_style( 'wp-search-suggest' );
}
add_action( 'wp_enqueue_scripts', 'wpss_enqueue_scripts' );

/**
 * Handles the AJAX request for the search term.
 */
function wpss_ajax_response() {
	check_ajax_referer( 'wp-search-suggest' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$s = trim( stripslashes( $_GET['q'] ) );

	$query_args = apply_filters(
		'wpss_search_query_args',
		array(
			's'           => $s,
			'post_status' => 'publish',
		),
		$s
	);

	$query = new WP_Query( $query_args );

	if ( $query->posts ) {
		$results = apply_filters( 'wpss_search_results', wp_list_pluck( $query->posts, 'post_title' ), $query );
		echo wp_kses_post( join( "\n", $results ) );
	}

	wp_die();
}
add_action( 'wp_ajax_wp-search-suggest', 'wpss_ajax_response' );
add_action( 'wp_ajax_nopriv_wp-search-suggest', 'ajax_response' );

/**
 * Handles the AJAX request for a specific title.
 */
function wpss_post_url() {
	check_ajax_referer( 'wpss-post-url' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$post = wpss_get_post_id_from_title( trim( stripslashes( $_REQUEST['title'] ) ) );

	if ( $post ) {
		echo esc_url( get_permalink( $post ) );
	}

	wp_die();
}
add_action( 'wp_ajax_wpss-post-url', 'wpss_post_url' );
add_action( 'wp_ajax_nopriv_wpss-post-url', 'wpss_post_url' );

/**
 * Examines a title and tries to determine the post ID it represents.\
 *
 * @param string $title Post title to check.
 * @return int Post ID or 0 on failure.
 */
function wpss_get_post_id_from_title( $title ) {
	global $wpdb;

	$post_id = wp_cache_get( 'wpss_post_title' . $title, 'post' );

	if ( false === $post_id ) {
		$post_id = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_status = 'publish' LIMIT 1",
				$title
			)
		);

		wp_cache_set( 'wpss_post_title' . $title, $post_id, 'post' );
	}

	return absint( $post_id );
}
