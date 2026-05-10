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
	 * `wpss_init` localises two distinct nonces — one per AJAX endpoint.
	 *
	 * The plugin uses `wpss-post-url` for the URL resolver and `wp-search-suggest`
	 * for the suggest endpoint. Conflating the two would leave one endpoint
	 * accepting nonces minted for the other; this test fails if a future change
	 * collapses them.
	 */
	public function test_init_localises_two_distinct_nonces() {
		wpss_init();

		$options = $this->get_localised_wpss_options();

		$this->assertArrayHasKey( 'nonce', $options, 'Expected a wpss-post-url nonce on wpss_options.nonce.' );
		$this->assertArrayHasKey( 'ajaxurl', $options, 'Expected wpss_options.ajaxurl to be localised.' );

		$ajax_args = array();
		wp_parse_str( (string) wp_parse_url( $options['ajaxurl'], PHP_URL_QUERY ), $ajax_args );

		$post_url_nonce = $options['nonce'];
		$suggest_nonce  = isset( $ajax_args['_wpnonce'] ) ? $ajax_args['_wpnonce'] : '';

		$this->assertNotEmpty( $suggest_nonce, 'Expected a wp-search-suggest nonce inside wpss_options.ajaxurl.' );
		$this->assertNotSame( $post_url_nonce, $suggest_nonce, 'The two endpoints must use distinct nonces.' );
		$this->assertNotFalse( wp_verify_nonce( $post_url_nonce, 'wpss-post-url' ), 'wpss_options.nonce must verify against wpss-post-url.' );
		$this->assertNotFalse( wp_verify_nonce( $suggest_nonce, 'wp-search-suggest' ), 'wpss_options.ajaxurl nonce must verify against wp-search-suggest.' );
	}

	/**
	 * `wpss_init` registers both assets with the version from the plugin header.
	 *
	 * Pinning this contract guarantees that a release that bumps the `Version:`
	 * header actually busts cached asset URLs.
	 */
	public function test_init_uses_plugin_header_version_for_assets() {
		wpss_init();

		$plugin_data = get_file_data(
			dirname( __DIR__ ) . '/wp-search-suggest.php',
			array( 'Version' => 'Version' ),
			'plugin'
		);

		$this->assertSame( $plugin_data['Version'], wp_scripts()->registered['wp-search-suggest']->ver );
		$this->assertSame( $plugin_data['Version'], wp_styles()->registered['wp-search-suggest']->ver );
	}

	/**
	 * `wpss_init` swaps the `.dev` asset suffix in iff SCRIPT_DEBUG is enabled.
	 *
	 * Reads the current SCRIPT_DEBUG state rather than toggling it (the constant
	 * cannot be redefined mid-process); this asserts that registration honours
	 * whichever state the runtime is in.
	 */
	public function test_init_selects_dev_asset_suffix_based_on_script_debug() {
		wpss_init();

		$expected_js  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'wpss-search-suggest.dev.js' : 'wpss-search-suggest.js';
		$expected_css = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'wpss-search-suggest.dev.css' : 'wpss-search-suggest.css';

		$this->assertStringEndsWith( $expected_js, wp_scripts()->registered['wp-search-suggest']->src );
		$this->assertStringEndsWith( $expected_css, wp_styles()->registered['wp-search-suggest']->src );
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

	/**
	 * Decodes the JSON object that `wp_localize_script` emits for `wpss_options`.
	 *
	 * Reads the `data` extra (`var wpss_options = {...};`), extracts the first
	 * JSON object literal, and returns the decoded array — robust against
	 * future WordPress changes to whitespace or property ordering inside the
	 * assignment.
	 *
	 * Note: `wp_localize_script` *appends* to the data extra, so when the
	 * `init` hook has already fired before a test calls `wpss_init()`, the
	 * data string contains two `var wpss_options = {...};` statements with
	 * identical payloads. The regex below is intentionally non-greedy so it
	 * captures only the first object instead of spanning across both.
	 *
	 * @return array Decoded wpss_options payload.
	 */
	private function get_localised_wpss_options() {
		$data = (string) wp_scripts()->get_data( 'wp-search-suggest', 'data' );

		$this->assertNotEmpty( $data, 'Expected wpss_options to be localised onto the script.' );
		$this->assertSame(
			1,
			preg_match( '/wpss_options\s*=\s*(\{.*?\});/s', $data, $matches ),
			'Could not locate the wpss_options assignment in localised data.'
		);

		$decoded = json_decode( $matches[1], true );
		$this->assertIsArray( $decoded, 'wpss_options payload was not valid JSON.' );

		return $decoded;
	}
}
