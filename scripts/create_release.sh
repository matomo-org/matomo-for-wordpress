#!/usr/bin/env bash
VERSION="$1"

wget https://github.com/matomo-org/wp-matomo/archive/live.zip
unzip live.zip
mv wp-matomo-live matomo
zip -r wordpress-matomo-$VERSION.zip matomo
rm live.zip
rm -rf matomo
scp -p wordpress-matomo-$VERSION.zip "piwik-builds@matomo.org:/home/piwik-builds/www/builds.piwik.org/"
scp -p wordpress-matomo-$VERSION.zip "piwik-builds@matomo.org:/home/piwik-builds/www/builds.piwik.org/wordpress-matomo-latest.zip"
rm wordpress-matomo-$VERSION.zip
