<?php
/** wp-search-suggest.php
 * 
 * Plugin Name:	WP Search Suggest
 * Plugin URI:	http://www.obenlands.de/en/portfolio/wp-search-suggest/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Description:	Provides title suggestions while typing a search query, using the built in jQuery suggest script.
 * Version:		1.1
 * Author:		Konstantin Obenland
 * Author URI:	http://www.obenlands.de/en/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-search-suggest
 * Text Domain: wp-search-suggest
 * Domain Path: /lang
 * License:		GPLv2
 */


if( ! class_exists('Obenland_Wp_Plugins') ) {
	require_once('obenland-wp-plugins.php');
}


class Obenland_Wp_Search_Suggest extends Obenland_Wp_Plugins {
	
	
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
			'plugin_name'		=>	plugin_basename(__FILE__),
			'donate_link_id'	=>	'TLX9TH5XRURBA'
		));
		
		foreach( array('wp_ajax_', 'wp_ajax_nopriv_') as $hook ) {
			add_action( $hook . $this->textdomain, array(
				&$this,
				'ajax_response'
			));
		}
		
		if ( ! is_admin() ) {
			add_filter( 'init', array(
				&$this,
				'register_scripts_styles'
			), 9); // Set to 9, so they can easily be deregistered
				
			add_filter( 'wp_print_scripts', array(
				&$this,
				'print_scripts'
			));
			
			add_filter( 'wp_print_styles', array(
				&$this,
				'print_styles'
			));
		}		
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
	public function register_scripts_styles() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		wp_register_script(
			$this->textdomain,
			plugins_url("/js/wpss-search-suggest$suffix.js", __FILE__),
			array('suggest'),
			filemtime($this->plugin_path . "js/wpss-search-suggest$suffix.js"),
			true
		);
		
		wp_localize_script(
			$this->textdomain,
			'wpss_options',
			array(
				'ajaxurl'	=>	add_query_arg(
					array(
						'action'	=>	$this->textdomain,
						'_wpnonce'	=>	wp_create_nonce( $this->textdomain )
					),
					admin_url('admin-ajax.php')
				),
			)
		);
		
		wp_register_style(
			$this->textdomain,
			plugins_url("/css/wpss-search-suggest$suffix.css", __FILE__),
			array(),
			filemtime($this->plugin_path . "css/wpss-search-suggest$suffix.css")
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
	public function print_scripts() {
		wp_enqueue_script( $this->textdomain );
	}
	
	
	/**
	 * Enqueues the stylesheet
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.0 - 16.04.2011
	 * @access	public
	 * 
	 * @return	void
	 */
	public function print_styles() {
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
		
		// jQuery can't handle escaped URLs??
		check_ajax_referer( $this->textdomain, 'amp;_wpnonce' );
	
		$s = trim(stripslashes( $_GET['q'] ));
		
		$query_args = apply_filters(
			'wpss_search_query_args',
			array(
				's'				=>	$s,
				'post_status'	=>	'publish'
			),
			$s
		);
		
		$query = new WP_Query( $query_args );
		
		if ( ! empty($query->posts) ) {
			
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