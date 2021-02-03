=== Plugin Name ===
Contributors: Sneddo
Tags: etsy, widget
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7NV5BUT3A2TEG
Requires at least: 3.0.5
Tested up to: 3.2.1
Stable tag: 1.3.6

This widget displays Etsy items (from favourites or store) in a widget, without using flash or an iframe, which allows greater theme integration. 

== Description ==

This widget combines the features of two other widget: *Custom Etsy Widget* and *Etsy Sidebar Widget*. This allows you to display Etsy items from your favourites or a store in a sidebar widget, without relying on an iframe or Flash. This allows you to integrate the widget into your theme with ease.

You can choose to show items in a random order, or newest items.

This plugin requires [cURL](http://au.php.net/manual/en/book.curl.php). 

You can see it action at http://www.phoebec.com

As this is my first attempt at a Wordpress widget, there may be some bugs. Please use the [contact form](http://sneddo.net/contact) on my website or send me a convo on [Etsy](http://www.etsy.com/people/WascallyWabbit) to report bugs. Please do this before reporting the widget as "Broken".

**The term "Etsy" is a trademark of Etsy, Inc.  This application uses the Etsy API but is not endorsed or certified by Etsy, Inc.**

== Installation ==

1. Upload `CustomizableEtsyWidget.php` to the `/wp-content/plugins/` directory or install through Wordpress Plugins menu.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Add the Widget to a sidebar.
1. Set the Etsy Shop Name, and set options to suit how you wish the widget to display.
1. Style the output as you wish, using the etsyItemTable and widget_customizableetsywidget classes. If your theme supports the wp_head hook, some basic CSS is inserted here to display the table centred, with some padding between items.

== Frequently Asked Questions ==

= How can I report a bug or suggest a new feature? =

Please use the [contact form](http://sneddo.net/contact) on my website or send me a convo on [Etsy](http://www.etsy.com/people/WascallyWabbit). Please do this before reporting the widget as "Broken".

= Why is my latest item is not showing up? =

Etsy developer guidelines suggest that item results should be cached for 6 hours to reduce the amount of load on their servers. 

= How can I change how it looks? =

Because the widget does not add a Flash element or an iframe, the theme CSS should be inherited from the sidebar style.

The table that the widget builds has the class etsyItemTable, which can be used to style the widget. If you want to override the default styles, you can do so using the [!important](http://www.w3.org/TR/CSS2/cascade.html#important-rules) keyword.

= Why does it take so long to load occasionally? =

By default, the widget refreshes the data every 6 hours, which can be a lengthy process if you have a lot of items in your store/favourites. The next time you load the page you will notice the time is significantly less.

You can customise this behaviour in the Advanced settings section of the widget control panel. 

If you get script execution timeouts, I suggest you change to displaying newest items, as this restricts the data update to only the number of items you are displaying.

= Why are my favourites not showing? =

You must have your favourites set to public for you to be able to show your favourites with this widget.

== Screenshots ==

1. Widget Settings panel
2. The widget in use

== Changelog ==

= 1.0 =
* Initial release.

= 1.1 =
* Added configurable cache behavior - 6 hours, 12 hours, 24 hours, Manual
* Added cache directory setting - defaults to sys_get_temp_dir()
* Cache now cleared and re-populated when changes are made to settings

= 1.2 =
* Additional configuration of store link 
* Improved cache refresh - now only refreshes when saving settings that change cach requirements
* Updated screenshots and readme

= 1.3 =
* Added ability to show items from a store section only
* Began improvements to settings panel

= 1.3.1 =
* Fixed path to javascript file error - removed hard-coded path

= 1.3.2 =
* Fixed bug in showing random favourites

= 1.3.3 =
Updated widget to support Wordpress 3.2 when released - jQuery updated to 1.6

= 1.3.4 =
Updated to new Etsy API URLs. Improved error handling and other improvements suggested by alx359

= 1.3.5 =
Option to open links in new window. Tweak to file handling.

= 1.3.6 =
Removed PHP short tags. Thanks Workshopshed

== Upgrade Notice ==
= 1.1 = 
Adds advanced features to allow greater control of caching

= 1.2 =
Additional config of store link. You may need to reset some options with this release.

= 1.3 = 
Added ability to show a store section only. As this is a big update, please contact me if you have any problems.

= 1.3.2 =
Fixed bug that occurs occasionally when showing random favourites.

= 1.3.3 =
Updated widget to support Wordpress 3.2 when released

= 1.3.4 =
Support new Etsy API and suggestions/bug fixes 

= 1.3.5 = 
Option to open links in new window. Tweak to file handling.

= 1.3.6 =
Removed PHP short tags. Thanks Workshopshed