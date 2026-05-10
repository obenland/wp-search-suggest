# AGENTS.md

This file provides guidance to coding agents (e.g. Claude Code at claude.ai/code) when working with code in this repository.

## Project

WP Search Suggest is a WordPress.org plugin that adds AJAX title autocomplete to a theme's search field (`#s` / `[name="s"]`) using the built-in `suggest` jQuery script. The plugin is single-file PHP plus one JS and one CSS asset. Public name on WordPress.org: `wp-search-suggest`.

## Commands

The test harness runs through [`@wordpress/env`](https://www.npmjs.com/package/@wordpress/env) (Docker). Node version is pinned in `.nvmrc`; PHP/WordPress versions are controlled by `.wp-env.json` and the matrix in `.github/workflows/phpunit.yml`.

```bash
# One-time setup
composer install
npm ci

# Boot the test WordPress environment (Docker)
npm run start

# Run PHPUnit (single-site / multisite)
npm test                # alias for npm run test-php
npm run test-php
npm run test-php-multisite

# Lint (WordPress Coding Standards via WPCS 3.x)
composer lint
composer lint:fix
```

`npm run test-php` shells into the wp-env `tests-cli` container and runs `composer test` (`phpunit -c phpunit.xml --verbose`); `composer test` directly is meaningful only inside that container, since the harness expects the WP test suite available there.

CI matrix: PHP 7.4 + 8.4 against WP latest, both single-site and multisite, on every PR/push to `trunk` (path-filtered to PHP/composer/test config changes).

## Architecture

**Single-file plugin.** All PHP lives in `wp-search-suggest.php` — no classes, no autoloading.

**Request flow:**
1. `wpss_init` (priority 9 on `init`) registers the script + style and calls `wp_localize_script` to expose `wpss_options` (two AJAX URLs and two distinct nonces) to the front end.
2. `wpss_enqueue_scripts` (on `wp_enqueue_scripts`) actually enqueues them on the front end.
3. The frontend JS (`js/wpss-search-suggest.dev.js`) attaches jQuery `suggest` to `#s, [name="s"]` and points it at `admin-ajax.php?action=wp-search-suggest` for typeahead matches.
4. On selection, the JS POSTs to `admin-ajax.php` with `action=wpss-post-url` to resolve the chosen title to a permalink and redirects there; on failure it falls back to a normal form submit.

**Two AJAX endpoints** (both registered for `wp_ajax_*` and `wp_ajax_nopriv_*`):
- `wp-search-suggest` → `wpss_ajax_response()`: runs `WP_Query` against the typed string; output is filtered through `wpss_search_query_args` and `wpss_search_results` (public extension points — see `readme.txt`).
- `wpss-post-url` → `wpss_post_url()`: looks up a post by exact title via `wpss_get_post_id_from_title()` (direct `$wpdb` query cached in object cache under `wpss_post_title{$title}` in the `post` group) and echoes the permalink.

**Asset variants.** Both `js/` and `css/` ship a production file (`wpss-search-suggest.js` / `.css`) and a `.dev.*` counterpart. `wpss_init` picks the `.dev` variant when `SCRIPT_DEBUG` is true. Asset versions are read from the plugin header via `get_file_data( __FILE__, ... 'Version' ... )` — bumping the `Version:` header in `wp-search-suggest.php` is what busts cached assets.

**Tests.** Two suites under `tests/`:
- `test-ajax-requests.php` (extends `WP_Ajax_UnitTestCase`) exercises the `wp-search-suggest` AJAX endpoint with valid/invalid nonces and logged-in/out roles.
- `test-wp-search-suggest.php` (extends `WP_UnitTestCase`) covers the registration helpers (`wpss_init` script + style + `wpss_options` localisation, `wpss_enqueue_scripts`) and the title→post-ID lookup (`wpss_get_post_id_from_title`, including the `post_status = 'publish'` filter and the object-cache key).
- The `wpss_post_url` AJAX wrapper itself isn't directly tested, but its core lookup is covered by the helper tests.

`tests/bootstrap.php` is wp-env-aware: it loads the plugin on `muplugins_loaded` against the WP test suite that wp-env mounts inside the `tests-cli` container.

**Release / deploy.** Pushing a git tag triggers `.github/workflows/deploy.yml`, which uses `10up/action-wordpress-plugin-deploy` to sync to the WordPress.org SVN repo. `update-tested-up-to.yml` runs the full PHPUnit suite (single-site + multisite) against latest WP in a `validate` job, and only proceeds to bump `Tested up to:` if those pass. `push-asset-readme-update.yml` keeps `.wordpress-org/` assets in sync with SVN. `.distignore` controls what is excluded from the SVN deploy. Bumping a release means updating both the `Version:` header in `wp-search-suggest.php` and `Stable tag:` + changelog in `readme.txt`, then tagging.

## Conventions

- Keep changes minimal and within the existing single-file style — do not introduce classes or autoloading without a reason.
- The `Version:` header is the source of truth for both plugin version and registered asset version; keep it in sync with `Stable tag:` in `readme.txt`.
- When editing JS/CSS, update both the `.dev` and the production (minified) variant.
- Two distinct nonces are used (`wp-search-suggest` for the suggest endpoint, `wpss-post-url` for the URL resolver); don't conflate them.
