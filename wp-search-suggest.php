<?php
/** wp-search-suggest.php
 *
 * Plugin Name:	WP Search Suggest
 * Plugin URI:	http://en.obenland.it/wp-search-suggest/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Description:	Provides title suggestions while typing a search query, using the built in jQuery suggest script.
 * Version:		1.3.1
 * Author:		Konstantin Obenland
 * Author URI:	http://en.obenland.it/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Text Domain: wp-search-suggest
 * Domain Path: /lang
 * License:		GPLv2
 */


if ( ! class_exists('Obenland_Wp_Plugins_v200') ) {
	require_once( 'obenland-wp-plugins.php' );
}


class Obenland_Wp_Search_Suggest extends Obenland_Wp_Plugins_v200 {
	
	
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
			'textdomain'		=>	'wp-search-suggest',
			'plugin_path'		=>	__FILE__,
			'donate_link_id'	=>	'TLX9TH5XRURBA'
		));

		$this->hook( 'wp_ajax_wp-search-suggest',			'ajax_response' );
		$this->hook( 'wp_ajax_nopriv_wp-search-suggest',	'ajax_response' );
		$this->hook( 'init', 9 ); // Set to 9, so they can easily be deregistered
		$this->hook( 'wp_enqueue_scripts' );
	}
	
	
	/**
	 * Registers the script and stylesheet
	 *
	 * The scripts and stylesheets can easilz be deregeistered be calling
	 * <code>wp_deregister_script( 'wp-search-suggest' );</code> or
	 * <code>wp_deregister_style( 'wp-search-suggest' );</code> on the init
	 * hook
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 16.04.2011
	 * @access	public
	 *
	 * @return	void
	 */
	public function init() {
		$plugin_data = get_file_data( __FILE__, array('Version' => 'Version'), 'plugin' );
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		wp_register_script(
			$this->textdomain,
			plugins_url("/js/wpss-search-suggest$suffix.js", __FILE__),
			array('suggest'),
			$plugin_data['Version'],
			true
		);
		
		wp_localize_script(
			$this->textdomain,
			'wpss_options',
			array( 'ajaxurl'	=>	add_query_arg( array(
				'action'	=>	$this->textdomain,
				'_wpnonce'	=>	wp_create_nonce( $this->textdomain )
			), admin_url('admin-ajax.php') ), )
		);
		
		wp_register_style(
			$this->textdomain,
			plugins_url("/css/wpss-search-suggest$suffix.css", __FILE__),
			array(),
			$plugin_data['Version']
		);
	}
	
	
	/**
	 * Enqueues the script
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 16.04.2011
	 * @access	public
	 *
	 * @return	void
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_script( $this->textdomain );
		wp_enqueue_style( $this->textdomain );
	}
	
	
	/**
	 * Handles the AJAX request
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 16.04.2011
	 * @access	public
	 *
	 * @return	void
	 */
	public function ajax_response() {
		
		// jQuery can't handle escaped URLs until v1.7
		$amp	=	version_compare(get_bloginfo('version'), '3.2.1', '<=') ? 'amp;' : '';
		check_ajax_referer( $this->textdomain, "{$amp}_wpnonce" );
	
		$s = trim( stripslashes( $_GET['q'] ) );
		
		$query_args = apply_filters(
			'wpss_search_query_args',
			array(
				's'				=>	$s,
				'post_status'	=>	'publish'
			),
			$s
		);
		
		$query = new WP_Query( $query_args );
		
		if ( $query->posts ) {
			
			foreach ( $query->posts as $post ) {
				$results[] = $post->post_title;
			}
			
			$results = apply_filters(
				'wpss_search_results',
				$results,
				$query
			);
			
			echo join( $results, "\n" );
		}
		
		die;
	}

}  // End of class Obenland_Wp_Search_Suggest


new Obenland_Wp_Search_Suggest;


/* End of file wp-search-suggest.php */
/* Location: ./wp-content/plugins/wp-search-suggest/wp-search-suggest.php */