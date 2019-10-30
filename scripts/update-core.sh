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

rm -r matomo/ 2> /dev/null
unzip -o -q matomo-$VERSION.zip
cp -R matomo/* $MATOMO_ROOT
rm -r matomo/
rm matomo-$VERSION.zip
rm "How to install Matomo.html"
find $MATOMO_ROOT/misc/* ! -name 'gpl-3.0.txt' -exec rm -rf {} +
rm -rf $MATOMO_ROOT/tmp
rm -rf $MATOMO_ROOT/tests
rm -rf $MATOMO_ROOT/config/manifest.inc.php
rm -rf $MATOMO_ROOT/piwik.js
# important to remove pclzip as it is shipped with WP and would need to use their lib
rm -rf $MATOMO_ROOT/matomo/app/vendor/piwik/decompress/libs/PclZip

echo -e "Done!... "
echo -e "We now need to hardcode the path to jquery.js in app/plugins/Overlay/client/client.js until we automated / changed things"
echo -e "Then need to manually generate the core assets js file and put it into the assets directory"
echo -e "Then execute 'php ../app/console wordpress:generate-language-files'"
echo -e "Then execute ./remove_not_needed_assets.sh"
