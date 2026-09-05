== Changelog ===

= 5.13.1 =
* Bug fix: when the plugin is network activated, a blog's administrator now holds Matomo super
  user access on that blog, instead of no access at all. Note: Matomo super user access is not
  the same as WordPress super user access; it only provides access to a specific blog's Matomo,
  nothing else. Because of this, the administrator role can no longer be given a Matomo access of
  its own on Matomo > Settings > Access. Access a network activated install had configured there
  before no longer has any effect, since Matomo super user access outranks anything that screen can
  grant.
* Note: because of the above, a blog admin now sees more Matomo screens than before on a network
  activated install, including Matomo > Diagnostics and some of its troubleshooting actions. The
  actions shown to such users only operate on the single blog they are displayed for, which the
  admin has access to.
* Security: the "Sync all users across sites / blogs" and "Sync all sites (blogs)" actions on
  Matomo > Diagnostics now require an administrator of the network and are hidden from everyone else.
* Security: the "Install/Update Geo-IP DB" action on Matomo > Diagnostics now requires an
  administrator of the network when the plugin is network activated, and is hidden from everyone
  else, since the geolocation database is downloaded once for the whole install rather than once per
  blog.
* Security: the request that previews the generated JavaScript tracking code on
  Matomo > Settings > Tracking now requires the same access as the tracking settings page:
  Matomo admin access.
* Security: when the plugin is network activated, the tracking settings are stored once for the
  whole network, but the Get Started page and Matomo > Settings > Tracking only asked for Matomo
  super user access, which a blog's own administrator can grant. Changing them now requires an
  administrator of the network.
* Security: in a multisite network, the Manual tracking mode, and the "Tracking code" and "Noscript
  code" tracking settings now require WordPress' unfiltered_html capability.
* New feature: the tracking filter on Matomo > Settings > Exclusions is now two settings when the
  plugin is network activated. A network administrator can set the roles excluded on every blog in
  the network, while administrators of specific blogs can exclude roles for the blogs they manage.
* New feature: everything on Matomo > Settings > Exclusions is now editable with Matomo admin access
  in a blog's own admin: the excluded IPs, query parameters, user agents and URL fragments.
* Bug fix: the excluded user agents on Matomo > Settings > Exclusions are now stored per blog,
  like the other exclusions on that screen, when the plugin is network activated.
* Bug fix: network activating the plugin on a multisite where blogs had activated it individually
  now carries each blog's tracking filter over to the per blog setting described above. Previously
  a blog's global settings stopped being read the moment the network's took over, so roles a blog
  had excluded from tracking started being tracked again, with nothing to show for it and no way
  left to reach the setting. Only the tracking filter is carried over; the remaining global
  settings become the network's, as they always have.

= 5.13.0 =
* Update core Matomo to version 5.13.0.
* Compatibility: Matomo 6 will require PHP 8.1, MySQL 8.0 or MariaDB 10.6 at minimum. Plugin updates to Matomo 6 are now aborted on systems that do not meet these requirements.
* Compatibility: should a version that needs the new requirements end up installed on a
  server that does not meet them, the plugin now loads in safe mode and explains what is
  missing.
* Security: in multisite, a Settings instance kept across a switch_to_blog() call could write
  one blog's tracking code and other per blog settings into a different blog. Settings are now
  reloaded when the current blog changes.
* Bug fix: syncing a single blog's site metadata no longer overwrites the network wide manual
  tracking code, and no longer forces every other blog to regenerate its tracking code.
* Bug fix: a blog that is given a new Matomo site during a sync now regenerates its tracking
  code, instead of keeping one that still refers to the site it no longer belongs to.
* Bug fix: site syncing now reports a failure when a blog could not be installed.
* Bug fix: site and user syncing now skip archived blogs and blogs marked as spam, as they
  already did for deleted ones. A blog returning to service is synced straight away.
* Bug fix: the MaxMind license key is no longer written to the debug log in plain text.
* Internal change: matomo_tracking_settings_changed is no longer fired when a site sync only
  updated a blog's metadata (its name, URL, timezone, currency or ecommerce flag), since no
  tracking setting changed in that case. Listeners that need to know a site was synced can use
  matomo_site_synced, which is still fired.

= 5.12.2 =
* Bug fix: removing users from a blog did not correctly remove Matomo permissions. Matomo
  permissions stayed valid until the daily sync executed.
* Security: after authentication, if a user has more Matomo capabilities than their WP
  capabilities allow, correct the user access row and only grant what their WP capabilities
  allow.
* Bug fix: multisite syncing and other functionality was broken for multisite installs
  with > 100 sites.
