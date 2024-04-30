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
rm -r matomo/vendor/phpmailer # removing before scoping so it won't be included in the autoloader files
cp -R matomo/* $MATOMO_ROOT
cp -R matomo/.* $MATOMO_ROOT
rm -r matomo/

if [ ! -z "$MATOMO_SCOPER_PATH" ]; then
  echo "Running matomo-scoper..."

  php "$MATOMO_SCOPER_PATH/bin/matomo-scoper" scope -y  --rename-references --ignore-platform-check "$MATOMO_ROOT"
else
  echo "Error: MATOMO_SCOPER_PATH not defined."
  exit 1;
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
rm $MATOMO_ROOT/.gitmodules
rm $MATOMO_ROOT/.gitattributes # rely on root .gitattributes only

cd $SCRIPTPATH

find $MATOMO_ROOT/ -iname 'tests' -type d -prune -exec rm -rf {} \;

rm -rf $MATOMO_ROOT/vendor/bin
rm -rf $MATOMO_ROOT/vendor/prefixed/bin
rm -rf $MATOMO_ROOT/config/environment/test.php
rm -rf $MATOMO_ROOT/config/environment/ui-test.php
rm -rf $MATOMO_ROOT/vendor/prefixed/twig/twig/ext
rm -rf $MATOMO_ROOT/vendor/twig/twig/ext
rm -rf $MATOMO_ROOT/vendor/doctrine/cache/lib/Doctrine/Common/Cache/RiakCache.php
rm -rf $MATOMO_ROOT/node_modules/materialize-css/extras $MATOMO_ROOT/node_modules/materialize-css/js $MATOMO_ROOT/node_modules/materialize-css/sass
rm -rf $MATOMO_ROOT/vendor/container-interop/container-interop/docs
rm -rf $MATOMO_ROOT/vendor/davaxi/sparkline/composer-8.json
rm -rf $MATOMO_ROOT/vendor/davaxi/sparkline/docker-compose.yml
rm -rf $MATOMO_ROOT/vendor/davaxi/sparkline/Dockerfile
rm -rf $MATOMO_ROOT/vendor/prefixed/geoip2/geoip2/examples/
rm -rf $MATOMO_ROOT/vendor/lox/xhprof/bin
rm -rf $MATOMO_ROOT/vendor/lox/xhprof/examples
rm -rf $MATOMO_ROOT/vendor/lox/xhprof/scripts
rm -rf $MATOMO_ROOT/vendor/lox/xhprof/extension
rm -rf $MATOMO_ROOT/vendor/lox/xhprof/xhprof_html
rm -rf $MATOMO_ROOT/vendor/maxmind-db/reader/ext/
rm -rf $MATOMO_ROOT/vendor/maxmind-db/reader/autoload.php
rm -rf $MATOMO_ROOT/vendor/maxmind-db/reader/CHANGELOG.md
rm -rf $MATOMO_ROOT/vendor/maxmind/web-service-common/dev-bin/
rm -rf $MATOMO_ROOT/vendor/maxmind/web-service-common/CHANGELOG.md
rm -rf $MATOMO_ROOT/vendor/prefixed/pear/archive_tar/docs
rm -rf $MATOMO_ROOT/vendor/php-di/invoker/doc/
rm -rf $MATOMO_ROOT/vendor/php-di/php-di/benchmarks/
rm -rf $MATOMO_ROOT/vendor/symfony/console/Symfony/Component/Console/Resources/bin
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/doc
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/coverage.sh
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/codeception.yml
rm -f $MATOMO_ROOT/vendor/prefixed/monolog/monolog/phpstan.neon.dist
rm -f $MATOMO_ROOT/vendor/monolog/monolog/phpstan.neon.dist
rm $MATOMO_ROOT/vendor/composer/installed.json
rm $MATOMO_ROOT/vendor/lox/xhprof/package.xml
rm $MATOMO_ROOT/vendor/prefixed/pear/archive_tar/package.xml
rm $MATOMO_ROOT/vendor/prefixed/pear/console_getopt/package.xml
rm $MATOMO_ROOT/core/Mail/Transport.php

rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/tools
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/examples
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/CHANGELOG.TXT
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/ae_fonts_2.0
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.33
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.34
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freefont-20100919
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freefont-20120503
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freemon*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/cid*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/courier*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/aefurat*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavusansb*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavusansi*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavusansmono*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavusanscondensed*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavusansextralight*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/dejavuserif*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freesansi*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freesansb*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freeserifb*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/freeserifi*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/pdf*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/times*
rm -rf $MATOMO_ROOT/vendor/tecnickcom/tcpdf/fonts/uni2cid*

rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/advent_light*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/Bedizen*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/calibri*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/Forgotte*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/MankSans*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/pf_arma_five*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/Silkscreen*
rm -rf $MATOMO_ROOT/vendor/szymach/c-pchart/resources/fonts/verdana*

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

# we want to include vendor in git
sed -i -e 's/\/vendor\///' $MATOMO_ROOT/.gitignore

# unignoring @materializecss/materialize doesn't seem to work as expected
sed -i -e 's/!\/node_modules\/@materializecss\/materialize/!\/node_modules\/@materializecss/' $MATOMO_ROOT/.gitignore

RED='\033[0;31m'
NO_COLOR='\033[0m'

npm run compose -- run console wordpress:generate-lang-files || echo -e "${RED}Failed to generate lang files! Make sure to run 'npm run compose -- run console wordpress:generate-lang-files' after fixing the issue!${NO_COLOR}"

echo -e "Done!... "
