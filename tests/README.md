# How to run tests?

## Preparation
* [Install composer](https://getcomposer.org)
* cd into the matomo plugin directory, eg `cd wp-content/plugins/matomo`
* Run `composer install`
* Install the test DB `tests/bin/install.sh <db-name> <db-user> <db-password> [db-host]`
  * For example `./bin/install-wp-tests.sh wordpress_test root secure 127.0.0.1 latest`

## Running tests
* Run all the tests by executing `vendor/bin/phpunit`
* Run a specific test by executing `vendor/bin/phpunit tests/phpunit/wpmatomo/test-settings.php`
* Run tests for multi site prefix the tests with `WP_MULTISITE=1 vendor/bin/phpunit`
