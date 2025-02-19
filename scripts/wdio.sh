#!/bin/bash

export WORDPRESS_FOLDER=test

cat > .env.script <<EOF
WOOCOMMERCE=1
WORDPRESS_FOLDER=test
UID=$UID
WITHOUT_MARKETPLACE=1
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
INSTALLING_FROM_ZIP=1
RESET_DATABASE=1
EOF

function cleanup {
  CUSTOM_ENV_FILE=.env.script npm run compose stop || true
}

trap cleanup EXIT

echo "Creating test environment..."
rm -rf ./docker/wordpress/test/setup_finished
CUSTOM_ENV_FILE=.env.script npm run compose up wordpress &> .e2e-docker-out &

# wait for docker-compose launch to finish
elapsed=0
until [ -e ./docker/wordpress/test/setup_finished ] || (( elapsed++ >= 420 )); do
  sleep 1
done

set -o allexport
source .env.default
source .env
source .env.script
set +o allexport

# run tests
echo "Running tests..."
EXIT_STATUS=0
wdio run ./wdio.conf.tracking.ts && wdio run ./wdio.conf.ts || EXIT_STATUS=$?
wdio run ./wdio.conf.uninstall.ts || EXIT_STATUS=$?
exit $EXIT_STATUS
