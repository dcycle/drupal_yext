#!/bin/bash
#
# Run some self-tests. Must be on a running environment.
#

docker-compose exec drupal /bin/bash -c 'drush en -y drupal_yext_find_by_title'
docker-compose exec drupal /bin/bash -c 'drush ev "drupal_yext()->selftest();"'
