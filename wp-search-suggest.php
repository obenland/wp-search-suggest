<?php
/** wp-search-suggest.php
 *
 * Plugin Name: WP Search Suggest
 * Plugin URI:  http://en.obenland.it/wp-search-suggest/#utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Description: Provides title suggestions while typing a search query, using the built in jQuery suggest script.
 * Version:     2.0.1
 * Author:      Konstantin Obenland
 * Author URI:  http://en.obenland.it/#utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Text Domain: wp-search-suggest
 * Domain Path: /lang
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! class_exists( 'Obenland_Wp_Plugins_v301' ) ) {
	require_once( 'obenland-wp-plugins.php' );
}


class Obenland_Wp_Search_Suggest extends Obenland_Wp_Plugins_v301 {


	///////////////////////////////////////////////////////////////////////////
	// METHODS, PUBLIC
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Constructor
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 16.04.2011
	 * @access	public
	 *
	 * @return	Obenland_Wp_Search_Suggest
	 */
	public function __construct() {

		parent::__construct( array(
			'textdomain'     => 'wp-search-suggest',
			'plugin_path'    => __FILE__,
			'donate_link_id' => 'TLX9TH5XRURBA',
		) );

		$this->hook( 'wp_ajax_wp-search-suggest',        'ajax_response' );
		$this->hook( 'wp_ajax_nopriv_wp-search-suggest', 'ajax_response' );
		$this->hook( 'wp_ajax_wpss-post-url',            'post_url' );
		$this->hook( 'wp_ajax_nopriv_wpss-post-url',     'post_url' );
		$this->hook( 'init', 9 ); // Set to 9, so they can easily be deregistered
		$this->hook( 'wp_enqueue_scripts' );
	}


	/**
	 * Registers the script and stylesheet
	 *
	 * The scripts and stylesheets can easily be deregeistered be calling
	 * <code>wp_deregister_script( 'wp-search-suggest' );</code> or
	 * <code>wp_deregister_style( 'wp-search-suggest' );</code> on the init
	 * hook
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
				'_wpnonce' => wp_create_nonce( $this->textdomain )
			), admin_url( 'admin-ajax.php' ) ),
		) );

		wp_register_style( $this->textdomain, plugins_url( "css/wpss-search-suggest$suffix.css", __FILE__ ), array(), $plugin_data['Version'] );
	}


	/**
	 * Enqueues the script and style
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 16.04.2011
	 * @access public
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_script( $this->textdomain );
		wp_enqueue_style( $this->textdomain );
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

		$s = trim( stripslashes( $_GET['q'] ) );

		$query_args = apply_filters( 'wpss_search_query_args', array(
			's'           => $s,
			'post_status' => 'publish',
		), $s );

		$query = new WP_Query( $query_args );

		if ( $query->posts ) {
			$results = apply_filters( 'wpss_search_results', wp_list_pluck( $query->posts, 'post_title' ), $query );
			echo join( $results, "\n" );
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

		global $wpdb;
		$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s LIMIT 1", trim( stripslashes($_REQUEST['title'] ) ) ) );

		if ( $post ) {
			echo get_permalink( $post );
		}

		wp_die();
	}
}  // End of class Obenland_Wp_Search_Suggest


new Obenland_Wp_Search_Suggest;


/* End of file wp-search-suggest.php */
/* Location: ./wp-content/plugins/wp-search-suggest/wp-search-suggest.php */