#!/usr/bin/env bash

# TODO: this script could probably be cleaned up. very low priority task though.

set -e

cd /var/www/html

if [[ "$1" = "bash" ]]; then
  "$@"
  exit $?
fi

function export_global() {
  export WP_CLI_CACHE_DIR=/.wp-cli

  # http serves a single offer, whereas https serves multiple. we only want one
  export LATEST_WORDPRESS_VERSION=$( php -r 'echo @json_decode(file_get_contents("http://api.wordpress.org/core/version-check/1.7/"), true)["offers"][0]["version"];' );
  if [[ -z "$LATEST_WORDPRESS_VERSION" ]]; then
    echo "Latest WordPress version could not be found"
    exit 1
  fi

  if [[ "$WORDPRESS_VERSION" = "latest" || -z "$WORDPRESS_VERSION" ]]; then
    export WORDPRESS_VERSION="$LATEST_WORDPRESS_VERSION"
  fi

  export WORDPRESS_FOLDER=${WORDPRESS_FOLDER:-$WORDPRESS_VERSION}
  export WORDPRESS_FOLDER_BASE=$WORDPRESS_FOLDER
}

function export_install_dependent() {
  ARG_SUFFIX="$1"
  export WORDPRESS_FOLDER="$WORDPRESS_FOLDER_BASE${ARG_SUFFIX}"

  export WP_DB_NAME=$(echo "wp_matomo_$WORDPRESS_FOLDER" | sed 's/\./_/g' | sed 's/-/_/g')

  export WP_TESTS_DIR=/var/www/html/$WORDPRESS_FOLDER/wp-test # used for setting up for tests and running phpunit
}

function init_wpload_dir_file() {
  echo "<?php # /var/www/html/$WORDPRESS_FOLDER/wp-load.php" > /var/www/html/matomo.wpload_dir.php || true
}

