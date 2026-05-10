<?php
/**
 * WP Search Suggest tests.
 *
 * Covers the registration helpers (wpss_init, wpss_enqueue_scripts) and the
 * post-URL lookup helpers. Ajax handler tests are in test-ajax-requests.php.
 *
 * @package wp-search-suggest
 */

/**
 * Tests for the registration / enqueue / lookup helpers.
 */
class Test_WP_Search_Suggest extends WP_UnitTestCase {

	/**
	 * `wpss_init` registers the script + style with the expected handles +
	 * dependencies. Asserted by checking the global registry rather than UI.
	 */
	public function test_init_registers_script_and_style() {
		// Re-run init to ensure we exercise the registration code rather than
		// relying on whatever ran during bootstrap (which may have happened
		// before this test class loaded).
		wpss_init();

		global $wp_scripts, $wp_styles;
		$this->assertTrue( isset( $wp_scripts->registered['wp-search-suggest'] ) );
		$this->assertContains( 'suggest', $wp_scripts->registered['wp-search-suggest']->deps );

		$this->assertTrue( isset( $wp_styles->registered['wp-search-suggest'] ) );
	}

	/**
	 * `wpss_init` localises a `wpss_options` global with the AJAX URL +
	 * nonce(s) the front-end script uses.
	 */
	public function test_init_localises_ajax_options() {
		wpss_init();

		global $wp_scripts;
		$data = $wp_scripts->get_data( 'wp-search-suggest', 'data' );

		$this->assertNotFalse( $data, 'Expected wpss_options to be localised onto the script.' );
		$this->assertStringContainsString( 'wpss_options', (string) $data );
		$this->assertStringContainsString( 'admin-ajax.php', (string) $data );
		$this->assertStringContainsString( 'wp-search-suggest', (string) $data, 'Expected the wp-search-suggest action in the URL.' );
	}

	/**
	 * `wpss_enqueue_scripts` enqueues both the JS and CSS handles registered
	 * by `wpss_init`.
	 */
	public function test_enqueue_scripts_enqueues_both_assets() {
		wpss_init();
		wpss_enqueue_scripts();

		$this->assertTrue( wp_script_is( 'wp-search-suggest', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-search-suggest', 'enqueued' ) );
	}

	/**
	 * `wpss_get_post_id_from_title` resolves a published post by title.
	 */
	public function test_get_post_id_from_title_resolves_published_post() {
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Resolvable Title ' . wp_generate_uuid4(),
				'post_status' => 'publish',
			)
		);

		$resolved = wpss_get_post_id_from_title( get_post( $post_id )->post_title );

		$this->assertSame( $post_id, $resolved );
	}

	/**
	 * `wpss_get_post_id_from_title` returns 0 when no published post matches.
	 */
	public function test_get_post_id_from_title_returns_zero_for_missing() {
		$this->assertSame( 0, wpss_get_post_id_from_title( 'Definitely Not A Title ' . wp_generate_uuid4() ) );
	}

	/**
	 * `wpss_get_post_id_from_title` ignores draft / non-public posts —
	 * the SQL filters on post_status = 'publish'.
	 */
	public function test_get_post_id_from_title_ignores_drafts() {
		$title = 'Draft Title ' . wp_generate_uuid4();
		self::factory()->post->create(
			array(
				'post_title'  => $title,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( 0, wpss_get_post_id_from_title( $title ) );
	}

	/**
	 * `wpss_get_post_id_from_title` caches its result so a second call for the
	 * same title doesn't hit the database.
	 */
	public function test_get_post_id_from_title_caches_result() {
		$title   = 'Cached Title ' . wp_generate_uuid4();
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
			)
		);

		// First call populates the cache.
		wpss_get_post_id_from_title( $title );

		$this->assertSame(
			(string) $post_id,
			(string) wp_cache_get( 'wpss_post_title' . $title, 'post' ),
			'Expected the post ID to be cached under wpss_post_title<title>.'
		);
	}
}
