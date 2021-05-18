#!/usr/bin/env bash
VERSION="$1"

hub release create --message $VERSION --message "If you download this release, make sure the directory name within the 'wordpress/wp-content' directory is 'matomo' and not for example 'matomo-for-wordpress'. [View changes](https://github.com/matomo-org/matomo-for-wordpress/blob/develop/CHANGELOG.md)"  -t live  $VERSION
