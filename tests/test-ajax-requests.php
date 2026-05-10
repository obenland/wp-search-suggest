<?php // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
/**
 * Tests for the two AJAX endpoints exposed by the plugin.
 *
 * @package wp-search-suggest
 */

/**
 * Covers wp_ajax(_nopriv)_wp-search-suggest and wp_ajax(_nopriv)_wpss-post-url.
 */
class Ajax_Requests extends WP_Ajax_UnitTestCase {

	/**
	 * Suggest endpoint returns the matching post title for a logged-in user.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_suggest_returns_matching_post_title_for_logged_in_user() {
		$this->_setRole( 'subscriber' );
		$this->assert_suggest_returns_title();
	}

	/**
	 * Suggest endpoint returns the matching post title for a logged-out user.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_suggest_returns_matching_post_title_for_logged_out_user() {
		$this->logout();
		$this->assert_suggest_returns_title();
	}

	/**
	 * Suggest endpoint rejects an invalid nonce when logged in.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_suggest_rejects_invalid_nonce_when_logged_in() {
		$this->_setRole( 'subscriber' );
		$this->assert_suggest_rejects_invalid_nonce();
	}

	/**
	 * Suggest endpoint rejects an invalid nonce when logged out.
	 *
	 * The wp_ajax_nopriv_* hook runs the same nonce check; this guards against the
	 * nopriv branch silently accepting unauthenticated requests.
	 *
	 * @covers ::wpss_ajax_response
	 */
	public function test_suggest_rejects_invalid_nonce_when_logged_out() {
		$this->logout();
		$this->assert_suggest_rejects_invalid_nonce();
	}

	/**
	 * Post URL endpoint returns the permalink for a published post matching the title.
	 *
	 * @covers ::wpss_post_url
	 */
	public function test_post_url_returns_permalink_for_matching_title() {
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Sample Post Title',
				'post_status' => 'publish',
			)
		);

		$_GET['title']    = 'Sample Post Title';
		$_GET['_wpnonce'] = wp_create_nonce( 'wpss-post-url' );

		try {
			$this->_handleAjax( 'wpss-post-url' );
		} catch ( WPAjaxDieContinueException $exception ) {
			// Expected: wp_die() at end of handler.
		}

		$this->assertSame( get_permalink( $post_id ), $this->_last_response );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Post URL endpoint returns an empty response when no post matches the title.
	 *
	 * @covers ::wpss_post_url
	 */
	public function test_post_url_returns_empty_response_when_no_post_matches() {
		$_GET['title']    = 'No Such Title Exists';
		$_GET['_wpnonce'] = wp_create_nonce( 'wpss-post-url' );

		try {
			$this->_handleAjax( 'wpss-post-url' );
		} catch ( WPAjaxDieStopException $exception ) {
			/*
			 * Expected: wp_die() at end of handler. WP_Ajax_UnitTestCase throws
			 * the Stop variant when nothing was echoed (here, because the title
			 * matches no post and the esc_url(...) branch is skipped).
			 */
		}

		$this->assertSame( '', $this->_last_response );
	}

	/**
	 * Post URL endpoint rejects an invalid nonce.
	 *
	 * The plugin uses TWO distinct nonces (`wp-search-suggest` for suggest,
	 * `wpss-post-url` for the URL resolver). This test guards the second one
	 * — collapsing the two would make this assertion still pass under suggest
	 * but break the URL endpoint in production.
	 *
	 * @covers ::wpss_post_url
	 */
	public function test_post_url_rejects_invalid_nonce() {
		$_GET['title']    = 'Sample Post Title';
		$_GET['_wpnonce'] = 'invalid_nonce';

		try {
			$this->_handleAjax( 'wpss-post-url' );
		} catch ( WPAjaxDieStopException $exception ) {
			// Expected: check_ajax_referer() dies with -1 on failure.
		}

		$this->assertEmpty( $this->_last_response );
	}

	/**
	 * Asserts the suggest endpoint returns the matching post title for the current request state.
	 */
	private function assert_suggest_returns_title() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Sample Post Title' ) );

		$_GET['q']        = 'Sample';
		$_GET['_wpnonce'] = wp_create_nonce( 'wp-search-suggest' );

		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieContinueException $exception ) {
			// Expected: wp_die() at end of handler.
		}

		$this->assertSame( 'Sample Post Title', $this->_last_response );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Asserts the suggest endpoint rejects an invalid nonce for the current request state.
	 */
	private function assert_suggest_rejects_invalid_nonce() {
		$_GET['q']        = 'Title';
		$_GET['_wpnonce'] = 'invalid_nonce';

		try {
			$this->_handleAjax( 'wp-search-suggest' );
		} catch ( WPAjaxDieStopException $exception ) {
			// Expected: check_ajax_referer() dies with -1 on failure.
		}

		$this->assertEmpty( $this->_last_response );
	}
}
