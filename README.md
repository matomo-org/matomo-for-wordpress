# Matomo Analytics & Tag Manager for WordPress

## Code Status

[![Build Status Develop](https://travis-ci.com/matomo-org/matomo-for-wordpress.svg?branch=develop)](https://travis-ci.com/matomo-org/matomo-for-wordpress)
[![Build Status Live](https://travis-ci.com/matomo-org/matomo-for-wordpress.svg?branch=live)](https://travis-ci.com/matomo-org/matomo-for-wordpress)

## Description

A hassle-free and cost-free web analytics platform for your WordPress which lets you stay in full control with 100% data ownership and user-privacy protection.

This plugin installs a fully functional [Matomo](https://matomo.org) within your WordPress. If you already have a working Matomo (either [On-Premise](https://matomo.org/matomo-on-premise/) or [Matomo Cloud](https://matomo.org/hosting/)), use the [Connect Matomo Integration plugin](https://wordpress.org/plugins/wp-piwik/) instead. If you have a high traffic website, we recommend using On-Premise or the Cloud-hosted solution for better performance.

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

If you have docker and docker compose installed, you can setup a local development environment with a single command:

```bash
npm run compose up wordpress; npm run compose stop
```

The first time the container starts it will compile php extensions within the container it needs, download wordpress
and set it up.

After starting the container, visit `http://localhost:3000/` to see the list of available wordpress versions.
Pick one, and it will take you to Wordpress.

Go to wp-login.php, then enter `root` for the user name and `pass` for the password and login.

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
- `WP_ADMIN_USER` - the admin wordpress username, defaults to 'root'. This variable is only used when installing wordpress. It shouldn't be changed afterwards, unless doing a fresh install.
- `WP_ADMIN_EMAIL` - the admin user's email, defaults to 'nobody@nowhere.local'. This variable is only used when installing wordpress. It shouldn't be changed afterwards, unless doing a fresh install.
- `WOOCOMMERCE` - if set, installs and sets up woocommerce. This includes setting up a payment gateway with stripe and adding some test products. (Note: you will still have to go through woocommerce
  onboarding as there is no way to disable it, and that means you will have to enter your stripe test keys manually when setting up the payment gateway.)

**Running wp-cli**

Make sure the wordpress service is running in one terminal, then in another terminal run:

```bash
npm run compose run wp -- <... rest of command ...>
```

**Running matomo console**

Make sure the wordpress service is running in one terminal, then in another terminal run:

```bash
npm run compose run console -- <... rest of command ...>
```

**Testing on nginx**

To run the local dev environment with nginx instead of apache, first make sure there is a `127.0.0.1 nginx` entry in your `/etc/hosts` file.

Then run the following command:

```bash
npm run compose up nginx fpm; npm run compose stop
```

Finally visit `http://nginx:3000/`.

Note: you cannot have both the apache and nginx services running simultaneously as they will try to use the same port.

**Accessing MariaDB/MySQL**

First ensure the database you want to inspect (mariadb or mysql) is the one that's currently being used by your local
environment. Then, while the local environment is running in one shell, open another and run the command:

```bash
npm run compose run mariadb mariadb -h mariadb -u root -p
```

Enter `pass` for the password.

(For mysql, replace instances of "mariadb" in the command with "mysql".)

#### Updating the Matomo core version

Matomo for WordPress embeds the self-hosted Matomo in the `app/` subdirectory. To update the version that is used,
run:

```
$ cd scripts
$ ./update-core.sh <matomo version number>
```

Note: this script will try to run a Matomo console command through docker, so you must have docker installed.

After it finishes, you can see what's changed via `git status`.

## Security
Security is a top priority at Matomo. As potential issues are discovered, we validate, patch and release fixes as quickly as we can. We have a security bug bounty program in place that rewards researchers for finding security issues and disclosing them to us.

[Learn more](https://matomo.org/security/) or check out our [HackerOne program](https://hackerone.com/matomo).

## Contact

Website: [matomo.org](https://matomo.org)

About us: [matomo.org/team/](https://matomo.org/team/)

Contact us: [matomo.org/contact/](https://matomo.org/contact/)
