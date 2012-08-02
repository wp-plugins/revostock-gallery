=== RevoStock Media Gallery ===
Contributors: NewClarity, RevoStock
Tags: revostock, video, audio, aftereffects
Requires at least: 3.2
Tested up to: 3.4.1
License: GPLv2 or later
Plugin URI: http://revostock.com/wordpress.html
Stable tag: 1.1.1


The RevoStock Media Gallery plugin displays a gallery of media assets available for purchase from RevoStock.com.


== Description ==

Welcome to the RevoStock Media Gallery WordPress plugin! This plugin allows RevoStock members to insert a gallery of RevoStock media items (video, audio, AfterEffects templates, Apple Motion templates) into WordPress posts or pages, using the shortcode or the added RevoStock star button on the post editor.

A thumbnail image and description are displayed for each media file, and display of the gallery is controlled by the shortcode attributes - or default values specified on the plugin settings page. For your convenience, you can either manually add the shortcode with your desired attributes, or just click the RevoStock Media Gallery editor button to have it inserted for you.

Choose from one of the four bundled color schemes (black-and-white, grey, red, blue), or add your own custom CSS by specifying a prefix.

Requires WordPress 3.1 *and* PHP 5.2

### How-to video:###

[youtube=http://www.youtube.com/watch?v=6WL3CSioEyA]

**Current gallery display options**

* Display a particular RevoStock file
* Display files from a particular RevoStock mediabox
* Display files from a specific RevoStock producer
* Display files of a particular type: Audio, Video, AfterEffects, Motion
* Display files containing search terms
* Display files from a specific RevoStock group: Newest, Most Downloaded, or Editor's Choice
* Limit the number of files displayed - from 1 to 40
* Choose from one of the four bundled color schemes (black-and-white, grey, red, blue)
* Add a custom CSS prefix to provide your own custom CSS

== Installation ==

This section describes how to install the RevoStock Media Gallery plugin.

1. Upload `revostock-gallery` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add your RevoStock credentials to the Settings->Revostock->Account page.
4. Manually add a shortcode to your post or page (e.g. [revostock-gallery mediabox=29323] )
or simply use the button on the editor which inserts the shortcode and arguments for you

== Frequently Asked Questions ==

= How do I get started? =
The RevoStock Media Gallery plugin requires

* A RevoStock user account (it's free!) - http://www.revostock.com/RegMember.html

* API access - after logging in to RevoStock, visit: http://www.revostock.com/api.html

Currently, API authorization is only available to RevoStock Producers (users who sell content through RevoStock) but will be open to all members soon.

Also, you can earn more by joining "Share the Revo"
http://www.revostock.com/Affiliate.html

Video:
[youtube=http://www.youtube.com/watch?v=6WL3CSioEyA]


= I've installed the plugin, placed the [revostock-gallery] shortcode into a post but I'm receiving a "There is a problem with your account. Please check settings" message displayed in the post =


Be sure that you've provided credentials on the Account page of the plugin's settings. You'll need both an email address
and password registered at RevoStock.com as well as API authorization.

= Where do I go for support? =

 - Ask questions or report bugs at [http://www.revostock.com/Helpdesk.html](http://www.revostock.com/Helpdesk.html),

= Who developed this plugin? =
 - <a href="http://profiles.wordpress.org/satsura/">Valara Satsura</a> - 1.1 Development
 - Craig Lillard - 1.1 Development
 - <a href="http://profiles.wordpress.org/mikeschinkel/">Naomi C. Bush</a> - 1.0 Development
 - <a href="http://profiles.wordpress.org/Meanderingcode/">Sean Leonard</a> - Pre-release Development
 - <a href="http://profiles.wordpress.org/cshepherd/">Carol Shepherd</a> - Technical project management, wireframes
 - <a href="http://profiles.wordpress.org/marnafriedman/">Marna Friedman</a> - QA and Project management
 - <a href="http://profiles.wordpress.org/mikeschinkel/">Mike Schinkel</a> - Client engagement and high-level architecture


== Screenshots ==

1. First, make sure you have your RevoStock user name and password, AND active API access
2. Welcome page & explanation of shortcode attributes
3. Set shortcode default attributes
4. Use the editor button to insert the shortcode into your posts and pages
5. Gallery display on page


== Changelog ==
= 1.1.1 =
* Fixed problems with popups not displaying large enough in some cases.
* Shortened names of CSS files.

= 1.1.0 =
* Updated styling
* Fixed problems with popup window overflow
* Added rel=nofollow to links (http://support.google.com/webmasters/bin/answer.py?hl=en&answer=96569)
* Made the iframe bigger to accomodate varying sizes of videos playing.
* Checked compatibility with Wordpress 3.4.1

= 1.0.0 =
* Rewritten core
* Redesigned admin interface
* Changed to user-friendly shortcode attribute names and removed unnecessary attributes
* Added post editor button

= 0.9.14 =
* Updated styling
* Minor changes to admin page labels
* Fixed problem with some audio not playing

= 0.9.13 =
Updated styling

= 0.9.10 =
Updated styling

= 0.5 =
Initial beta release

== Upgrade Notice ==
= 1.0.0 =
This is a significant release. Please upgrade.
