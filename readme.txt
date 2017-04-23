=== Eighty / 20 Results: Annual Pricing Choice for Paid Memberships Pro ===
Contributors: sjolshagen
Tags: pmpro, paid memberships pro, members, memberships, annual pricing choice, annual pricing, choice
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 1.4

Adds an option to select monthly or annual pricing for a membership. Will only show one or the other, based on the users choice.

== Description ==

Adds an option to the PMPro membership level selection page: "Payment choice" ("Monthly" / "Annual"). Will show/hide the level type selected depending on radio button choice.

Known limitations:

When using this add-on with the PMPro Multiple Membership Levels per User add-on, you can only configure annual/monthly payment levels when they're included in the Main group, not in custom groups.

== Installation ==

1. Upload the `e20r-annual-pricing-choice` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit your membership levels and set the Annual Pricing Choice options for each level.

== Changelog == 

== 1.4 ==

* BUG: Would sometimes loop too many times while processing levels
* BUG: Would sometimes fail with fatal error during plugin check(s).
* BUG: Would sometimes load too many levels BUG: Work around change in behavior for pmpro_getAllLevels()
* ENH: Use WP specific query argument handlers
* ENH: WordPress style changes

== 1.3 ==

* BUG: Wouldn't always display all levels during management operations
* BUG: Only process the Annual Pricing choice list
* ENH: Support pmpro_levels_array filter
* ENH: Remove levels handled by this add-on from the general levels list.
* ENH: Add support for handling Advanced Levels Shortcode add-on
* ENH: Add support for handling Multiple Membership Levels per User add-on

== 1.2 ==

* ENH/BUG: Handle excluded membership levels that use annual payment
* ENH: Split annual/monthly selectable membership levels from the other membership level(s)

== 1.1 ==

* ENH: Update CSS for backend
* ENH: Add build tools for Plugin
* ENH: Included GPL2 license text
* ENH: Added one-click update support
* ENH: Update version number (1.1)
* BUG: Didn't handle updates of options properly
* BUG: Clean up backend settings


== 1.0 ==

* Initial release