<?php 
/** obenland-wp-plugins.php
 * 
 * @author		Konstantin Obenland
 * @version		1.2
 * @since		1.1
 */


class Obenland_Wp_Plugins {
	
	
	/////////////////////////////////////////////////////////////////////////////
	// PROPERTIES, PROTECTED
	/////////////////////////////////////////////////////////////////////////////
	
	/**
	 * The plugins' text domain
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.1 - 03.04.2011
	 * @access	protected
	 * @static
	 * 
	 * @var		string
	 */
	protected $textdomain;
	
	
	/**
	 * The name of the calling plugin
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.0 - 23.03.2011
	 * @access	protected
	 * @static
	 * 
	 * @var		string
	 */
	protected $plugin_name;
	
	
	/**
	 * The donate link for the plugin
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.0 - 23.03.2011
	 * @access	protected
	 * @static
	 * 
	 * @var		string
	 */
	protected $donate_link;
	
	
	/**
	 * The path to the plugin folder
	 * 
	 * /path/to/wp-content/plugins/{plugin-name}/
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.2 - 21.04.2011
	 * @access	protected
	 * @static
	 * 
	 * @var		string
	 */
	protected $plugin_path;
	
	
	/**
	 * Holds all admin notices produced by plugins
	 *
	 * Expected values: array(
	 * 	'class'	=>	'error' OR 'updated',
	 * 	'text'	=>	__('The message')
	 * )
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 21.04.2011
	 * @access	protected
	 * @static
	 *
	 * @var		array
	 */
	protected $admin_notices	=	array();
	
	
	///////////////////////////////////////////////////////////////////////////
	// METHODS, PUBLIC
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Constructor
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.0 - 23.03.2011
	 * @access	public
	 * 
	 * @param	string	$plugin_name
	 * @param	string	$donate_link_id
	 */
	public function __construct( $args = array() ) {
		$this->textdomain	=	$args['textdomain'];
		$this->plugin_name	=	$args['plugin_name'];
		$this->set_donate_link( $args['donate_link_id'] );
		$this->plugin_path	=	trailingslashit( WP_PLUGIN_DIR )
							.	str_replace(
									basename($this->plugin_name),
									"",
									$this->plugin_name
								);

		add_action( 'plugin_row_meta', array(
			&$this,
			'plugin_meta_donate'
		), 10, 2 );
	}
	
	/**
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.0 - 23.03.2011
	 * @access	public
	 * 
	 * @param	array	$plugin_meta
	 * @param	string	$plugin_file
	 * 
	 * @return	string
	 */
	public function plugin_meta_donate( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_name == $plugin_file ) {
			$plugin_meta[]	=	sprintf('
				<a href="%1$s" target="_blank" title="%2$s">%2$s</a>',
				$this->donate_link,
				__('Donate', $this->textdomain)
			);
		}
		return $plugin_meta;
	}
	
	/**
	 * Displays all error and update messages
	 * 
	 * Helper function for displaying errors and notices on the admin screen.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 21.04.2011
	 * @access	public
	 *
	 * @return	void
	 */
	public function show_admin_notices() {
		 echo $this->admin_notices();
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	// METHODS, PROTECTED
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Sets the donate link
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.1 - 03.04.2011
	 * @access	public
	 * 
	 * @param	string	$donate_link_id
	 */
	protected function set_donate_link( $donate_link_id ) {
		$this->donate_link	=	add_query_arg( array(
			'cmd'				=>	'_s-xclick',
			'hosted_button_id'	=>	$donate_link_id
		), 'https://www.paypal.com/cgi-bin/webscr' );
	}
	
	
	/**
	 * Returns a string with all messages
	 * 
	 * Helper function for displaying errors and notices on the admin screen.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 21.04.2011
	 * @access	protected
	 *
	 * @return	string
	 */
	protected function get_admin_notices() {
		 
		$message = '';
		
		if( ! empty($this->admin_notices) ) {
			foreach( $this->admin_notices as $notice ) {
				$message	.=	'<div class="' . $notice['class'] . ' fade">'	. "\n"
							.	'	<p>' . $notice['text'] . '</p>'				. "\n"
							.	'</div>'										. "\n";
			}
		}
		
		return $message;
	}
	
	
	/**
	 * Sets an admin notice
	 * 
	 * @author	Konstantin Obenland
	 * @since	1.2 - 21.04.2011
	 * @access	protected
	 * 
	 * @param	string	$class
	 * @param	string	$notice
	 * 
	 * @return	void
	 */
	protected function set_admin_notice( string $class, string $notice ) {
		$this->admin_notices[] = array(
			'class'	=>	$class,
			'text'	=>	$notice
		);
	}
} // End of class Obenland_Wp_Plugins


/* End of file obenland-wp-plugins.php */
/* Location: ./wp-content/plugins/{obenland-plugin}/obenland-wp-plugins.php */