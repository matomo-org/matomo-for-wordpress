#!/usr/bin/env bash

# TODO: instructions on running this file

set -e

VERSION="$1"

function catch_error() {
    status=$? bc="$BASH_COMMAND" ln="$BASH_LINENO"
    echo ">> Command '$bc' failed on line $ln and status is $status <<" >&2
    exit $status
}

# report error and exit
function die() {
        echo -e "$0: $1"
        exit 2
}

[ ! -z "$VERSION" ] || die "Expected a Matomo version number as a parameter"
which git &> /dev/null || die "git is required for this script"
which composer &> /dev/null || die "composer is required for this script"

trap catch_error ERR

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
MATOMO_ROOT="$SCRIPTPATH/../app/";

cd $SCRIPTPATH
echo -e "Upgrading to $VERSION"

echo -e "Downloading $VERSION from git..."

rm -rf matomo/ 2> /dev/null
git clone --recurse-submodules --depth 1 --branch "$VERSION" https://github.com/matomo-org/matomo.git matomo || die "Got an error while downloading this Matomo version"
cp $MATOMO_ROOT/bootstrap.php bootstrap.php
cp $MATOMO_ROOT/.htaccess .htaccess

cd matomo/
rm -r ./tests
# delete most submodules (copied from https://github.com/matomo-org/matomo/blob/5.x-dev/.github/scripts/build-package.sh)
SUBMODULES_PACKAGED_WITH_CORE='log-analytics|plugins/Morpheus/icons|plugins/TagManager'
for P in $(git submodule status | egrep -v $SUBMODULES_PACKAGED_WITH_CORE | awk '{print $2}')
do
    echo "removing $P"
    rm -Rf ./$P
done
# Remove and deactivate the TestRunner plugin in production build
sed -i '/Plugins\[\] = TestRunner/d' config/global.ini.php
rm -rf plugins/TestRunner

composer install --no-dev -o -q --ignore-platform-reqs

find . -name .git -exec rm -rf {} +
cd ..

rm -r "${MATOMO_ROOT:?}/"* "${MATOMO_ROOT:?}/".*
cp -R matomo/* $MATOMO_ROOT
cp -R matomo/.* $MATOMO_ROOT
rm -r matomo/

# TODO: force the use of matomo-scoper after we're sure everything works
if [ ! -z "$MATOMO_SCOPER_PATH" ]; then
  echo "Running matomo-scoper..."

  php "$MATOMO_SCOPER_PATH/bin/matomo-scoper" scope -y  --rename-references "$MATOMO_ROOT"
else
  echo "MATOMO_SCOPER_PATH not defined, skipping scoping."
fi

find $MATOMO_ROOT/misc/* -exec rm -rf {} +
rm -r $MATOMO_ROOT/js/piwik.js
rm -r $MATOMO_ROOT/CONTRIBUTING.md
rm -r $MATOMO_ROOT/CHANGELOG.md
rm -r $MATOMO_ROOT/plugins/Morpheus/fonts/selection.json
rm -r $MATOMO_ROOT/lang/README.md
rm -r $MATOMO_ROOT/plugins/Example*
rm -r $MATOMO_ROOT/plugins/*/tests
find $MATOMO_ROOT -name .github -exec rm -rf {} +
rm -r $MATOMO_ROOT/plugins/*/config/test.php
rm -r $MATOMO_ROOT/plugins/*/config/ui-test.php
rm -r $MATOMO_ROOT/plugins/*/screenshots
rm -r $MATOMO_ROOT/tmp/CACHEDIR.TAG
find $MATOMO_ROOT/plugins/Morpheus/icons \( -type f -o -type l \) -not -path "$MATOMO_ROOT/plugins/Morpheus/icons/dist/*" -exec rm -rf {} +
find $MATOMO_ROOT -name "*.spec.js" -exec rm -rf {} +
rm $MATOMO_ROOT/HIRING.md
rm $MATOMO_ROOT/.travis.yml
rm $MATOMO_ROOT/.scrutinizer.yml
rm $MATOMO_ROOT/.coveralls.yml
rm $MATOMO_ROOT/.gitmodules

cd $SCRIPTPATH

rm -rf $MATOMO_ROOT/config/environment/test.php
rm -rf $MATOMO_ROOT/config/environment/ui-test.php
rm -rf $MATOMO_ROOT/vendor/twig/twig/ext
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/tools
rm -rf $MATOMO_ROOT/vendor/doctrine/cache/lib/Doctrine/Common/Cache/RiakCache.php
rm -rf $MATOMO_ROOT/node_modules/materialize-css/extras $MATOMO_ROOT/node_modules/materialize-css/js $MATOMO_ROOT/node_modules/materialize-css/sass

touch $MATOMO_ROOT/tmp/.gitkeep # we keep this folder to avoid permissions issues in the local docker environment

# remove the plugins also from auto loader so they can be installed through marketplace
if [ -d "$MATOMO_ROOT/plugins/Provider" ]; then
  rm -rf $MATOMO_ROOT/plugins/Provider
  awk '!/Plugins\\\\Provider/' $MATOMO_ROOT/vendor/composer/autoload_classmap.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_classmap.php
  awk '!/Plugins\\\\Provider/' $MATOMO_ROOT/vendor/composer/autoload_static.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_static.php
fi

if [ -d "$MATOMO_ROOT/plugins/CustomVariables" ]; then
  rm -rf $MATOMO_ROOT/plugins/CustomVariables
  awk '!/Plugins\\\\CustomVariables/' $MATOMO_ROOT/vendor/composer/autoload_classmap.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_classmap.php
  awk '!/Plugins\\\\CustomVariables/' $MATOMO_ROOT/vendor/composer/autoload_static.php > temp && mv temp $MATOMO_ROOT/vendor/composer/autoload_static.php
fi

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

RED='\033[0;31m'
NO_COLOR='\033[0m'

npm run compose -- run console wordpress:generate-lang-files || echo "${RED}Failed to generate lang files! Make sure to run 'npm run compose -- run console wordpress:generate-lang-files' after fixing the issue!${NO_COLOR}"

echo -e "Done!... "
