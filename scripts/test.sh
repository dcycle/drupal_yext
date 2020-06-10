#!/bin/bash
#
# Run all tests and linting.
#

set -e

echo "Make sure we have no deprecated code which won't work with Drupal 9"
docker run --rm -v "$(pwd)":/var/www/html/modules/drupal_yext dcycle/drupal-check:1.2019-12-30-21-59-43-UTC drupal_yext/src
docker run --rm -v "$(pwd)":/var/www/html/modules/drupal_yext dcycle/drupal-check:1.2019-12-30-21-59-43-UTC drupal_yext/modules/drupal_yext_find_by_title/src
docker run --rm -v "$(pwd)":/var/www/html/modules/drupal_yext dcycle/drupal-check:1.2019-12-30-21-59-43-UTC drupal_yext/modules/drupal_yext_sync_deleted/src
./scripts/lint-php.sh
./scripts/lint-sh.sh
./scripts/unit-tests.sh
