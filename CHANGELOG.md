== Changelog ==

= 4.2.0 =
* Update Matomo core to 4.2.1
* Improve usability of getting started page and some tracking options
* Fix ecommerce tracking might not have worked when using Tag Manager embed method

= 4.1.3 =
* Fix query regarding a logtmpsegment appears falsely in logs
* Make sure tracker debug can print the output
* No longer include removed jQuery UI assets in WP 5.6
* jQuery 3 compatibility tweak

= 4.1.2 =
* Update core to 4.1.1
* Add tracker debug setting

= 4.1.1 =
* Improved upgrade logic from version 1.X. If you are already on version 4.X there won't be a change.

= 4.1.0 =
* Update core to 4.1.0

= 4.0.4 =
* Improved upgrade logic from version 1.X. If you are already on version 4.X there won't be a change. 

= 4.0.3 =
* Update core to 4.0.5
* Marked cookiebot and WP RSS Aggregator as incompatible
* Fixed an issue with WP Mail SMTP 

= 4.0.2 =
* Fix incompatibility with Total Cache plugin
* Add some new REST API methods
* Update core to 4.0.4

= 4.0.1 =
* Make sure archiving works when browser archiving is disabled

= 4.0.0 =
* Update Matomo core to 4.0 see changelog: https://matomo.org/changelog/matomo-4-0-0/
* Custom Variables is now no longer included but it is available as a marketplace plugin.
* Possibility to disable Apache AddHandler in .htaccess
* Better compatibility with PHP 8 
* Compatibility with WordPress 5.6

= 1.3.2 =
* Fix an issue where some versions of MemberPress were not tracked
* Fix WooCommerce cart coupons might not be applied when using WooCommerce Subscriptions
* More error logging for WooCommerce tracking 
* Fix segment in WP API had to be double encoded to work

= 1.3.1 =
* Log less messages by default
* Add possibility to disable logging and enable logging of all messages through wp-config.php
* Make sure jQuery URLs use correct protocol
* Optimise order of tracking settings
* Show a warning in system report if MS Edge 85+ is used
* Add more information to system report
* Other minor fixes

= 1.3.0 =
* Changed opt out shortcode to no longer use an iframe and instead print the content directly
* Automatically use a primary key for log tmp table when required
* Various minor edge case fixes

= 1.2.0 =
* Update core to Matomo 3.14.0
* Compatibility with WordPress 5.5.0
* Support selecting currency in tracking settings
* Various minor edge case fixes

= 1.1.3 =
* Improve compatibility with other plugins
* Improve system report
* Sync WP timezone change immediately when possible
* Keep caches for longer for better performance
* Automatically anonymise order confirmation url in WooCommerce
* Remove Apple pinned image
* Use sendBeacon when tracking to improve load time

= 1.1.2 =
* Update core to Matomo 3.13.6
* Improve installation
* Fix city report cannot be loaded

= 1.1.1 =
* Fix some settings were not accessible in WP Multisite mode when plugin is network enabled
* Ensure utf8mb4 upgrade works when when large indexes are disabled
* Fix archive reports button in diagnostics wasn't always triggering an archive

= 1.1.0 =
* Support utf8mb4 character set in tracking
* Improve compatibility with some plugins fixing some archiving issues
* Improve tracking settings for multi sites
* Add widgets to dashboard from the summary page
* Show a blog selector in the reporting page when using multi site
* Fix super admins weren't always synced in multisite mode
* By default delete all data on plugin uninstallation unless configured differently
* Improve system report
* Some minor fixes

= 1.0.6 =
* Improve compatibility with some other plugins
* Improve system report by adding more information
* Ensure to use WordPress DB charset

= 1.0.5 =
* Update Matomo core to 3.13.5
* Add location checks to system report
* Improve summary report layout for large screens
* Prevent a method in bootstrap may be defined twice
* Add new tracking setting to force POST request to prevent HTTP 414 errors

= 1.0.4 =
* Update Matomo core to 3.13.4
* Fix the website's timezone may be set to UTC instead of the WP timezone
* Improve compatibility with PHP 7.4 by fixing more notices
* Add a review link to the About page
* Add a newsletter signup possibility to the About page.
* Support MaxMind geolocation database
* Better support for hiding login URLs eg with WPS plugin
* Show header icon images
* Update GeoIP DB monthly instead of weekly
* Ask for a review every 90 days unless dismissed
* Possibility to configure proxy client header

= 1.0.3 =
* Update Matomo core to 3.13.3
* Improve detection of regions
* Tweak system report to detect an incompatibility with WP-Matomo
* Improve wp-content path detection
* Ensure custom trackers are detected correctly
* Improve WooCommerce tracking

= 1.0.2 =
* First release.
