=== WP Search Suggest ===
Contributors: obenland
Tags: search, AJAX, jQuery
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=TLX9TH5XRURBA
Requires at least: 3.3
Tested up to: 6.1
Stable tag: 6

Provides title suggestions while typing a search query, using the built in jQuery suggest script.

== Description ==

This plugin lets you provide the user with search suggestions based on the information entered in the search field.

It adds an AJAX call to the search form, returning matches for the current search query from the database.
There is no change of template files necessary as this plugin hooks in the existing WordPress API to unfold its magic.

= Translations =
I will be more than happy to update the plugin with new locales, as soon as I receive them!
Currently available in:

* English
* Deutsch
* Czech


== Installation ==

1. Download WP Search Suggest.
2. Unzip the folder into the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= The plugin doesn't work with my theme. What's wrong? =
Make sure your theme's search input field has an `id="s"` and/or `name="s"` as in TwentyTen and TwentyEleven. It'll work fine then.


== Screenshots ==

1. The suggested post and page titles.


== Filter Reference ==

**wpss_search_query_args** (*array|string*)
> The query args, passed to [WP_Query](http://codex.wordpress.org/Function_Reference/WP_Query "WP_Query in the WordPress Codex"), either as an array or a string.
> An array with the default query args and the current search query are passed to the filter.

**wpss_search_results** (*array*)
> An array with the result strings as values. An array with the default results and the WP_Query object are passed to the filter.


== Changelog ==

= 6 =
* Bumped z-index of search result box to be compatible with Boldwp theme.

= 5 =
* Fixed a bug where suggested selections could end up redirecting to the wrong version of a post.

= 4 =
* Finally adds support for HTML5 search forms.

= 3 =
* Maintenance release.
* Updated code to adhere to WordPress Coding Standards.
* Tested for WordPress 5.0

= 2.1.0 =
* Maintenance release.
* Tested for WordPress 4.0

= 2.0.1 =
* Fixed a bug with how scripts and styles were enqueued.

= 2.0.0 =
* Now goes directly to the post selected.
* Changed license to GPLv2 or later
* Added Czech translation. Props Roman Opet.
* Minor formatting updates.
* Removed compatibility for pre-3.3. installations.

= 1.3.1 =
* Updated utility class
* Various minor code changes

= 1.3 =
* Cut down on filter calls
* Optimized AJAX handling with jQuery 1.7.1
* Tested for WordPress 3.3.1

= 1.2 =
* Added compatibilty for WordPress 3.3
* Updated FAQ section

= 1.1 =
* Added filters so users can adjust the search query and displayed results to their needs (see Filter Reference)
* Fixed a bug where titles of unpublished posts were displayed

= 1.0 =
* Initial Release


== Upgrade Notice ==
Maintenance update for jQuery 1.7.1
