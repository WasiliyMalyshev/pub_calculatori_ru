=== MicroCalc Widgets ===
Contributors: microcalc
Tags: calculator, math, education, school, trainer
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free step-by-step calculators and trainers from MicroCalc for your site: block, shortcode and sidebar widget.

== Description ==

More than 600 calculators, unit converters and trainers for school and everyday life: math, physics, chemistry, finance, dates. Every calculator shows a step-by-step solution. Available in Russian, English and French.

* Gutenberg block with search by name.
* Shortcode `[microcalc id="318"]` for the classic editor and page builders.
* Sidebar widget.
* Theme, button color, corner radius, font and width.
* The widget height adapts to the solution automatically.
* "Check installation" button: the service checks the page the same way a search robot does.

= Terms =

The widget is free. Under each widget the plugin shows a regular link to the calculator page on microcalc.io. The link must stay open to search engines: without it the widget shows an error and does not calculate. The plugin shows nothing until you accept the terms in Settings → MicroCalc.

= External service =

This plugin relies on the MicroCalc service (https://microcalc.io/):

* the calculator list is loaded from `https://microcalc.io/calc/widget_catalog.php` and cached for 12 hours;
* on pages with a widget, visitors' browsers load `https://microcalc.io/include/js/widget.js` and the calculator in an iframe; the address of the page with the widget is passed to the service to check the link;
* the "Check installation" button sends the page address you enter to `https://microcalc.io/calc/widget_check.php`.

The plugin itself does not collect or store any data about visitors. Service terms and privacy policy: https://microcalc.io/

== Installation ==

1. Install and activate the plugin.
2. Open Settings → MicroCalc and accept the terms.
3. Add the MicroCalc block to a page (or the `[microcalc id="…"]` shortcode) and choose a calculator.
4. Publish the page and check it with "Check installation".

== Frequently Asked Questions ==

= Where do I find the calculator id? =

In the block, choose a calculator by name. For the shortcode, use the list on the plugin settings page.

= The widget says "Widget disabled" =

The service did not find an open link on the page. Make sure the link under the widget is not removed, does not have rel="nofollow" and the page is not closed with noindex or robots.txt. After the fix the widget starts working within 5 minutes.

= Can I change the address of the service? =

Yes: Settings → MicroCalc → Service address, or `define( 'MICROCALC_WIDGET_BASE', 'https://…/' );` in wp-config.php.

== Changelog ==

= 1.0.0 =
* First release: block, shortcode, sidebar widget, settings and installation check.
