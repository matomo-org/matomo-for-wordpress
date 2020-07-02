== Changelog ==

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
