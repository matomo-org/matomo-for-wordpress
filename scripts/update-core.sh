#!/usr/bin/env bash

set -e

VERSION="$1"

function catch_error() {
    status=$? bc="$BASH_COMMAND" ln="$BASH_LINENO"
    echo ">> Command '$bc' failed on line $ln and status is $status <<" >&2
    exit $status
}

trap catch_error ERR

# report error and exit
function die() {
        echo -e "$0: $1"
        exit 2
}

[ ! -z "$VERSION" ] || die "Expected a Matomo version number as a parameter"

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
MATOMO_ROOT="$SCRIPTPATH/../app/";

cd $SCRIPTPATH
echo -e "Upgrading to $VERSION"

URL="https://builds.matomo.org/matomo-$VERSION.zip"

echo -e "Downloading $URL..."
wget $URL -P "$SCRIPTPATH" || die "Got an error while downloading this Matomo version"

cp $MATOMO_ROOT/bootstrap.php bootstrap.php
cp $MATOMO_ROOT/.htaccess .htaccess
rm -r "${MATOMO_ROOT:?}/"*
rm -rf matomo/ 2> /dev/null
unzip -o -q matomo-$VERSION.zip
cp -R matomo/* $MATOMO_ROOT
rm -r matomo/
rm matomo-$VERSION.zip
rm "How to install Matomo.html"

if [ ! -z "$MATOMO_SCOPER_PATH" ]; then
  echo "Running matomo-scoper..."
  php "$MATOMO_SCOPER_PATH/bin/matomo-scoper" scope "$MATOMO_ROOT" --rename-references
else
  echo "MATOMO_SCOPER_PATH not defined, skipping scoping."
fi

find $MATOMO_ROOT/misc/* -exec rm -rf {} +
rm -r $MATOMO_ROOT/js/piwik.js
rm -r $MATOMO_ROOT/CONTRIBUTING.md
rm -r $MATOMO_ROOT/CHANGELOG.md
rm -r $MATOMO_ROOT/plugins/Morpheus/fonts/selection.json
rm -r $MATOMO_ROOT/lang/README.md
rm -rf $MATOMO_ROOT/tmp
rm -r $MATOMO_ROOT/tests
rm -r $MATOMO_ROOT/config/manifest.inc.php
# remove the plugins also from auto loader so they can be installed through marketplace
rm -rf $MATOMO_ROOT/plugins/CustomVariables
rm -rf $MATOMO_ROOT/plugins/Provider
awk '!/Plugins\\\\Provider/' $MATOMO_ROOT/vendor/composer/autoload_classmap.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_classmap.php
awk '!/Plugins\\\\Provider/' $MATOMO_ROOT/vendor/composer/autoload_static.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_static.php
awk '!/Plugins\\\\CustomVariables/' $MATOMO_ROOT/vendor/composer/autoload_classmap.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_classmap.php
awk '!/Plugins\\\\CustomVariables/' $MATOMO_ROOT/vendor/composer/autoload_static.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_static.php
find $MATOMO_ROOT/core/Updates -name '*.php' ! -name '3.12.0-b1.php' ! -name '3.12.0-b7.php' ! -name '4.*' ! -name '5.*' ! -name '6.*' ! -name '7.*' -exec rm -rf {} +
# important to remove pclzip as it is shipped with WP and would need to use their lib
rm -rf $MATOMO_ROOT/vendor/piwik/decompress/libs/PclZip
mv bootstrap.php $MATOMO_ROOT/bootstrap.php
mv .htaccess $MATOMO_ROOT/.htaccess

sed -i -e 's/node_modules\/jquery\/dist\/jquery.min.js/..\/..\/..\/..\/..\/..\/..\/wp-includes\/js\/jquery\/jquery.js/' $MATOMO_ROOT/plugins/Overlay/client/client.js
if grep -Fq "/wp-includes/js/jquery/jquery.js" $MATOMO_ROOT/plugins/Overlay/client/client.js
then
    echo -e "jquery.js replaced correctly"
else
    echo -e "WordPress jquery was not replaced. There is an error."
fi
echo -e "Done!... "
echo -e "Now execute 'php ../app/console wordpress:generate-core-assets' or 'npm run compose run console wordpress:generate-core-assets'"
echo -e "Then execute 'php ../app/console wordpress:generate-lang-files' or 'npm run compose run console wordpress:generate-lang-files'"
echo -e "Then execute './remove_not_needed_assets.sh'"
