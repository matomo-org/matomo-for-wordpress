#!/usr/bin/env bash

set -e

cd /var/www/html

LATEST_WORDPRESS_VERSION=6.3.1 # can't use the github API, too easy to get rate limited

# install wp-cli.phar
if [ ! -f "/var/www/html/wp-cli.phar" ]; then
  curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /var/www/html/wp-cli.phar
fi
chmod +x /var/www/html/wp-cli.phar

# TODO: switch download to use wp-cli instead of just curling (also can use wp db create instead of raw php)
# install wordpress if not present
WORDPRESS_VERSION=${WORDPRESS_VERSION:-$LATEST_WORDPRESS_VERSION}
if [ ! -d "/var/www/html/$WORDPRESS_VERSION" ]; then
  WORDPRESS_URL="https://wordpress.org/wordpress-$WORDPRESS_VERSION.tar.gz"
  echo "installing wordpress $WORDPRESS_VERSION from $WORDPRESS_URL..."

  curl -O "$WORDPRESS_URL"
  tar -xvf "wordpress-$WORDPRESS_VERSION.tar.gz"
  mv wordpress "$WORDPRESS_VERSION"

  echo "wordpress installed!"
else
  echo "wordpress $WORDPRESS_VERSION already installed."
fi

echo "waiting for database..."
sleep 5 # wait for database
echo "done."

# create database if it does not already exist
WP_DB_NAME=$(echo "wp_matomo_$WORDPRESS_VERSION" | sed 's/\./_/g')
php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
\$pdo->exec('CREATE DATABASE IF NOT EXISTS \`$WP_DB_NAME\`');\
\$pdo->exec('GRANT ALL PRIVILEGES ON $WP_DB_NAME.* TO \'root\'@\'%\' IDENTIFIED BY \'pass\'');"

# setup wordpress config if not done so
if [ ! -f "/var/www/html/$WORDPRESS_VERSION/wp-config.php" ]; then
  cat > "/var/www/html/$WORDPRESS_VERSION/wp-config.php" <<EOF