function handle_cli_command() {
  EXECUTE_TARGET="$1"
  EXECUTE_ARGS="${@:2}"

  if [[ "$MULTISITE" == "1" ]]; then
    WP_FOLDER_SUFFIX="-multi"
  fi

  export_install_dependent $WP_FOLDER_SUFFIX
  init_wpload_dir_file

  echo "Using WordPress install $WORDPRESS_FOLDER."
  echo

  if [[ "$EXECUTE_TARGET" = "wp" ]]; then
    /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER $EXECUTE_ARGS
    exit $?
  elif [[ "$EXECUTE_TARGET" = "matomo:console" ]]; then
    cd /var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo/app
    ./console $EXECUTE_ARGS
    exit $?
  elif [[ "$EXECUTE_TARGET" = "phpunit" ]]; then
    cd /var/www/html/matomo-for-wordpress

    php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
    \$pdo->exec('DROP DATABASE IF EXISTS \`${WP_DB_NAME}_test\`');\
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${WP_DB_NAME}_test\`');\
    \$pdo->exec('GRANT ALL PRIVILEGES ON ${WP_DB_NAME}_test.* TO \'root\'@\'%\' IDENTIFIED BY \'pass\'');"

    ./vendor/bin/phpunit $EXECUTE_ARGS
    exit $?
  else
    cd /var/www/html/matomo-for-wordpress
    "$EXECUTE_TARGET" $EXECUTE_ARGS
    exit $?
  fi
}

function install_wordpress() {
  MULTISITE="$1"
  WP_FOLDER_SUFFIX="$2"

  chmod 777 "/.wp-cli"

  export_install_dependent $WP_FOLDER_SUFFIX
  init_wpload_dir_file

  # install wp-cli.phar
  if [ ! -f "/var/www/html/wp-cli.phar" ]; then
    curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /var/www/html/wp-cli.phar
  fi
  chmod +x /var/www/html/wp-cli.phar

  # TODO: switch download to use wp-cli instead of just curling (also can use wp db create instead of raw php)
  # install wordpress if not present
  if [ ! -d "/var/www/html/$WORDPRESS_FOLDER" ]; then
    WORDPRESS_URL="https://wordpress.org/wordpress-$WORDPRESS_VERSION.zip"
    if [ "$WORDPRESS_VERSION" = "trunk" ]; then
      WORDPRESS_URL="https://wordpress.org/nightly-builds/wordpress-latest.zip"
    fi

    echo "installing wordpress $WORDPRESS_VERSION from $WORDPRESS_URL to /var/www/html/$WORDPRESS_FOLDER/... (multisite = $MULTISITE)"

    curl "$WORDPRESS_URL" > "wordpress-$WORDPRESS_VERSION.zip"

    rm -rf wordpress
    unzip -q "wordpress-$WORDPRESS_VERSION.zip"
    mv wordpress "$WORDPRESS_FOLDER"

    echo "wordpress installed!"
  else
    echo "wordpress $WORDPRESS_VERSION already installed at /var/www/html/$WORDPRESS_FOLDER/."
  fi

  # if requested, drop the database for a clean install (used mainly for automated tests)
  if [[ ! -z "$RESET_DATABASE" ]]; then
    echo "dropping existing database..."

    php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
    \$pdo->exec('DROP DATABASE IF EXISTS \`$WP_DB_NAME\`');"

    rm /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads/matomo/config/config.ini.php || true
    rm /var/www/html/$WORDPRESS_FOLDER/apppassword || true
  fi

  # create database if it does not already exist
  php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
  \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`$WP_DB_NAME\`');\
  \$pdo->exec('GRANT ALL PRIVILEGES ON $WP_DB_NAME.* TO \'root\'@\'%\' IDENTIFIED BY \'pass\'');"

  # setup wordpress config if not done so
  if [ ! -f "/var/www/html/$WORDPRESS_FOLDER/wp-config.php" ]; then
    if [[ "$MULTISITE" = "1" ]]; then
      MULTISITE_CONFIG="
  define( 'WP_ALLOW_MULTISITE', true );
  define( 'MULTISITE', true );
  define( 'SUBDOMAIN_INSTALL', false );
  define( 'DOMAIN_CURRENT_SITE', 'localhost' . ($PORT === 80 ? '' : ':$PORT') );
  define( 'PATH_CURRENT_SITE', '/$WORDPRESS_FOLDER/' );
  define( 'SITE_ID_CURRENT_SITE', 1 );
  define( 'BLOG_ID_CURRENT_SITE', 1 );
  "
    fi

    WP_DEBUG="${WP_DEBUG:-false}"
    WP_DEBUG_LOG="${WP_DEBUG_LOG:-false}"
    WP_DEBUG_DISPLAY="${WP_DEBUG_DISPLAY:-true}"
    cat > "/var/www/html/$WORDPRESS_FOLDER/wp-config.php" <<EOF
<?php
define( 'DB_NAME', '$WP_DB_NAME' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'pass' );
define( 'DB_HOST', getenv('WP_DB_HOST') );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'WP_DEBUG', $WP_DEBUG );
define( 'WP_DEBUG_LOG', $WP_DEBUG_LOG );
define( 'WP_DEBUG_DISPLAY', $WP_DEBUG_DISPLAY );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
$MULTISITE_CONFIG

define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

define('FORCE_SSL', false);
define('FORCE_SSL_ADMIN', false);

# mail settings
define( 'WPMS_ON', true );
define( 'WPMS_MAILER', 'smtp' );
define( 'WPMS_SMTP_HOST', 'mailer' );
define( 'WPMS_SMTP_PORT', 1025 );
define( 'WPMS_SSL', 'none' );
define( 'WPMS_SMTP_AUTH', false );
define( 'WPMS_SMTP_AUTOTLS', true );
define( 'WPMS_SMTP_USER', '' );
define( 'WPMS_SMTP_PASS', '' );

define( 'MATOMO_ANALYTICS_FILE', __DIR__ . '/wp-content/plugins/matomo/matomo.php' );
define( 'MATOMO_LOCAL_ENVIRONMENT', 1 );

if ( ! empty( \$_SERVER['HTTP_HOST'] )
  && preg_match( '/\.ngrok-free\.app$/', \$_SERVER['HTTP_HOST'] )
) {
  \$folder = basename( __DIR__ );
  define('WP_HOME', 'https://' . \$_SERVER['HTTP_HOST'] . '/' . \$folder );
  define('WP_SITEURL', 'https://' . \$_SERVER['HTTP_HOST'] . '/' . \$folder );
} else {
  \$folder = basename( __DIR__ );
  define('WP_HOME', 'http://localhost/' . \$folder);
  define('WP_SITEURL', 'http://localhost/' . \$folder);
}

if ( isset( \$_SERVER['HTTP_X_FORWARDED_PROTO'] )
  && strpos( \$_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) !== false
) {
  \$_SERVER['HTTPS'] = 'on';
}

\$table_prefix = 'wp_';

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
  define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
EOF

    echo "setup wp-config.php!"
  fi

  HOSTNAME=localhost
  if [[ $PORT != "80" ]]; then
    HOSTNAME="$HOSTNAME:$PORT"
  fi

  # install wordpress
  echo "installing wordpress"
  if [[ "$MULTISITE" = "1" ]]; then
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER core multisite-install --url=$HOSTNAME --title="Matomo for Wordpress Test" --admin_user=$WP_ADMIN_USER --admin_password=pass --admin_email=$WP_ADMIN_EMAIL

    cat > "/var/www/html/$WORDPRESS_FOLDER/.htaccess" <<EOF
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /$WORDPRESS_FOLDER/
RewriteRule ^index\.php\$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin\$ \$1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) \$2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)\$ \$2 [L]
RewriteRule . index.php [L]
EOF

    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER option set siteurl "http://$HOSTNAME/$WORDPRESS_FOLDER"
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER option set home "http://$HOSTNAME/$WORDPRESS_FOLDER"

    # create test sites
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER site create --slug=test2 || true
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER site create --slug=test3 || true
  else
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER core install --url="$HOSTNAME" --title="Matomo for Wordpress Test" --admin_user=$WP_ADMIN_USER --admin_password=pass --admin_email=$WP_ADMIN_EMAIL

    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER option set siteurl "http://$HOSTNAME/$WORDPRESS_FOLDER"
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER option set home "http://$HOSTNAME/$WORDPRESS_FOLDER"
  fi

  # set permalink structure to /%postname%/
  /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER rewrite structure '/%postname%/'

  # add .htaccess files required for the above permalink structure change
  if [[ ! -f "/var/www/html/$WORDPRESS_FOLDER/.htaccess" ]]; then
    if [[ "$MULTISITE" == "1" ]]; then
      cat > "/var/www/html/$WORDPRESS_FOLDER/.htaccess" <<EOF
# BEGIN WordPress Multisite
# Using subfolder network type: https://wordpress.org/documentation/article/htaccess/#multisite

RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . $WORDPRESS_FOLDER/index.php [L]

# END WordPress Multisite
EOF
    else
      cat > "/var/www/html/$WORDPRESS_FOLDER/.htaccess" <<EOF
# BEGIN WordPress

RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /$WORDPRESS_FOLDER/index.php [L]

# END WordPress
EOF
    fi
  fi

  # link matomo for wordpress volume as wordpress plugin
  if [[ "$INSTALLING_FROM_ZIP" != "1" ]]; then
    if [[ ! -d "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" ]]; then
      ln -s /var/www/html/matomo-for-wordpress "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo"
    fi
  else
    echo "installing latest stable matomo..."

    if [ -L "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" ]; then
      rm "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" || true
    else
      rm -r "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" || true
    fi

    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin install --activate "https://downloads.wordpress.org/plugin/matomo.latest-stable.zip"
    chown -R "${FIlE_OWNER_USERID:-1000}:${GID:-1000}" /var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo
  fi

  if [[ "$MULTISITE" = "1" ]]; then
    COMMAND_SUFFIX=" --network"
  fi

  /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin activate matomo $COMMAND_SUFFIX
  /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER matomo install

  # extra actions required during tests
  if [ "$WORDPRESS_FOLDER" = "test" ]; then
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER matomo globalSetting set track_mode default
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER matomo sync sites

    # update site created date for e2e tests
    php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
    \$pdo->exec('UPDATE \`${WP_DB_NAME}\`.wp_matomo_site SET ts_created = \"2023-01-01 00:00:00\"');"
  fi

  # add index.php file listing available installs to root /var/www/html
  if [ ! -f "/var/www/html/index.php" ]; then
    cat > "/var/www/html/index.php" <<EOF
<html lang="en">
<head>
<style>
  body { padding: 0; margin: 0; font-family: sans-serif; background-color: #f8f8f8; height: calc(100%); display: flex; flex-direction: column; }
  header { background-color: lightskyblue; padding: 10px 0; }
  h1 { text-align: center; margin: 0; color: #fafafa; }
  .content { width: 800px; margin: 20px auto; background-color: white; padding: 20px; flex: 1; overflow-y: auto; }
</style>
</head>
<body>
<header><h1>Available Wordpress Installs</h1></header>
<div class="content">
<ul>
<?php
  foreach (scandir(__DIR__) as \$folder) {
    if (is_dir(\$folder) && is_file(\$folder . '/wp-load.php')) {
?>
<li><a href="<?php echo \$folder; ?>/"><?php echo \$folder; ?></a></li>
<?php
    }
  }
?>
</ul>
</div>
</body>
</html>
EOF
  fi

  if [[ "$WITHOUT_MARKETPLACE" = "1" ]]; then
    rm -f "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo-marketplace-for-wordpress" || true
  else
    if [ ! -d "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo-marketplace-for-wordpress" ]; then
      echo "installing matomo marketplace"
      ln -s /var/www/html/matomo-for-wordpress/marketplace "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo-marketplace-for-wordpress"
      /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin activate matomo-marketplace-for-wordpress
    else
      /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin activate matomo-marketplace-for-wordpress
    fi
  fi

  # other plugins used during tests
  if [ ! -d "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/speculation-rules" ] && php -r "exit('$WORDPRESS_VERSION' === 'trunk' || version_compare('$WORDPRESS_VERSION', '6.6', '>=') ? 0 : 1);"; then
    echo "installing speculation-rules"

    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin install --activate speculation-rules
  fi

  if [ ! -d "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/wp-statistics" ]; then
    echo "installing wp-statistics"

    WP_STATS_VERSION=""
    if php -r "exit('$WORDPRESS_VERSION' !== 'trunk' && version_compare('$WORDPRESS_VERSION', '5.3', '<') ? 0 : 1);"; then
      WP_STATS_VERSION="--version=13.2.16"
    elif php -r "exit('$WORDPRESS_VERSION' !== 'trunk' && version_compare(PHP_VERSION, '8.0', '<') ? 0 : 1);"; then
      WP_STATS_VERSION="--version=14.5.2"
    fi

    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin install --activate wp-statistics $WP_STATS_VERSION
  fi

  # download WP_PLUGINS plugins if not present
  for PLUGIN_VERSION in $WP_PLUGINS
  do
    PLUGIN_VERSION_ARRAY=(${PLUGIN_VERSION//:/ })
    PLUGIN=${PLUGIN_VERSION_ARRAY[0]}
    VERSION=${PLUGIN_VERSION_ARRAY[1]}

    if [ "$PLUGIN" = "matomo" ]; then
      echo "skipping matomo plugin install"
      continue
    fi

    if [[ ! -z "$VERSION" ]]; then
      VERSION_ARGUMENT="--version=$VERSION"
    fi

    echo "installing plugin $PLUGIN $VERSION_ARGUMENT"
    /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_FOLDER plugin install --activate $VERSION_ARGUMENT $PLUGIN || true
  done

  # install oceanwp
  echo "installing oceanwp..."
  if php -r 'exit(version_compare(PHP_VERSION, "7.3", "<") ? 0 : 1);'; then
    OCEANWP_VERSION="--version=3.5.5"
  fi
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root theme install oceanwp --activate $OCEANWP_VERSION

  # setup woocommerce if requested
  if [[ "$WOOCOMMERCE" == "1" ]]; then
    if [[ ! -d "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/woocommerce" ]]; then
      echo "setting up woocommerce..."

      if php -r 'exit(version_compare(PHP_VERSION, "7.3", "<") ? 0 : 1);'; then
        WOOCOMMERCE_VERSION="--version=7.6.1"
      fi

      # install woocommerce
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root plugin install woocommerce --activate $WOOCOMMERCE_VERSION
    else
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root plugin activate woocommerce
    fi

    function wc_product_exists() {
      PROD_SKU=$1
      PROD_COUNT=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product list --sku=$PROD_SKU --format=count )
      [ $PROD_COUNT != "0" ]
    }

    # add 5 test products
    echo "Generating test products..."

    if ! wc_product_exists PROD_1; then
      IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/matomo-for-wordpress/tests/resources/products/ceiling_fan.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product create --name="Ceiling Fan" --short_description="Pink butterfly ceiling fan" --description="Pink butterfly ceiling fan" --slug="ceiling-fan-pink" --regular_price="309.99" --sku="PROD_1" --images="[{\"id\":$IMAGE_ID}]" || true
    fi

    if ! wc_product_exists PROD_2; then
      IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/matomo-for-wordpress/tests/resources/products/film_projector.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product create --name="Film Projector Lens" --short_description="A film projector lens" --description="A film projector lens" --slug="film-projector-lens" --regular_price="439.89" --sku="PROD_2" --images="[{\"id\":$IMAGE_ID}]" || true
    fi

    if ! wc_product_exists PROD_3; then
      IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/matomo-for-wordpress/tests/resources/products/monitors.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product create --name="Folding monitors" --short_description="Folding monitors, three monitors combined" --description="Folding monitors, three monitors combined" --slug="folding-monitors" --regular_price="286.00" --sku="PROD_3" --images="[{\"id\":$IMAGE_ID}]" || true
    fi

    if ! wc_product_exists PROD_4; then
      IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/matomo-for-wordpress/tests/resources/products/spotlight.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product create --name="Spotlight" --short_description="Single hanging spotlight" --description="Single hanging spotlight, fixed, not portable" --slug="spotlight" --regular_price="279.99" --sku="PROD_4" --images="[{\"id\":$IMAGE_ID}]" || true
    fi

    if ! wc_product_exists PROD_5; then
      IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/matomo-for-wordpress/tests/resources/products/tripod.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER wc product create --name="Small camera tripod in red" --short_description="Small camera tripod in red" --description="Small portable tripod for your camera. Available colors: red." --slug="camera-tripod-small" --regular_price="13.99" --sku="PROD_5" --images="[{\"id\":$IMAGE_ID}]" || true
    fi
  fi

  # setup wp-mail-smtp
  if [[ "$WORDPRESS_VERSION" != "trunk" ]] && php -r "exit(version_compare('$WORDPRESS_VERSION', '5.5', '<') ? 0 : 1);"; then
    WP_MAIL_SMTP_VERSION="--version=3.11.1"
  elif php -r "exit(version_compare('$PHP_VERSION', '7.4', '<') ? 0 : 1);"; then
    WP_MAIL_SMTP_VERSION="--version=4.4.0"
  fi

  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root plugin install --activate $WP_MAIL_SMTP_VERSION wp-mail-smtp

  # create WordPress app password for matomo API
  if [[ ! -f /var/www/html/$WORDPRESS_FOLDER/apppassword ]]; then
    if /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER user application-password exists root wp_rest; then
      echo "removing existing app password..."

      APP_PASSWORD_UUID=$(/var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER user application-password list root --fields=name,uuid --format=csv | grep wp_rest | awk -F ',' '{ print $2 }')
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER user application-password delete root $APP_PASSWORD_UUID
    fi

    echo "creating new app password..."
    APP_PASSWORD=$(/var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER user application-password create --porcelain root wp_rest || true) # can fail on older WordPress versions
    echo $APP_PASSWORD > /var/www/html/$WORDPRESS_FOLDER/apppassword
  fi

  # add some test pages and posts
  echo "generating test pages/posts..."

  function wp_post_exists() {
    POST_TYPE="$1"
    POST_NAME="$2"
    POST_COUNT=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER post list --post_type="$POST_TYPE" | grep "$POST_NAME" | wc -l )
    [ $POST_COUNT != "0" ]
  }

  function wp_new_post() {
    POST_TYPE=$1
    POST_NAME=$2
    POST_TITLE=$3
    HAS_OPT_OUT="${4:-0}"
    POST_CONTENT="
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultrices tellus eu ante finibus, ac finibus nunc interdum. Donec arcu ante, eleifend vel mollis at, varius et mi. Nullam sagittis justo sit amet arcu mattis, eu rutrum ligula imperdiet. Maecenas condimentum libero sem, scelerisque porttitor magna viverra at. Vivamus sollicitudin facilisis maximus. Nulla vitae eros tristique eros gravida tempor. Nunc eleifend tortor ac nisl porttitor rhoncus. Quisque vestibulum suscipit ligula, sed pulvinar tellus bibendum ac. Ut porta gravida arcu in eleifend.</p>

<p>Suspendisse venenatis varius congue. Morbi varius, velit sit amet imperdiet pharetra, orci ex molestie leo, a fermentum lorem est sit amet mi. Quisque dolor dolor, mattis finibus interdum nec, interdum ut mauris. Duis cursus lectus id turpis ornare mollis. Donec posuere eget ipsum vitae suscipit. Phasellus ac faucibus nisl, laoreet lacinia nisi. Nunc est turpis, sagittis vitae tempus nec, efficitur et dui. Quisque tincidunt ante at tortor tincidunt porttitor. Donec at vulputate neque. Mauris aliquet non sapien nec convallis. Mauris scelerisque gravida tortor. Pellentesque in pulvinar arcu. Maecenas ante sem, mollis sed augue vitae, consequat sollicitudin ex.</p>
";

    if [ "$HAS_OPT_OUT" = "1" ]; then
      POST_CONTENT="$POST_CONTENT

[matomo_opt_out_form]

[matomo_opt_out]
"
    fi

    if ! wp_post_exists "$POST_TYPE" "$POST_NAME"; then
      /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_FOLDER --allow-root --user=$WP_ADMIN_USER post create --post_type="$POST_TYPE" --post_name="$POST_NAME" --post_title="$POST_TITLE" --post_content="$POST_CONTENT" --post_status=publish
    fi
  }

  wp_new_post page "about" "About"
  wp_new_post page "contact-us" "Contact Us" 1
  wp_new_post page "learn-more" "Learn More"

  wp_new_post post "march-update" "March Update"
  wp_new_post post "10-new-ways-to-whatever" "Learn 10 exciting new ways to WHATEVER!"
  wp_new_post post "why-use-our-stuff" "Why you should be using our stuff and whatnot!"

  # setup everything required for unit tests
  if [ "$WORDPRESS_VERSION" = "trunk" ]; then
    WORDPRESS_SVN_FOLDER="trunk"
  else
    WORDPRESS_SVN_FOLDER="tags/$WORDPRESS_VERSION"
  fi

  if [[ ! -d "$WP_TESTS_DIR/includes" || ! -d "$WP_TESTS_DIR/data" ]];
  then
      mkdir -p $WP_TESTS_DIR
      if [[ ! -d "$WP_TESTS_DIR/includes" ]]; then
        echo "checking out phpunit includes..."
        svn co --quiet https://develop.svn.wordpress.org/$WORDPRESS_SVN_FOLDER/tests/phpunit/includes/ $WP_TESTS_DIR/includes
      fi

      if [[ ! -d "$WP_TESTS_DIR/data" ]]; then
        echo "checking out phpunit data..."
        svn co --quiet https://develop.svn.wordpress.org/$WORDPRESS_SVN_FOLDER/tests/phpunit/data/ $WP_TESTS_DIR/data
      fi
  fi

  if [ ! -f $WP_TESTS_DIR/wp-tests-config.php ]; then
    curl https://develop.svn.wordpress.org/$WORDPRESS_SVN_FOLDER/wp-tests-config-sample.php > "$WP_TESTS_DIR"/wp-tests-config.php
    # remove all forward slashes in the end
    sed -i "s:dirname( __FILE__ ) . '/src/':'/var/www/html/$WORDPRESS_FOLDER/':" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s/youremptytestdbnamehere/${WP_DB_NAME}_test/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s/yourusernamehere/root/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s/yourpasswordhere/pass/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s|'localhost'|getenv('WP_DB_HOST')|" "$WP_TESTS_DIR"/wp-tests-config.php
    cat >> "$WP_TESTS_DIR/wp-tests-config.php" <<EOF
# mail settings
define( 'WPMS_ON', true );
define( 'WPMS_MAILER', 'smtp' );
define( 'WPMS_SMTP_HOST', 'mailer' );
define( 'WPMS_SMTP_PORT', 1025 );
define( 'WPMS_SSL', 'none' );
define( 'WPMS_SMTP_AUTH', false );
define( 'WPMS_SMTP_AUTOTLS', true );
define( 'WPMS_SMTP_USER', '' );
define( 'WPMS_SMTP_PASS', '' );
EOF
  fi

  # create unit test database if it does not already exist
  echo "creating test database..."
  php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
  \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${WP_DB_NAME}_test\`');\
  \$pdo->exec('GRANT ALL PRIVILEGES ON ${WP_DB_NAME}_test.* TO \'root\'@\'%\' IDENTIFIED BY \'pass\'');"

  # install GeoLite2 for matomo/wp-statisitcs
  if [ ! -f /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads/matomo/GeoIP2-City.mmdb ]; then
    echo "downloading GeoLite2-City.mmdb..."

    mkdir -p /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads/matomo
    curl 'https://cdn.jsdelivr.net/npm/geolite2-city/GeoLite2-City.mmdb.gz' > /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads/matomo/GeoIP2-City.mmdb
  fi

  # set allow_wp_app_password_auth tracker config, used in tests
  echo "set allow_wp_app_password_auth config..."
  php /var/www/html/matomo-for-wordpress/app/console config:set --section=Tracker --key=allow_wp_app_password_auth --value=1

  # add test-utility-plugin used in UI tests
  mkdir -p /var/www/html/$WORDPRESS_FOLDER/wp-content/mu-plugins
  cp /var/www/html/matomo-for-wordpress/tests/e2e/resources/test-utility-plugin/test-utility-plugin.php /var/www/html/$WORDPRESS_FOLDER/wp-content/mu-plugins/test-utility-plugin.php

  FIlE_OWNER_USERID=$UID
  if [[ -z "$FIlE_OWNER_USERID" || "$FIlE_OWNER_USERID" == "0" ]]; then
    FIlE_OWNER_USERID=1000
  fi

  # make sure the files can be edited outside of docker (for easier debugging)
  # TODO: file permissions becoming a pain, shouldn't have to deal with this for dev env. this works for now though.
  touch /var/www/html/$WORDPRESS_FOLDER/wp-content/debug.log /var/www/html/matomo.wpload_dir.php
  mkdir -p /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads/matomo/tmp/cache/tracker /var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo/app/tmp
  chown -R "${FIlE_OWNER_USERID:-1000}:${GID:-1000}" /var/www/html/$WORDPRESS_FOLDER/wp-content/uploads
  find "/var/www/html/$WORDPRESS_FOLDER" -path "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" -prune -o -exec chown "${FIlE_OWNER_USERID:-1000}:${GID:-1000}" {} +
  find "/var/www/html/$WORDPRESS_FOLDER" -path "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo" -prune -o -exec chmod 0777 {} +
  chmod -R 0777 "/var/www/html/$WORDPRESS_FOLDER/wp-content/plugins/matomo/app/tmp" "/var/www/html/index.php" "/usr/local/etc/php/conf.d" "/var/www/html/$WORDPRESS_FOLDER/wp-content/debug.log" /var/www/html/matomo.wpload_dir.php

  echo "finish wordpress install (multisite = $MULTISITE)"
}

function start_webserver() {
  a2enmod rewrite || true

  if ! which apache2-foreground &> /dev/null; then
    # TODO: is it possible to use wp-cli for this?
    # make sure home url points to 'nginx' service
    php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
    \$pdo->exec('UPDATE \`$WP_DB_NAME\`.wp_options SET option_value = REPLACE(option_value, \'localhost\', \'nginx\') WHERE option_name IN (\'home\', \'siteurl\')');" || true

    php-fpm "$@"
  else
    # set port to exposed port so we can make server side requests to localhost
    sed -i "s/Listen 80\\>/Listen $PORT/" /etc/apache2/ports.conf

    # make sure home url points to 'localhost'
    php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
    \$pdo->exec('UPDATE \`$WP_DB_NAME\`.wp_options SET option_value = REPLACE(option_value, \'nginx\', \'localhost\') WHERE option_name IN (\'home\', \'siteurl\')');" || true

    usermod -u "${FIlE_OWNER_USERID:-1000}" www-data
    apache2-foreground "$@"
  fi
}

function wait_for_database() {
  echo "waiting for database..."
  while ! php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');\$pdo->exec('SELECT VERSION()');" > /dev/null
  do
    sleep 2
  done
  echo "database running."
}

export_global

if [[ "$EXECUTE_CLI" = "1" ]]; then
  handle_cli_command "$@"
fi

wait_for_database

# install normal wordpress + multisite wordpress
install_wordpress 0
install_wordpress 1 -multi

touch /var/www/html/$WORDPRESS_FOLDER_BASE/setup_finished || true
touch /var/www/html/$WORDPRESS_FOLDER_BASE-multi/setup_finished || true
chmod 777 /var/www/html/$WORDPRESS_FOLDER_BASE/setup_finished || true
chmod 777 /var/www/html/$WORDPRESS_FOLDER_BASE-multi/setup_finished || true

if [[ "$INSTALL_ONLY" != "1" ]]; then
  start_webserver "$@"
fi