* Bug fix: make sure access syncing occurs when a user is granted superuser access and
  if superuser access is revoked.
* Bug fix: make sure user syncing can occur in REST, XML RPC and other non-frontend requests.
* Bug fix: make sure site syncing can occur in REST, XML RPC and other non-frontend requests.
* Bug fix: when saving user assigned WP role => matomo permission mappings, only sync changes
  with Matomo after the changes are finalized in WordPress (or nothing gets synced until the
  daily user sync).
* Bug fix: drop Matomo tables and the WP => Matomo site mapping when a site is deleted in WP.
* Bug fix: ensure Matomo capabilities are correctly assigned to users in safe mode.
* Reliability: better handling of potential failures during the user and site sync processes.
* Bug fix: make sure site and user syncing occur when a WP blog is restored (and Matomo is
  activated for it).

= 5.12.1 =
* Security: remove unneeded token auth authentication path for Matomo API from WordPress/Auth.

= 5.12.0 =
* Update core to 5.12.0.
* Bug fix: fix user collision that can occur when a user is deleted from Matomo.

= 5.11.1 =
* Fix two vulnerabilities.

= 5.11.0 =
* Update embedded Matomo to version 5.11.1.
* Bug fix: fix incorrect capability check that used current user in some cases instead of requested user.
* Add notice to inform users of change in minimum requirements in Matomo 6.
* Some visual tweaks to recent marketplace UI changes.

= 5.10.2 =
* Bug fix: fix bug incompatibility issue in admin display, with several other WordPress plugins.
* Bug fix: update API version of Matomo opt out block.

= 5.10.1 =
* Enable dismissible premium plugin promotional content in the main reporting menu.
* Bug fix: missing global declaration breaks woocommerce order tracking for some users.
* Redesign marketplace overview.
* Some minor UX tweaks to better match WordPress 7's look and feel.

= 5.10.0 =
* Update embedded Matomo to version 5.10.0.
* Open Matomo Admin and Reporting pages in a new tab.
* Hide 3rd party notices on MWP pages to allow for a clutter free experience.
* Update Matomo logo for rebrand.

= 5.8.2 =
* New feature: behaviour based plugin suggestions to connect users with potentially useful premium features.

= 5.8.1 =
* Bug fix: shortened date labels in Visits Over Time graph were not correct.
* Bug fix: premium plugin prices were not accurate.

= 5.8.0 =
* Display unique visitors along with visits in Visits Over Time graph on Summary page.
* Add marketplace install steps to Get Started page.
* Re-word Get Started page to provide better information on respecting user privacy.
* Upgrade Matomo core to 5.8.0.

= 5.7.1 =
* Bug fix: avoid error in AI bot tracking feature when REST API tracking endpoint is used.
* Tie asset cache busters to plugin version.

= 5.7.0 =
* Update Matomo core to 5.7.1.
* Change left menu icon and menu item positioning.
* New feature: server side tracking of AI bots.
* Bug fix: check that order in query parameters matches current order before tracking an order.

= 5.6.1 =
* Update Matomo core to 5.6.2.
* Bug fix: only execute geolocation database update if internet features are enabled. (Add `[General] enable_internet_features=0` to the /path/to/wordpress/wp-content/uploads/matomo/config/config.ini.php file to disable internet features.)
* Bug fix: avoid autoloading conflicts with the wikimedia/less.php library (fixing display issues when this plugin is used on wordpress.com and with certain other plugins).
* Bug fix: fix incorrect detection of global upload directory in a multisite installation.
* Bug fix: allow geolocation database update to also run on multisite installs when the plugin is not network activated.

= 5.6.0 =
* Update Matomo core to 5.6.1.
* Add clear warning message if an adblocker is detected since many adblockers interfere with Matomo's reporting.
* Slight redesign to Marketplace overview.
* Ensure matomo language cookie is always set to current user's locale so changes users make to language settings will always be reflected in Matomo.
* Bug fix: ensure the wp-statistics importer works with the newest version of wp-statistics.
* Add current user locale to system report.
* Bug fix: ensure user agents with commas can be excluded from tracking.

= 5.3.3 =
* Fix regression causing fatal errors when ecommerce tracking is used but WooCommerce is not installed.
* Workaround infinite recursion problem in Matomo core that occurs when an AJAX method is not authorized to function.

= 5.3.2 =
* Fix ecommerce tracking issues that occur when WordPress plugins that initiate WooCommerce events from JavaScript hosted on another server, like the Klarna plugin.
* Fix issue site switching error that occurs during scheduled task execution when resetting the Matomo environment.
* Fix issue preventing users with Matomo roles only to view the WordPress admin dashboard.
* Fix image display issue with SearchEngineKeywordsPerformance plugin.

