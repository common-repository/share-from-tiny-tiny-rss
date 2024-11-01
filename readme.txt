=== Share from Tiny Tiny RSS ===
Contributors: kjcoop
Tags: tt-rss, ttrss, tiny tiny rss, post
Requires at Least:
Tested Up To:
Stable tag: 1.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Automatically create a Wordpress post of items you marked as "published" in Tiny Tiny RSS.

== Description ==

Tiny Tiny RSS [Tiny Tiny RSS](http://tt-rss.org/redmine/projects/tt-rss/wiki) is a server-based RSS reader and Android app, ttrss. To spare you the pain of copying and pasting between ttrss and Wordpress, this plugin can create a Wordpress post linking to all the items you marked as "published" in ttrss/Tiny Tiny RSS. It can optionally un-publish them on the server.

Please note that this plugin is not in any way affiliated with Tiny Tiny RSS - I'm just a fan. TTRSS has not in any way endorsed or approved this plugin - I rather doubt they even know it exists.

== Installation ==

1. Upload the contents of the zip file to wp-contents/plugins/
2. Activate the widget in the Plugins page
3. Under "Options", enter the link to your Tiny Tiny RSS installation and your username and password.
4. To use, go to "Posts" and click on the submenu, "Share from Tiny Tiny RSS"

== Frequently Asked Questions ==

None yet.

== Upgrade Notice ==

== Changelog ==

= 0.9 =

* Initial version

= 1.0 =

* Checks for "/api" at the end of URL and adds if necessary
* Automatically checks connection when you save new credentials
