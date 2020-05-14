#!/bin/bash

# source: https://github.com/10up/action-wordpress-plugin-asset-update/blob/develop/entrypoint.sh
# https://github.com/10up/action-wordpress-plugin-asset-update/
# License: MIT see https://github.com/10up/action-wordpress-plugin-asset-update/blob/develop/LICENSE

# Modifications from the Matomo team for our use case

VERSION=$1
SVN_USERNAME=$2
SVN_PASSWORD=$3

# Note that this does not use pipefail because if the grep later
# doesn't match I want to be able to show an error first
set -eo

# Ensure SVN username and password are set
# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!
if [[ -z "$VERSION" ]]; then
	echo "Set the VERSION number"
	exit 1
fi

if [[ -z "$SVN_USERNAME" ]]; then
	echo "Set the SVN_USERNAME secret"
	exit 1
fi

if [[ -z "$SVN_PASSWORD" ]]; then
	echo "Set the SVN_PASSWORD secret"
	exit 1
fi

# Allow some ENV variables to be customized
SLUG=matomo

echo "ℹ︎ SLUG is $SLUG"

ASSETS_DIR=".wordpress-org"
echo "ℹ︎ ASSETS_DIR is $ASSETS_DIR"

README_NAME="readme.txt"
echo "ℹ︎ README_NAME is $README_NAME"

mkdir -p /tmp/github

SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="/tmp/github/svn-${SLUG}"
rm -rf "$SVN_DIR"

GITHUB_WORKSPACE="/tmp/github/git-${SLUG}"
rm -rf "$GITHUB_WORKSPACE"

TMP_DIR="/tmp/github/archivetmp"
rm -rf "$TMP_DIR"
mkdir -p "$TMP_DIR"

# Checkout just trunk and assets for efficiency
# Stable tag will come later, if applicable
echo "➤ Checking out svn .org repository..."
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"
cd "$SVN_DIR"
svn update --set-depth infinity assets
svn update --set-depth infinity trunk

echo "➤ Checking out git wp-matomo repository..."
git clone --single-branch --branch master git@github.com:matomo-org/wp-matomo.git "$GITHUB_WORKSPACE"

echo "➤ Copying files..."

cd "$GITHUB_WORKSPACE"
git archive HEAD | tar x --directory="$TMP_DIR"

cd "$SVN_DIR"

# Copy from clean copy to /trunk, excluding dotorg assets
# The --delete flag will delete anything in destination that no longer exists in source
rsync -rc "$TMP_DIR/" trunk/ --delete --delete-excluded

# Copy dotorg assets to /assets
rsync -rc "$GITHUB_WORKSPACE/$ASSETS_DIR/" assets/ --delete --delete-excluded

# Add everything and commit to SVN
# The force flag ensures we recurse into subdirectories even if they are already added
# Suppress stdout in favor of svn status later for readability
echo "➤ Preparing files..."
svn add . --force > /dev/null

# SVN delete all deleted files
# Also suppress stdout here
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null

# Copy tag locally to make this a single commit
echo "➤ Copying tag..."
svn cp "trunk" "tags/$VERSION"

# Fix screenshots getting force downloaded when clicking them
# https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
svn propset svn:mime-type image/png assets/*.png || true
svn propset svn:mime-type image/jpeg assets/*.jpg || true

svn status

echo "➤ Committing files..."
svn commit -m "Update to version $VERSION from GitHub" --no-auth-cache --non-interactive  --username "$SVN_USERNAME" --password "$SVN_PASSWORD"

echo "✓ Plugin deployed!"
