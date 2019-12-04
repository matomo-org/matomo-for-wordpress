#!/usr/bin/env bash
VERSION="$1"

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
rm -rf $MATOMO_ROOT/*
rm -r matomo/ 2> /dev/null
unzip -o -q matomo-$VERSION.zip
cp -R matomo/* $MATOMO_ROOT
rm -r matomo/
rm matomo-$VERSION.zip
rm "How to install Matomo.html"
find $MATOMO_ROOT/misc/* -exec rm -rf {} +
rm -rf $MATOMO_ROOT/js/piwik.js
rm -rf $MATOMO_ROOT/tmp
rm -rf $MATOMO_ROOT/tests
rm -rf $MATOMO_ROOT/config/manifest.inc.php
# important to remove pclzip as it is shipped with WP and would need to use their lib
rm -rf $MATOMO_ROOT/vendor/piwik/decompress/libs/PclZip
mv bootstrap.php $MATOMO_ROOT/bootstrap.php

sed -i -e 's/libs\/bower_components\/jquery\/dist\/jquery.min.js/..\/..\/..\/..\/..\/..\/..\/wp-includes\/js\/jquery\/jquery.js/' $MATOMO_ROOT/plugins/Overlay/client/client.js

echo -e "Done!... "
echo -e "Should double check that path to jquery.js was updated in plugins/Overlay/client/client.js"
echo -e "Then need to manually generate the core assets js file and put it into the assets directory by executing below commands:"
echo -e "Then execute 'php ../app/console wordpress:generate-core-assets'"
echo -e "Then execute './remove_not_needed_assets.sh'"
