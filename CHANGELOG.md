1.0.3
- Update Matomo core to 3.13.3
- Improve detection of regions
- Tweak system report to detect an incompatibility with WP-Matomo
- Improve wp-content path detection
- Ensure custom trackers are detected correctly
- Improve WooCommerce tracking

1.0.0
- First release.

0.5.0
- Upgrade core to 3.13.2
- Fix some ecommerce cart updates were not tracked
- Fix incompatibility with wp-external-links

0.4.1
- Allow enable tag manager when running in multi site (won't work when network enabled)

0.4.0
- Update core to 3.13.1
- No longer show Tag Manager in menu for multi site installations

0.3.16
- Log everytime we send an email

0.3.15
- Improve sending emails

0.3.14
- Improve memory management

0.3.13
- Make installer more stable

0.3.12
- Use DB-IP Geolocation DB instead of MaxMind
- Improve system report
- Improve visitor detection when cookies disabled

0.3.11
- Improve archiving when some PHP functions are disabled
- Improve system report

0.3.10
- Add new feature to clear system report logs
- Ignore warnings when bootstrapping archiving
- Fix table name for log tmp segment may be too long

0.3.9
- Fix admin backend might not load some html pages

0.3.8
- Fix archiving might not work when using lightspeed
- Improve system report detecting wrong plugin name
- Let plugins extend system report

0.3.7
- Fix warning constant matomo unit test not defined

0.3.6
- Improve formatting of numbers for some languages
- Fix error while archiving table name could be too long
- Improved system report

0.3.5
- Better PHP 7.4 support
- Improved error handling
- Improved update logging
- Fix admin not working when iThemes is used with feature disable plugin php files

0.3.4
- Fix issues with CLI archiving

0.3.3
- Improve test cookie logic
- Fix malformed download link in real time widget
- Improvements to system report & archiving

0.3.2
- Add method for plugins to detect the context

0.3.1
- Allow requests to piwik.php
- Make sure to install only if uploads dir has write permission

0.3.0
- Update Matomo core to 3.13.0
- Fix scheduled report in PDF format may not be rendered correctly under circumstances
- Check for symlinks in system report

0.2.11
- Better support when a WordPress directory is symlinked

0.2.10
- Improvements for the system report
- Apache htaccess change fixing HTML were not loaded on some installs

0.2.9
- Add zlib output compression to system report
- Added new update command
- Enabled the Marketplace

0.2.8
- Show Marketplace menu item but no content just yet
- Fix zlib output compression warning may be shown
- Better/faster data structure for settings
- Check for not compatible plugins in system report
- When tracking mode is disabled, show a tracking code example

0.2.7
- Improved system report
- Check for incompatible plugins
- Log SQL query when error occurs

0.2.6
- Improvements for multi site tracking in network mode
- Show default tracking code when tracking is disabled

0.2.5
- Require at least PHP 7.0 as WP with PHP 5.X is not compatible with Matomo
- Various improvements

0.2.4
- Add new feature: Safe mode

0.2.3
- Better handling of stripslashes

0.2.2
- Fix tag manager custom html tag adds slashes

0.2.1
- Option to disable ecommerce
- Show Matomo settings in system report

0.2.0
- Update to Matomo 3.12.0
- Improve session start success check

0.1.9
- Better session error logging

0.1.8
- Disable session when bootstrapping Matomo within WordPress
- Better system report

0.1.7
- Improve install script
- Tweaked wording in UI

0.1.6
- Handle database errors better and behave same as Matomo DB adapters
- Fix an issue in managing goals
- Improve support for PHP 7.2
- Show MySQL adapter in system report
- Hide not working link to tracking code in tour widget

0.1.5
- Fix error in htaccess file preventing tracker file to load
- Easy way to embed tag manager containers into site

0.1.4
- Fix opt out not working anymore
- Move tracking filter to exclusion settings
- Link from summary report to actual report in Matomo
- Add .htaccess files so Matomo works on more instances out of the box
- Improvements on how to load the core asset files

0.1.3
- Fix tracking code may have slashes added when switching between manually and disabled tracking code
- Fix proxy not sending JS when a WP plugin sends a notice

0.1.2
- Various fixes
- Show cron info in system report

0.1.1
- Improve compatibility with cookiebot

0.1.0
- Initial version
