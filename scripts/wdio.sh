#!/bin/bash

export WORDPRESS_FOLDER=test
TRACKING_WORDPRESS_FOLDER=test-tracking
E2E_SETUP_TIMEOUT=${E2E_SETUP_TIMEOUT:-900}

function wait_for_docker_compose_up() {
  FOLDER=$1
  COMPOSE_UP_PID=$2

  elapsed=0
  until [ -e "./docker/wordpress/$FOLDER/setup_finished" ] || (( elapsed++ >= E2E_SETUP_TIMEOUT )); do
    if ! kill -0 "$COMPOSE_UP_PID" 2>/dev/null; then
      wait "$COMPOSE_UP_PID" || true
      echo "WordPress container exited before $FOLDER setup completed. Recent setup output:"
      tail -n 200 .e2e-docker-out
      exit 1
    fi
    sleep 1
  done

  if [ ! -e "./docker/wordpress/$FOLDER/setup_finished" ]; then
    echo "did not setup local environment in ${E2E_SETUP_TIMEOUT} seconds"
    echo "Recent setup output:"
    tail -n 200 .e2e-docker-out
    exit 1
  fi
}

function docker_compose_up() {
  FOLDER=$1

  rm -rf ./docker/wordpress/$FOLDER/setup_finished
  CUSTOM_ENV_FILE=.env.script npm run compose up wordpress &>> .e2e-docker-out &
  COMPOSE_UP_PID=$!
  wait_for_docker_compose_up "$FOLDER" "$COMPOSE_UP_PID"
}

function cleanup {
  CUSTOM_ENV_FILE=.env.script npm run compose stop || true
}

echo "Creating test release..." # must be done before creating environment with latest stable version
: > .e2e-docker-out

cat > .env.script <<EOF
WOOCOMMERCE=0
WORDPRESS_FOLDER=test
UID=$UID
WITHOUT_MARKETPLACE=1
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
RESET_DATABASE=1
MATOMO_DISABLE_WP_ARCHIVING=1
PHP_OPCACHE=1
EOF

docker_compose_up test
CUSTOM_ENV_FILE=.env.script npm run matomo:console -- wordpress:build-release --name=test --zip
mv matomo-test.zip matomo.zip
cleanup

export RELEASE_ZIP="$( pwd )/matomo.zip"

trap cleanup EXIT

echo "Creating test environments..."
echo INSTALLING_FROM_ZIP=1 >> .env.script
echo MATOMO_PLUGIN_ZIP=matomo.zip >> .env.script
sed -i 's/WOOCOMMERCE=0/WOOCOMMERCE=1/' .env.script

# one install per wdio run
# release instead of the one under test, since the tracking run tests updating to the release.
echo "Creating $TRACKING_WORDPRESS_FOLDER environment..."
if ! CUSTOM_ENV_FILE=.env.script npm run compose -- run --rm -e WORDPRESS_FOLDER=$TRACKING_WORDPRESS_FOLDER -e WITHOUT_MULTISITE=1 -e INSTALL_ONLY=1 -e MATOMO_PLUGIN_ZIP= wordpress &>> .e2e-docker-out; then
  echo "failed to set up the $TRACKING_WORDPRESS_FOLDER environment"
  exit 1
fi

echo "Creating $WORDPRESS_FOLDER environments..."
docker_compose_up $WORDPRESS_FOLDER

set -o allexport
source .env.default
source .env
source .env.script
set +o allexport

# run tests
echo "Running tests..."
EXIT_STATUS=0

# the serial run uses its own install, so it runs alongside the main run
(
  WORDPRESS_FOLDER=$TRACKING_WORDPRESS_FOLDER wdio run ./wdio.conf.tracking.ts 2>&1 | sed -u 's/^/[serial] /'
  exit "${PIPESTATUS[0]}"
) &
SERIAL_PID=$!

wdio run ./wdio.conf.ts || EXIT_STATUS=$?
wait $SERIAL_PID || EXIT_STATUS=$?
exit $EXIT_STATUS
