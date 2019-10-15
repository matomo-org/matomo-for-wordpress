VERSION="$1"

wget https://github.com/matomo-org/wp-matomo/archive/master.zip
unzip master.zip
mv wp-matomo-master matomo
zip -r wordpress-matomo-$VERSION.zip matomo
rm master.zip
rm -rf matomo
scp -p wordpress-matomo-$VERSION.zip "piwik-builds@matomo.org:/home/piwik-builds/www/builds.piwik.org/"
rm wordpress-matomo-$VERSION.zip
