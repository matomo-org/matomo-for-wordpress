# Matomo Analytics & Tag Manager for WordPress

## Code Status

[![Build Status Develop](https://travis-ci.com/matomo-org/matomo-for-wordpress.svg?branch=develop)](https://travis-ci.com/matomo-org/matomo-for-wordpress)
[![Build Status Live](https://travis-ci.com/matomo-org/matomo-for-wordpress.svg?branch=live)](https://travis-ci.com/matomo-org/matomo-for-wordpress)

## Description

A hassle-free and cost-free web analytics platform for your WordPress which lets you stay in full control with 100% data ownership and user-privacy protection.

This plugin installs a fully functional [Matomo](https://matomo.org) within your WordPress. If you already have a working Matomo (either [On-Premise](https://matomo.org/matomo-on-premise/) or [Matomo Cloud](https://matomo.org/hosting/)), use the [WP-Matomo Integration plugin](https://wordpress.org/plugins/wp-piwik/) instead. If you have a high traffic website, we recommend using On-Premise or the Cloud-hosted solution for better performance.

Learn more about this plugin in the [readme.txt](readme.txt).

## License

Matomo is released under the GPL v3 (or later) license, see [LICENSE](LICENSE)

## Get involved!

We believe in liberating Web Analytics, providing a free platform for simple and advanced analytics. Matomo was built by dozens of people like you,
and we need your help to make Matomo better… Why not participate in a useful project today? [Learn how you can contribute to Matomo.](https://matomo.org/get-involved)

You can also find more information on our [developer website](https://developer.matomo.org/).

### Installing Matomo for WordPress for development using git

Make sure the plugin folder in your WordPress is not called `matomo-for-wordpress` but `matomo`. When you clone the repository you may directly want to specify the right path like this:

```bash
git clone git@github.com:matomo-org/matomo-for-wordpress.git wp-content/plugins/matomo
```

#### Install composer

Now run the below command to have phpunit etc available. Requires [Composer](https://getcomposer.org/) to be installed.

```bash
composer install # or composer.phar install
```

#### Local environment through docker

If you have docker and docker-compose installed, you can setup a local development environment with a single command:

```bash
docker-compose up wordpress; docker-compose stop
```

The first time the container starts it will compile php extensions within the container it needs, download wordpress
and set it up.

After starting the container, visit `https://localhost:3000/` to see the list of available wordpress versions.
Pick one, and it will take you to the Wordpress installer.

After installing Wordpress, go to the plugins page and activate Matomo for Wordpress.

Note: docker related files, such as the downloaded wordpress and database files, will be stored in a new folder named `./docker`. As long
as you are using this local dev environment, you should not delete this folder.

**Customizing your local environment**

You can customize your local environment by setting environment variables in a `.env` file. Currently, the following
variables are supported:

- `PHP_VERSION` - defaults to 8.1, must be a version that has an official docker container available
- `BACKEND` - 'mariadb' or 'mysql', defaults to 'mariadb'
- `WORDPRESS_VERSION` - defaults to 6.3.1
- `PORT` - the port to expose wordpress on, defaults to 3000
- `WP_PLUGINS` - a list of plugin/version pairs like "my-plugin my-other-plugin:1.2.3". For each item, wp-cli will attempt to download and activate the plugin.
  This is the same format as the Active Plugins entry in the System Report, so you could copy that value to this environment variable to quickly (or more quickly)
  replicate a user's setup.

**Running wp-cli**

Make sure the wordpress service is running in one terminal, then in another terminal run:

```bash
docker-compose run wp <... rest of command ...>
```

**Accessing MariaDB/MySQL**

First ensure the database you want to inspect (mariadb or mysql) is the one that's currently being used by your local
environment. Then, while the local environment is running in one shell, open another and run the command:

```bash
docker-compose run mariadb mariadb -h mariadb -u root -p
```

Enter `pass` for the password.

(For mysql, replace instances of "mariadb" in the command with "mysql".)

## Security

Security is a top priority at Matomo. As potential issues are discovered, we validate, patch and release fixes as quickly as we can. We have a security bug bounty program in place that rewards researchers for finding security issues and disclosing them to us.

[Learn more](https://matomo.org/security/) or check out our [HackerOne program](https://hackerone.com/matomo).

## Contact

Website: [matomo.org](https://matomo.org)

About us: [matomo.org/team/](https://matomo.org/team/)

Contact us: [matomo.org/contact/](https://matomo.org/contact/)
