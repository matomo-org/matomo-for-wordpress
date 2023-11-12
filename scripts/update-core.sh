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

if [ ! -f "$MATOMO_ROOT/.github/scripts/clean-build.sh" ]; then
  mkdir -p $MATOMO_ROOT/.github/scripts
  wget -O "$MATOMO_ROOT/.github/scripts/clean-build.sh" 'https://raw.githubusercontent.com/matomo-org/matomo/5.x-dev/.github/scripts/clean-build.sh'
fi

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

rm -rf .git
cd ..

rm -r "${MATOMO_ROOT:?}/"* "${MATOMO_ROOT:?}/".*
cp -R matomo/* $MATOMO_ROOT
cp -R matomo/.* $MATOMO_ROOT
rm -r matomo/

# TODO: force the use of matomo-scoper after we're sure everything works
if [ ! -z "$MATOMO_SCOPER_PATH" ]; then
  echo "Running matomo-scoper..."

  # download manifest and lock file from github since it's not available in the built package
  wget "https://raw.githubusercontent.com/matomo-org/matomo/$VERSION/composer.json" -O "$MATOMO_ROOT/composer.json"
  wget "https://raw.githubusercontent.com/matomo-org/matomo/$VERSION/composer.lock" -O "$MATOMO_ROOT/composer.lock"

  php "$MATOMO_SCOPER_PATH/bin/matomo-scoper" scope -y  --rename-references "$MATOMO_ROOT"

  rm "$MATOMO_ROOT"/composer.json
  rm "$MATOMO_ROOT"/composer.lock
else
  echo "MATOMO_SCOPER_PATH not defined, skipping scoping."
fi

find $MATOMO_ROOT/misc/* -exec rm -rf {} +
rm -r $MATOMO_ROOT/js/piwik.js
rm -r $MATOMO_ROOT/CONTRIBUTING.md
rm -r $MATOMO_ROOT/CHANGELOG.md
rm -r $MATOMO_ROOT/plugins/Morpheus/fonts/selection.json
rm -r $MATOMO_ROOT/lang/README.md

cd $MATOMO_ROOT
cp package.json package.json.keep # we want to keep these files
cp package-lock.json package-lock.json.keep
cp .gitignore .gitignore.keep
cp -R node_modules node_modules.keep

chmod +x ./.github/scripts/clean-build.sh
./.github/scripts/clean-build.sh

mv package.json.keep package.json
mv package-lock.json.keep package-lock.json
mv .gitignore.keep .gitignore
rm -rf node_modules && mv node_modules.keep node_modules
cd $SCRIPTPATH

# we need to remove jquery as it is shipped with wordpress and we use their jquery
rm -rf $MATOMO_ROOT/node_modules/jquery
# TODO: move following to .gitattributes
# find $MATOMO_ROOT/node_modules/jquery-ui-dist -name '*.*' ! -name 'jquery-ui.min.css' ! -name 'LICENSE.txt' ! -name 'AUTHORS.txt' ! -name 'jquery-ui.theme.min.css' -exec rm -rf {} +
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

npm run compose -- run console wordpress:generate-lang-files

echo -e "Done!... "