= 5.3.1 =
* New troubleshooting tool that runs a specific scheduled task.
* Fix compatibility issue with the All In One SEO plugin that resulted in incorrectly tracking a cart addition on a product view.
* Use HTTPS link to the marketplace install archive.
* Add JavaScript opt out code in new shortcode which is now considered the recommended option.
* Workaround lack of prerendering support in Matomo core JavaScript tracker.
* Make sure report export and report config buttons in report footers are noticed by screen readers.
* Update Matomo core to 5.3.2.
* Fix critical error that can occur if a product cannot be found during tracking.
* Fix issue causing images in third party Matomo plugins to fail to load.

= 5.3.0 =
* Update Matomo core to 5.3.1.
* Allow Matomo API and tracker requests to be authenticated by WordPress App Passwords.
* Allow direct access to the Matomo API, rather than only through the WordPress REST API.
* Bug fix: fix session token auth storage issue causing "Oops there was a problem" error messages for some users.

= 5.2.2 =
* Fix to a random failure in our automated release process that resulted in some broken images.
* Bug fix: revert change causing REST API methods to be mismatched.

= 5.2.1 =
* Update Matomo core to 5.2.2.
* New feature: tracking setting that allows generating a visitor ID server side if no visitor ID cookie exists.
* Bug fix: fix issue causing errors in archiving for some Matomo premium plugins that include '?' in SQL string literals.
* Bug fix: in the summary, do not display reports that have nothing but empty rows.
* Bug fix: only track cart updates after WooCommerce calculates cart totals, since triggering cart total calculation early can interfere with other WooCommerce plugins.
* Bug fix: make sure to track ecommerce cart updates if the shipping changes in WooCommerce.
* Bug fix: avoid errors when displaying the summary page without a period and date specified in the URL.
* Bug fix: fix issue with Matomo for WordPress plugin not being detected properly on Windows systems.
* Bug fix: when triggering an update manually in the Troubleshooting page, make sure core plugin updates are also executed, rather than just core updates.
* Bug fix: schedule geolocation db install and other events after an install completes successfully instead of during.
* Bug fix: do not allow multiple requests to install Matomo at the same time.
* Bug fix: do not trigger the golocation db install task during install, if it has run at least once before.
* Bug fix: the normal Matomo site selector should not be displayed on Matomo reporting/admin pages. It is now hidden.

= 5.2.0 =
* Update Matomo core to 5.2.1.
* Bug fix: using marketplace plugins in a multisite WordPress install could result in fatal errors on creation of a new blog.
* Compatibility with the latest wpstatistics version in the wpstatistics importer.

= 5.1.7 =
* Bug fix: in scheduled report emails, fix the URLs for flag images.
* Bug fix: use same charset/collate as WordPress.
* Bug fix: fix General_Confirm shown in some cases when showing the password confirmation modal.
* Bug fix: fix uninstall script error.
* Bug fix: more compatibility fixes with wpstatistics in the wpstatistics importer.

= 5.1.6 =
* Bug fix: the wpstatistics importer failed to import search keywords due to a change in the wpstatistics plugin from over year ago.
* Bug fix: the wpstatistics importer will now work with the latest wpstatistics version.
* New consts: for users that need to use a separate charset/collation from the values configured wp-config.php, there are now two consts that allow you to do that: MATOMO_DB_CHARSET and MATOMO_DB_COLLATE.
* Make the short description match both in matomo.php and readme.txt.

= 5.1.5 =
* Bug fix: remove system report issues notification earlier when system issues are resolved.
* Bug fix: remove duplicate redirect on activation logic.

