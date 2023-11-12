<?php
/**
 * Ajax requests test file.
 *
 * @package wp-search-suggest
 */

/**
 * User meta related tests.
 */
class Ajax_Requests extends WP_Ajax_UnitTestCase {\

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

		// Set up the request.
		$_GET['q']        = 'your_search_query';
		$_GET['_wpnonce'] = wp_create_nonce( 'wp-search-suggest' );;

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		// Assert that the response is not empty or contains an error message.
		$this->assertNotEmpty( $this->_last_response );
		$this->assertNotContains( 'error', $this->_last_response );
	}

	/**
	 * Tests whether a logged-out user can access the AJAX request.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_logged_out_user_can_access() {
		// Simulate a logged-out user.
		$this->logout();

		// Set up the request.
		$_GET['q']        = 'your_search_query';
		$_GET['_wpnonce'] = wp_create_nonce( 'wp-search-suggest' );;

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		// Assert that the response is not empty or contains an error message.
		$this->assertNotEmpty( $this->_last_response );
		$this->assertNotContains( 'error', $this->_last_response );
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
		$_GET['q']        = 'your_search_query';
		$_GET['_wpnonce'] = 'invalid_nonce';

		// Make the request.
		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		// Assert that the response contains an error message.
		$this->assertContains( 'error', $this->_last_response );
	}

	/**
	 * Tests the search with the first word of a post title.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_search_with_first_word_of_post_title() {
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
			unset( $exception );
		}

		// Assert that the response contains the post title.
		$this->assertContains( 'Sample Post Title', $this->_last_response );

		// Clean up by deleting the test post.
		wp_delete_post( $post_id, true );
	}
}
