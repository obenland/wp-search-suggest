=== WP Search Suggest ===
Contributors: kobenland
Tags: search, AJAX, jQuery
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=TLX9TH5XRURBA
Requires at least: 3.0
Tested up to: 3.1.2
Stable tag: 1.1

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


== Installation ==

1. Download WP Search Suggest.
2. Unzip the folder into the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

None asked yet.


== Screenshots ==

1. The suggested post and page titles.


== Filter Reference ==

**wpss_search_query_args** (*array|string*)
> The query args, passed to [WP_Query](http://codex.wordpress.org/Function_Reference/WP_Query "WP_Query in the WordPress Codex"), either as an array or a string.
> An array with the default query args and the current search query are passed to the filter.

**wpss_search_results** (*array*)
> An array with the result strings as values. An array with the default results and the WP_Query object are passed to the filter.


== Changelog ==

= 1.1 =
* Added filters so users can adjust the search query and displayed results to their needs (see Filter Reference)
* Fixed a bug where titles of unpublished posts were displayed

= 1.0 =
* Initial Release