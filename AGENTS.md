# AGENTS.md

This file provides guidance to coding agents (e.g. Claude Code at claude.ai/code) when working with code in this repository.

## Project

WP Search Suggest is a WordPress.org plugin that adds AJAX title autocomplete to a theme's search field (`#s` / `[name="s"]`) using the built-in `suggest` jQuery script. The plugin is single-file PHP plus one JS and one CSS asset. Public name on WordPress.org: `wp-search-suggest`.

## Commands

```bash
# Install dev deps (PHPUnit, Brain Monkey, WPCS, etc.)
composer install

# One-time: install the WP test suite locally (DB required)
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]

# Run PHPUnit (uses tests/bootstrap.php; requires WP test suite installed)
composer test
# or directly:
vendor/bin/phpunit --config=phpunit.xml

# Run a single test
vendor/bin/phpunit --config=phpunit.xml --filter test_logged_in_user_can_access

# Multisite run (CI runs both modes)
WP_MULTISITE=1 vendor/bin/phpunit --config=phpunit.xml

# Lint (WordPress Coding Standards)
vendor/bin/phpcs --standard=WordPress --extensions=php --ignore="node_modules,vendor" .
vendor/bin/phpcbf --standard=WordPress --extensions=php --ignore="node_modules,vendor" .
```

CI runs PHPUnit against PHP 7.4 / latest WordPress and PHPCS against the `WordPress` standard on every push.

## Architecture

**Single-file plugin.** All PHP lives in `wp-search-suggest.php`; the `composer.json` `psr-4` mapping to `./php` and `WordPressPlugin\` is unused — there is no class-based code.

**Request flow:**
1. `wpss_init` (priority 9 on `init`) registers the script + style and calls `wp_localize_script` to expose `wpss_options` (two AJAX URLs and two distinct nonces) to the front end.
2. `wpss_enqueue_scripts` (on `wp_enqueue_scripts`) actually enqueues them on the front end.
3. The frontend JS (`js/wpss-search-suggest.dev.js`) attaches jQuery `suggest` to `#s, [name="s"]` and points it at `admin-ajax.php?action=wp-search-suggest` for typeahead matches.
4. On selection, the JS POSTs to `admin-ajax.php` with `action=wpss-post-url` to resolve the chosen title to a permalink and redirects there; on failure it falls back to a normal form submit.

**Two AJAX endpoints** (both registered for `wp_ajax_*` and `wp_ajax_nopriv_*`):
- `wp-search-suggest` → `wpss_ajax_response()`: runs `WP_Query` against the typed string; output is filtered through `wpss_search_query_args` and `wpss_search_results` (public extension points — see `readme.txt`).
- `wpss-post-url` → `wpss_post_url()`: looks up a post by exact title via `wpss_get_post_id_from_title()` (direct `$wpdb` query cached in object cache under `wpss_post_title{$title}` in the `post` group) and echoes the permalink.

**Asset variants.** Both `js/` and `css/` ship a production file (`wpss-search-suggest.js` / `.css`) and a `.dev.*` counterpart. `wpss_init` picks the `.dev` variant when `SCRIPT_DEBUG` is true. Asset versions are read from the plugin header via `get_file_data( __FILE__, ... 'Version' ... )` — bumping the `Version:` header in `wp-search-suggest.php` is what busts cached assets.

**Tests.** `tests/test-ajax-requests.php` extends `WP_Ajax_UnitTestCase` and exercises the AJAX endpoints with valid/invalid nonces and logged-in/out roles. `tests/bootstrap.php` reads `WP_TESTS_DIR` (or falls back to a tempdir) and manually loads the plugin on `muplugins_loaded`.

**Release / deploy.** Pushing a git tag triggers `.github/workflows/deploy.yml`, which uses `10up/action-wordpress-plugin-deploy` to sync to the WordPress.org SVN repo. `update-tested-up-to.yml` and `push-asset-readme-update.yml` keep `Tested up to` and the `.wordpress-org/` assets in sync with SVN. `.distignore` controls what is excluded from the SVN deploy. Bumping a release means updating both the `Version:` header in `wp-search-suggest.php` and `Stable tag:` + changelog in `readme.txt`, then tagging.

## Conventions

- Keep changes minimal and within the existing single-file style — do not introduce classes or autoloading without a reason.
- The `Version:` header is the source of truth for both plugin version and registered asset version; keep it in sync with `Stable tag:` in `readme.txt`.
- When editing JS/CSS, update both the `.dev` and the production (minified) variant.
- Two distinct nonces are used (`wp-search-suggest` for the suggest endpoint, `wpss-post-url` for the URL resolver); don't conflate them.
