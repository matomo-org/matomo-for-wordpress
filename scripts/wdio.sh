#!/bin/bash

export WORDPRESS_FOLDER=test

function wait_for_docker_compose_up() {
  FOLDER=$1

  elapsed=0
  until [ -e ./docker/wordpress/$FOLDER/setup_finished ] || (( elapsed++ >= 420 )); do
    sleep 1
  done

  if [ ! -e ./docker/wordpress/$FOLDER/setup_finished ]; then
    echo "did not setup local environment in time"
    exit 1
  fi
}

function docker_compose_up() {
  FOLDER=$1

  rm -rf ./docker/wordpress/$FOLDER/setup_finished
  CUSTOM_ENV_FILE=.env.script npm run compose up wordpress &>> .e2e-docker-out &
  wait_for_docker_compose_up $FOLDER
}

function cleanup {
  CUSTOM_ENV_FILE=.env.script npm run compose stop || true
}

echo "Creating test release..." # must be done before creating environment with latest stable version

cat > .env.script <<EOF
WOOCOMMERCE=0
WORDPRESS_FOLDER=test
UID=$UID
WITHOUT_MARKETPLACE=1
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
RESET_DATABASE=1
EOF

docker_compose_up test
CUSTOM_ENV_FILE=.env.script npm run matomo:console -- wordpress:build-release --name=test --zip
mv matomo-test.zip matomo.zip
cleanup

export RELEASE_ZIP="$( pwd )/matomo.zip"

trap cleanup EXIT

echo "Creating test environment..."
echo INSTALLING_FROM_ZIP=1 >> .env.script
sed -i 's/WOOCOMMERCE=0/WOOCOMMERCE=1/' .env.script
docker_compose_up test

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
