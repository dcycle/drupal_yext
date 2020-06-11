#!/bin/bash
#
# Assuming you have the latest version Docker installed, this script will
# fully create or update your development environment.
#
set -e

if [ "$1" == 8 ]; then
  DRUPAL_VERSION=8
else
  DRUPAL_VERSION=9
fi

echo ''
echo 'About to try to get the latest version of'
echo 'https://hub.docker.com/r/dcycle/drupal/ from the Docker hub. This image'
echo 'is updated automatically every Wednesday with the latest version of'
echo 'Drupal and Drush. If the image has changed since the latest deployment,'
echo 'the environment will be completely rebuilt based on this image.'
source ./scripts/lib/docker-pull-image"$DRUPAL_VERSION".source.sh

echo ''
echo '-----'
echo 'About to start persistent (-d) containers based on the images defined'
echo 'in ./Dockerfile and ./docker-compose.yml. We are also telling'
echo 'docker-compose to rebuild the images if they are out of date.'
source ./scripts/lib/docker-compose-build"$DRUPAL_VERSION".source.sh

echo ''
echo '-----'
echo 'Running the deploy script on the running containers. This installs'
echo 'Drupal if it is not yet installed.'
docker-compose exec drupal /docker-resources/scripts/deploy-on-container.sh

echo ''
echo '-----'
echo ''
echo 'If all went well you can now access your site at:'
./scripts/uli.sh
echo '-----'
echo ''
