== Changelog ===
= 4.14.2 =
* Update Matomo core to 4.14.2

= 4.14.1 =
* Update Matomo core to 4.14.1

= 4.14.0 =
* Update Matomo core to 4.14.0
* Fix site sync problem in a non multistore mode
* Minor tweaks in the system report
* Fix notice in the system report with ithemes security above 8
* Update supported versions

= 4.13.5 =
* Removed php 8 function use

== Changelog ===
= 4.13.4 =
* Fix an autoloader bug in PHP 8.2
* Added notifications when there are issues in the system report

== Changelog ===
= 4.13.3 =
* Update Matomo core to 4.13.3
* Fix a bug for an undefined constant

= 4.13.2 =
* Update Matomo core to 4.13.2
* Fixes wrong linked GitHub issues when copy paste the system report in Github
* Fixes regression in the status report when the log contains HTML
* Update incompatiblity list

= 4.13.0 =
* Update Matomo core to 4.13.0
* Fix a bug in the MemberPress support
* Enhance the system report
* Fix a bug when detecting SQL access in the system report

= 4.12.0 =
* Update Matomo core to 4.12.3
* Update WordPress compatibility version
* Update WooCommerce compatibility version
* Update plugins compatiblity list

= 4.11.0 =
* Update Matomo core to 4.11.0
* Update plugins compatiblity list

= 4.10.0 =
* Update Matomo core to 4.10.0
* Fix a white screen issue in multisite mode
* Add wpstatistics import feature

= 4.6.0 =
* Update Matomo core to 4.6.0
* WooCommerce: Make excluded order status configurable using a constant
* Fix an open base dir issue

= 4.5.0 =
* Update Matomo core to 4.5.0
* Validate the input of the IP addresses and the user agents in exclusion settings
* Upgraded WooCommerce tested up to version
* Fix cannot exclude super admin accounts in multisite mode
* JS tracker via REST API doesn't work when using URL parameter for route

= 4.4.2 =
* Allow users to add opt out using a Gutenberg block
* Add a visual graph to the summary page
* Enable feature to select default report date
* Internal change: Improve coding style consistency
* Improve installation process

= 4.4.1 =
* Update core to 4.4.1
* Fix CORS settings could not be saved in the Matomo admin
* Add consent mode to the tracking code settings
* Fix the plugin paths which leads to an error on Windows OS
* Validate the HTML comments when a manual tracking code is configured
* Better content security policy support for the tracking code by using the "wp_get_inline_script_tag" method
* Mark few plugins as incompatible
* Detect if database tables are missing in the system report

= 4.3.1 =
* Validate HTML tracking code comments when configuring it manually
* Redirect to getting started page after activating the plugin in some cases
* Fix synchronising users may fail when there are thousands of users
* Update compatibility with WordPress and WooCommerce

= 4.3.0 =
* Update core to 4.3.1
* Detect Matomo URL better to prevent possible archiving failure "Unknown scheme"
* Don't stop the WP cron from executing when there is an archiving error
* Improve support for the Matomo Provider plugin
* Improve noscript configuration
* Add possibility to use JS tracking file from plugins directory if the default JS tracking file from uploads directory is blocked by the webserver
* Remove type="text/javascript" attribute from tracking code as it is not needed
* Other minor improvements

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