<?php
define('DB_NAME', '$WP_DB_NAME');
define('DB_USER', 'root');
define('DB_PASSWORD', 'pass');
define('DB_HOST', getenv('WP_DB_HOST'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define( 'WP_DEBUG', false );

define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

define( 'FS_CHMOD_DIR', ( 0777 & ~ umask() ) );
define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
define( 'FS_METHOD', 'direct' );

define('FORCE_SSL', false);
define('FORCE_SSL_ADMIN', false);

define( 'MATOMO_ANALYTICS_FILE', __DIR__ . '/wp-content/plugins/matomo/matomo.php' );

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

# install wordpress
/var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_VERSION core install --url=localhost:3000 --title="Matomo for Wordpress Test" --admin_user=$WP_ADMIN_USER --admin_password=pass --admin_email=$WP_ADMIN_EMAIL
/var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_VERSION option set siteurl "http://localhost:3000/$WORDPRESS_VERSION"
/var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_VERSION option set home "http://localhost:3000/$WORDPRESS_VERSION"

# link matomo for wordpress volume as wordpress plugin
if [ ! -d "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/matomo" ]; then
  ln -s /var/www/html/matomo-for-wordpress "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/matomo"
fi

if [[ -d "/var/www/html/woocommerce-piwik-analytics" && ! -d "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/woocommerce-piwik-analytics" ]]; then
  ln -s /var/www/html/woocommerce-piwik-analytics /var/www/html/$WORDPRESS_VERSION/wp-content/plugins/woocommerce-piwik-analytics
fi

/var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_VERSION plugin activate matomo

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
    if (preg_match('/^\d+\.\d+\.\d+\$/', \$folder) && is_dir(\$folder)) {
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
  /var/www/html/wp-cli.phar --allow-root --path=/var/www/html/$WORDPRESS_VERSION plugin install --activate $VERSION_ARGUMENT $PLUGIN || true
done

# setup woocommerce if requested
if [[ ! -z "$WOOCOMMERCE" && ! -d "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/woocommerce" ]]; then
  # install woocommerce and stripe payment gateway
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root plugin install woocommerce woocommerce-gateway-stripe --activate

  # install oceanwp
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root theme install oceanwp --activate

  # add 5 test products
  IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/6.3.1/wp-content/plugins/matomo/tests/resources/products/ceiling_fan.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER wc product create --name="Ceiling Fan" --short_description="Pink butterfly ceiling fan" --description="Pink butterfly ceiling fan" --slug="ceiling-fan-pink" --regular_price="309.99" --sku="PROD_1" --images="[{\"id\":$IMAGE_ID}]" || true

  IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/6.3.1/wp-content/plugins/matomo/tests/resources/products/film_projector.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER wc product create --name="Film Projector Lens" --short_description="A film projector lens" --description="A film projector lens" --slug="film-projector-lens" --regular_price="439.89" --sku="PROD_2" --images="[{\"id\":$IMAGE_ID}]" || true

  IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/6.3.1/wp-content/plugins/matomo/tests/resources/products/monitors.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER wc product create --name="Folding monitors" --short_description="Folding monitors, three monitors combined" --description="Folding monitors, three monitors combined" --slug="folding-monitors" --regular_price="286.00" --sku="PROD_3" --images="[{\"id\":$IMAGE_ID}]" || true

  IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/6.3.1/wp-content/plugins/matomo/tests/resources/products/spotlight.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER wc product create --name="Spotlight" --short_description="Single hanging spotlight" --description="Single hanging spotlight, fixed, not portable" --slug="spotlight" --regular_price="279.99" --sku="PROD_4" --images="[{\"id\":$IMAGE_ID}]" || true

  IMAGE_ID=$( /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER media import "/var/www/html/6.3.1/wp-content/plugins/matomo/tests/resources/products/tripod.jpg" | grep -o 'attachment ID [0-9][0-9]*' | awk '{print $3}' )
  /var/www/html/wp-cli.phar --path=/var/www/html/$WORDPRESS_VERSION --allow-root --user=$WP_ADMIN_USER wc product create --name="Small camera tripod in red" --short_description="Small camera tripod in red" --description="Small portable tripod for your camera. Available colors: red." --slug="camera-tripod-small" --regular_price="13.99" --sku="PROD_5" --images="[{\"id\":$IMAGE_ID}]" || true
fi

# make sure the files can be edited outside of docker (for easier debugging)
# TODO: file permissions becoming a pain, shouldn't have to deal with this for dev env. this works for now though.
mkdir -p /var/www/html/$WORDPRESS_VERSION/wp-content/uploads
find "/var/www/html/$WORDPRESS_VERSION" -path "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/matomo" -prune -o -exec chown "${UID:-1000}:${GID:-1000}" {} +
find "/var/www/html/$WORDPRESS_VERSION" -path "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/matomo" -prune -o -exec chmod 0777 {} +
chmod -R 0777 "/var/www/html/$WORDPRESS_VERSION/wp-content/plugins/matomo/app/tmp" "/var/www/html/index.php" "/usr/local/etc/php/conf.d"

if ! which apache2-foreground &> /dev/null; then
  # TODO: is it possible to use wp-cli for this?
  # make sure home url points to 'nginx' service
  php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
  \$pdo->exec('UPDATE \`$WP_DB_NAME\`.wp_options SET option_value = REPLACE(option_value, \'localhost\', \'nginx\') WHERE option_name IN (\'home\', \'siteurl\')');" || true

  php-fpm "$@"
else
  # make sure home url points to 'localhost'
  php -r "\$pdo = new PDO('mysql:host=$WP_DB_HOST', 'root', 'pass');
  \$pdo->exec('UPDATE \`$WP_DB_NAME\`.wp_options SET option_value = REPLACE(option_value, \'nginx\', \'localhost\') WHERE option_name IN (\'home\', \'siteurl\')');" || true

  apache2-foreground "$@"
fi