= 5.1.4 =
* Update Matomo core to version 5.1.2 (changes: https://matomo.org/changelog/matomo-5-1-2/)
* Update some Matomo core dependencies.

= 5.1.3 =
* Update Matomo core to version 5.1.1 (changes: https://matomo.org/changelog/matomo-5-1-1/)
* Moderate redesign to the feedback and get started admin pages.
* Redesign of the tracker settings admin page.

= 5.1.2 =
* Incomplete bug fix: the session hijacking or replay attack fix for functionality that hides notifications for users was incomplete.
* Bug fix: the wp-statistics import is broken when the newest version of wp-statistics is installed.

= 5.1.1 =
* Bug fix: in multisite installs, make sure the geoip database update only runs once for the entire WordPress instance.
* Bug fix: system report error notice should only be shown to superusers.
* Bug fix: patch Matomo core to fix an iconv() notice that can occur during geolocation.
* Allow re-running updates from a specific version in troubleshooting page to help when WordPress fails to update the plugin completely.
* Very minor security fix: functionality that hides notifications for users is no longer able to be used in session hijacking or replay attacks.

= 5.1.0 =
* Upgrade Matomo core to version 5.1.0 (changes: https://matomo.org/changelog/matomo-5-1-0/).
* Bug fix: sending emails with attachments failed on WordPress 5.
* Bug fix: fix rare critical error when PEAR library is used in a certain way.
* Fix deprecation notice in marketplace setup wizard.

= 5.0.8 =
* Improve PHP CLI diagnostic change: avoid reporting issues when a hosting provider does not support CLI archiving.

= 5.0.7 =
* Bug fix: setting an email report's segment to "All Visits" did not work properly.
* Update core Matomo to version 5.0.3.
* Workaround bug in the SMTP2GO WordPress plugin causing PDF email reports to be sent without the attached report.
* Change PHP CLI diagnostic to avoid showing users an "error" that has no effect on Matomo's ability to function.
* Better error handling for Matomo cron tasks.
* Add a simple new setup wizard for the Matomo Marketplace for new users.

= 5.0.6 =
* Display previously inaccessible Matomo plugin per-site settings in new settings tabs.
* Added a notice to the WP plugins admin displaying whether deleting the plugin will delete analytics data or not.
* During updates to Matomo, try to automatically fix any MySQL ROW_FORMAT errors that occur.
* Bug fix: ensure cookies will not be set during ecommerce tracking if cookies are disabled in settings.

= 5.0.5 =
* Improved compatibility with CiviCRM.
* Improved compatibility with AzonPress.
* Avoid critical error that can occur when a WordPress server's filesystem is not recognized as "direct".

= 5.0.4 =
* Fix display error in the multisite site selector.
* Remove diagnostics related to formerly incompatible WP plugins.

= 5.0.3 =
* Fix critical error that can occur while upgrading to Matomo for WordPress 5, if using Matomo marketplace plugins.

= 5.0.2 =
* Update Matomo core to 5.0.2.

= 5.0.1 =
* Update Matomo core to 5.0.1, which includes improved security, many bug fixes and performance improvements.
* Fix most existing plugin incompatibilities.
* Fix double encoded JSON response from REST API when `format=json` is used in Matomo API requests. If you use the Matomo API through WordPress REST requests, you may need to adjust your code that processes API responses.
* Matomo TagManager API methods added to WordPress' REST API.
* Respect user's configured error_reporting INI config value.
* Fix reports not being output correctly in WordPress' REST API.
* Fix ecommerce events not tracked if orders created from WordPress' REST API.
* Fix site sync tries to synchronize deleted blogs.
* Fix broken WpStatistics import caused by upates in the WpStatistics plugin.
* Add diagnostic that checks if WordPress can be loaded via PHP CLI.
* Make sure correct matomoversion query parameter is included in links to Matomo plugins.
* Fix scheduled tasks can be invoked non-stop when wp_next_scheduled() returns 0.
* Fix ms-blogs.php should only be required if currently in a multisite install.

= 4.15.3 =
* Compatibility with WooCommerce's HPOS feature
* Avoid executing System Report on every Matomo for WordPress admin page view
* Show Ninja Firewall notification as a row in the system report as opposed to a warning that cannot be dismissed
* Attempt to track WooCommerce orders when order status changes from pending to processing in case customers never visit the order confirmation page
* Better detection of WooCommerce orders that were created manually through the WordPress back office
* Fix Tag Manager errors caused by missing file in 4.15.1 release
* Add a new advanced setting for disabling async archiving without having to modify wp-config.php
* Add a success notification when manually triggered archiving via the Troubleshooting menu succeeds
* Add new system report check that checks that the bots.yml file is not accessible
* Use new name for related WordPress plugin 'Connect Matomo'

= 4.15.2 =
* Fix ecommerce reports not archiving if no goal conversions or order for the site
* Provide more context when an API request fails token authentication
* Add notification if Ninja Firewall plugin is detected to direct users to relevant FAQ
* Make sure user syncing does not fail if users are deleted outside of wordpress
* Fix archiving failures when getmypid() is disabled on a hosting provider
* Fix ecommerce tracking when the shared hosting server is detected as a bot by Matomo
* Fix ecommerce tracking when a wordpress plugin performs ecommerce AJAX via admin-ajax.php
* Add ecommerce tracking setting to system report
* Workaround FluentSMTP bug in sending emails with attachments (for email reports)

= 4.15.1 =
* Update Matomo core to 4.15.1
* Fix MaxMind license key input

= 4.15.0 =
* Update Matomo core to 4.15.0

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
