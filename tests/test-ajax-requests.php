<?php
/**
 * Ajax requests test file.
 *
 * @package wp-search-suggest
 */

/**
 * User meta related tests.
 */
class Ajax_Requests extends WP_UnitTestCase {

	/**
	 * Subscriber user object.
	 *
	 * @var WP_User
	 */
	public static $user;

	/**
	 * Setup before class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		static::$user = $factory->user->create_and_get( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Tests whether a logged-in user can access the AJAX request.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_logged_in_user_can_access() {
		// Simulate a logged-in user.
		wp_set_current_user( static::$user );

		// Create a nonce for the logged-in user.
		$nonce = wp_create_nonce( 'wp-search-suggest' );

		// Set up the request.
		$_GET['q'] = 'your_search_query';
		$_GET['_wpnonce'] = $nonce;

		// Call the tested function.
		ob_start();
		wpss_ajax_response();
		$output = ob_get_clean();

		// Assert that the response is not empty or contains an error message.
		$this->assertNotEmpty( $output );
		$this->assertNotContains( 'error', $output );
	}

	/**
	 * Tests whether a logged-out user can access the AJAX request.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_logged_out_user_can_access() {
		// Simulate a logged-out user.
		wp_set_current_user( 0 );

		// Create a nonce for the logged-out user.
		$nonce = wp_create_nonce( 'wp-search-suggest' );

		// Set up the request.
		$_GET['q'] = 'your_search_query';
		$_GET['_wpnonce'] = $nonce;

		// Call the tested function.
		ob_start();
		wpss_ajax_response();
		$output = ob_get_clean();

		// Assert that the response is not empty or contains an error message.
		$this->assertNotEmpty( $output );
		$this->assertNotContains( 'error', $output );
	}

	/**
	 * Tests whether an invalid nonce is rejected.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_invalid_nonce_for_logged_in_user() {
		// Simulate a logged-in user.
		wp_set_current_user( static::$user );

		// Create an invalid nonce.
		$nonce = 'invalid_nonce';

		// Set up the request with the invalid nonce.
		$_GET['q'] = 'your_search_query';
		$_GET['_wpnonce'] = $nonce;

		// Call the tested function.
		ob_start();
		wpss_ajax_response();
		$output = ob_get_clean();

		// Assert that the response contains an error message.
		$this->assertContains( 'error', $output );
	}

	/**
	 * Tests the search with the first word of a post title.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_search_with_first_word_of_post_title() {
		// Simulate a logged-out user.
		wp_set_current_user( 0 );

		// Create a nonce for the logged-out user.
		$nonce = wp_create_nonce( 'wp-search-suggest' );

		// Create a test post with a specific title.
		$post_id = $this->factory->post->create( array( 'post_title' => 'Sample Post Title' ) );

		// Set up the request with the first word of the post title as the query.
		$_GET['q'] = 'Sample';
		$_GET['_wpnonce'] = $nonce;

		// Call the tested function.
		ob_start();
		wpss_ajax_response();
		$output = ob_get_clean();

		// Assert that the response contains the post title.
		$this->assertContains( 'Sample Post Title', $output );

		// Clean up by deleting the test post.
		wp_delete_post( $post_id, true );
	}
}
