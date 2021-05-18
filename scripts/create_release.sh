#!/usr/bin/env bash
VERSION="$1"

wget https://github.com/matomo-org/matomo-for-wordpress/archive/live.zip
unzip live.zip
mv matomo-for-wordpress-live matomo
zip -r wordpress-matomo-$VERSION.zip matomo
rm live.zip
rm -rf matomo
scp -p wordpress-matomo-$VERSION.zip "piwik-builds@matomo.org:/home/piwik-builds/www/builds.piwik.org/"
scp -p wordpress-matomo-$VERSION.zip "piwik-builds@matomo.org:/home/piwik-builds/www/builds.piwik.org/wordpress-matomo-latest.zip"
rm wordpress-matomo-$VERSION.zip
