VERSION="$1"

wget https://github.com/matomo-org/wp-matomo/archive/master.zip
unzip master.zip
mv wp-matomo-master matomo
zip -r wordpress-matomo-$VERSION.zip matomo
rm master.zip
rm -rf matomo