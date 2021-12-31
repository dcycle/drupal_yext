#!/bin/bash
#
# Static analysis.
#
set -e

echo 'Static analysis of PHP files with https://github.com/dcycle/docker-phpstan-drupal'
echo 'If you are getting a false negative, use:'
echo ''
echo '// @phpstan-ignore-next-line'
echo ''

docker run --rm \
  -v "$(pwd)":/var/www/html/modules/custom/drupal_yext \
  -v "$(pwd)"/scripts/lib/phpstan:/phpstan-drupal \
  dcycle/phpstan-drupal:4 /var/www/html/modules/custom \
  -c /phpstan-drupal/phpstan.neon \
  --memory-limit=-1
