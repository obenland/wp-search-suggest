<?php
/**
 * Ajax requests test file.
 *
 * @package wp-search-suggest
 */

/**
 * User meta related tests.
 */
class Ajax_Requests extends WP_Ajax_UnitTestCase {

	/**
	 * Set up before class.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		add_action( 'wp_ajax_wp-search-suggest', 'wpss_ajax_response' );
		add_action( 'wp_ajax_nopriv_wp-search-suggest', 'wpss_ajax_response' );
	}

	/**
	 * Tests whether a logged-in user can access the AJAX request.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_logged_in_user_can_access() {
		// Simulate a logged-in user.
		$this->_setRole( 'subscriber' );

		// Create a test post with a specific title.
		$post_id = $this->factory->post->create( array( 'post_title' => 'Sample Post Title' ) );

		// Set up the request with the first word of the post title as the query.
		$_GET['q']        = 'Sample';
		$_GET['_wpnonce'] = wp_create_nonce( 'wp-search-suggest' );;

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			// We expect this exception to be thrown.
		}

		// Assert that the response contains the post title.
		$this->assertSame( 'Sample Post Title', $this->_last_response );

		// Clean up by deleting the test post.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Tests whether a logged-out user can access the AJAX request.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_logged_out_user_can_access() {
		// Simulate a logged-out user.
		$this->logout();

		// Create a test post with a specific title.
		$post_id = $this->factory->post->create( array( 'post_title' => 'Sample Post Title' ) );

		// Set up the request with the first word of the post title as the query.
		$_GET['q']        = 'Sample';
		$_GET['_wpnonce'] = wp_create_nonce( 'wp-search-suggest' );;

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			// We expect this exception to be thrown.
		}

		// Assert that the response contains the post title.
		$this->assertSame( 'Sample Post Title', $this->_last_response );

		// Clean up by deleting the test post.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Tests whether an invalid nonce is rejected.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_invalid_nonce_for_logged_in_user() {
		// Simulate a logged-in user.
		$this->_setRole( 'subscriber' );

		// Set up the request with the invalid nonce.
		$_GET['q']        = 'Title';
		$_GET['_wpnonce'] = 'invalid_nonce';

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieStopException $exception ) {
			// We expect this exception to be thrown.
		}

		// Assert that the response contains an error message.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
	}
}
