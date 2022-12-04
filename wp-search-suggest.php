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
 * @package WP Search Suggest
 */

if ( ! class_exists( 'Obenland_Wp_Plugins_V4' ) ) {
	require_once 'obenland-wp-plugins.php';
}

/**
 * Class Obenland_Wp_Search_Suggest
 */
class Obenland_Wp_Search_Suggest extends Obenland_Wp_Plugins_V4 {

	/**
	 * Constructor.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 16.04.2011
	 * @access public
	 */
	public function __construct() {
		parent::__construct( array(
			'textdomain'     => 'wp-search-suggest',
			'plugin_path'    => __FILE__,
			'donate_link_id' => 'TLX9TH5XRURBA',
		) );

		$this->hook( 'wp_ajax_wp-search-suggest', 'ajax_response' );
		$this->hook( 'wp_ajax_nopriv_wp-search-suggest', 'ajax_response' );
		$this->hook( 'wp_ajax_wpss-post-url', 'post_url' );
		$this->hook( 'wp_ajax_nopriv_wpss-post-url', 'post_url' );
		$this->hook( 'init', 9 ); // Set to 9, so they can easily be deregistered.
		$this->hook( 'wp_enqueue_scripts' );
	}


	/**
	 * Registers the script and stylesheet.
	 *
	 * The scripts and stylesheets can easily be deregeistered be calling
	 * <code>wp_deregister_script( 'wp-search-suggest' );</code> or
	 * <code>wp_deregister_style( 'wp-search-suggest' );</code> on the init
	 * hook.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 16.04.2011
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' );
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

		wp_register_script( $this->textdomain, plugins_url( "js/wpss-search-suggest$suffix.js", __FILE__ ), array( 'suggest' ), $plugin_data['Version'], true );
		wp_localize_script( $this->textdomain, 'wpss_options', array(
			'url'     => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpss-post-url' ),
			'ajaxurl' => add_query_arg( array(
				'action'   => $this->textdomain,
				'_wpnonce' => wp_create_nonce( $this->textdomain ),
			), admin_url( 'admin-ajax.php' ) ),
		) );

		wp_register_style( $this->textdomain, plugins_url( "css/wpss-search-suggest$suffix.css", __FILE__ ), array(), $plugin_data['Version'] );
	}


	/**
	 * Enqueues the script and style.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 16.04.2011
	 * @access public
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_script( $this->textdomain );
		wp_enqueue_style(  $this->textdomain );
	}


	/**
	 * Handles the AJAX request for the search term.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 16.04.2011
	 * @access public
	 *
	 * @return void
	 */
	public function ajax_response() {
		check_ajax_referer( $this->textdomain );

		// phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated
		$s = trim( stripslashes( $_GET['q'] ) );

		$query_args = apply_filters( 'wpss_search_query_args', array(
			's'           => $s,
			'post_status' => 'publish',
		), $s );

		$query = new WP_Query( $query_args );

		if ( $query->posts ) {
			$results = apply_filters( 'wpss_search_results', wp_list_pluck( $query->posts, 'post_title' ), $query );
			echo wp_kses_post( join( "\n", $results ) );
		}

		wp_die();
	}

	/**
	 * Handles the AJAX request for a specific title.
	 *
	 * @author Konstantin Obenland
	 * @since  2.0.0 - 29.12.2013
	 * @access public
	 *
	 * @return void
	 */
	public function post_url() {
		check_ajax_referer( 'wpss-post-url' );

		// phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated
		$post = $this->get_post_id_from_title( trim( stripslashes( $_REQUEST['title'] ) ) );

		if ( $post ) {
			echo esc_url( get_permalink( $post ) );
		}

		wp_die();
	}

	/**
	 * Examines a title and tries to determine the post ID it represents.
	 *
	 * @author Konstantin Obenland
	 * @since  2 - 20.12.2017
	 * @access protected
	 *
	 * @param string $title Post title to check.
	 *
	 * @return int Post ID or 0 on failure.
	 */
	protected function get_post_id_from_title( $title ) {
		global $wpdb;

		$post_id = wp_cache_get( 'wpss_post_title' . $title, 'post' );

		if ( false === $post_id ) {
			$post_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_status = 'publish' LIMIT 1",
				$title
			) );

			wp_cache_set( 'wpss_post_title' . $title, $post_id, 'post' );
		}

		return absint( $post_id );
	}
}  // End of class Obenland_Wp_Search_Suggest


new Obenland_Wp_Search_Suggest();
