#!/usr/bin/env bash
VERSION="$1"

hub release create --message $VERSION --message "If you download this release, make sure the directory name within the 'wordpress/wp-content' directory is 'matomo' and not for example 'wp-matomo'."  -t master  $VERSION